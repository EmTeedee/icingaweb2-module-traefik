<?php

namespace Icinga\Module\Traefik\ProvidedHook\Director;

use Exception;
use Icinga\Module\Director\Hook\ImportSourceHook;
use Icinga\Module\Director\Web\Form\QuickForm;

class ImportSource extends ImportSourceHook
{
    public function getName()
    {
        return 'Traefik HTTP Routers';
    }

    public function fetchData()
    {
        $baseUrl = rtrim((string) $this->getSetting('base_url'), '/');
        $username = $this->getSetting('username');
        $password = $this->getSetting('password');
        $verifyTls = ! $this->getSetting('insecure', false);
        $includeInternal = (bool) $this->getSetting('include_internal', false);
        $timeout = (int) $this->getSetting('timeout', 10);
        if ($timeout <= 0) {
            $timeout = 10;
        }
        $excludePaths = $this->getExcludePaths();

        $routers = $this->fetchJson(
            $baseUrl . '/api/http/routers',
            $username,
            $password,
            $verifyTls,
            $timeout
        );

        $hosts = [];

        foreach ($routers as $router) {
            if (empty($router->rule)) {
                continue;
            }

            $routerName = isset($router->name) ? $router->name : null;
            $provider = null;
            if ($routerName !== null && strpos($routerName, '@') !== false) {
                list(, $provider) = explode('@', $routerName, 2);
            }

            if (!$includeInternal && ($provider === 'internal')) {
                continue;
            }

            list($fqdns, $paths) = $this->parseRule($router->rule);

            if (! empty($excludePaths)) {
                $paths = array_values(array_diff($paths, $excludePaths));
            }

            if (empty($fqdns)) {
                continue;
            }

            foreach ($fqdns as $fqdn) {
                if (!$includeInternal && (strpos($router->service, '@internal') !== false)) {
                    continue;
                }
                if (! isset($hosts[$fqdn])) {
                    $hosts[$fqdn] = (object) [
                        'name'        => $fqdn,
                        'address'     => $fqdn,
                        'http_urls'   => [],
                        'routers'     => [],
                        'services'    => [],
                        'entrypoints' => [],
                        'tls'         => false,
                        'provider'    => $provider,
                    ];
                }

                $host = $hosts[$fqdn];

                $host->http_urls = array_values(array_unique(array_merge($host->http_urls, $paths)));

                if ($routerName !== null) {
                    $host->routers = array_values(array_unique(array_merge($host->routers, [$routerName])));
                }

                if (! empty($router->service)) {
                    $host->services = array_values(array_unique(array_merge($host->services, [$router->service])));
                }

                if (! empty($router->entryPoints)) {
                    $host->entrypoints = array_values(array_unique(
                        array_merge($host->entrypoints, (array) $router->entryPoints)
                    ));
                }

                if (! empty($router->tls)) {
                    $host->tls = true;
                }
            }
        }

        foreach ($hosts as $host) {
            if (empty($host->http_urls)) {
                $host->http_urls = ['/'];
            }
        }

        return array_values($hosts);
    }

    public function listColumns()
    {
        return [
            'name',
            'address',
            'http_urls',
            'routers',
            'services',
            'entrypoints',
            'tls',
            'provider',
        ];
    }

    public static function getDefaultKeyColumnName()
    {
        return 'name';
    }

    public static function addSettingsFormFields(QuickForm $form)
    {
        $form->addElement('text', 'base_url', [
            'label'       => 'Traefik API base URL',
            'description' => $form->translate(
                'Base URL of the Traefik instance, reachable from this Icinga Director'
                . ' container, e.g. https://traefik.example.com:8080.'
                . ' "/api/http/routers" is appended automatically.'
            ),
            'required'    => true,
        ]);

        $form->addElement('text', 'username', [
            'label'       => 'Username',
            'description' => $form->translate(
                'Optional HTTP Basic Auth username for the Traefik API.'
            ),
            'required'    => false,
        ]);

        $form->addElement('password', 'password', [
            'label'          => 'Password',
            'description'    => $form->translate(
                'Optional HTTP Basic Auth password for the Traefik API.'
            ),
            'required'       => false,
            'renderPassword' => true,
        ]);

        $form->addElement('checkbox', 'insecure', [
            'label'       => 'Skip TLS verification',
            'description' => $form->translate(
                'Disable TLS certificate verification, e.g. for self-signed certificates.'
            ),
            'required'    => false,
        ]);

        $form->addElement('checkbox', 'include_internal', [
            'label'       => 'Include internal routers',
            'description' => $form->translate(
                'Also import Traefik-internal routers such as api@internal and dashboard@internal.'
            ),
            'required'    => false,
        ]);

        $form->addElement('text', 'timeout', [
            'label'       => 'HTTP timeout',
            'description' => $form->translate('Request timeout in seconds.'),
            'value'       => 10,
            'required'    => false,
        ]);

        $form->addElement('text', 'exclude_paths', [
            'label'       => 'Exclude paths',
            'description' => $form->translate(
                'Comma-separated list of paths to exclude from http_urls (exact match'
                . ' against the Path()/PathPrefix() values), e.g. /metrics,/health.'
                . ' A host whose only paths are excluded falls back to "/".'
            ),
            'required'    => false,
        ]);
    }

    /**
     * Paths configured in the "exclude_paths" setting: a comma-separated list
     * of exact paths (as they appear in Path()/PathPrefix() matchers) that
     * should never end up in http_urls, e.g. "/metrics,/health".
     */
    protected function getExcludePaths()
    {
        $raw = (string) $this->getSetting('exclude_paths', '');

        if (trim($raw) === '') {
            return [];
        }

        $paths = array_map('trim', explode(',', $raw));

        return array_values(array_filter($paths, function ($path) {
            return $path !== '';
        }));
    }

    /**
     * Extract FQDNs (from Host/HostSNI matchers) and paths (from Path/PathPrefix
     * matchers) out of a Traefik router rule string, e.g.:
     *   Host(`a.example.com`) && PathPrefix(`/api`,`/health`)
     */
    protected function parseRule($rule)
    {
        $fqdns = [];
        $paths = [];

        if (preg_match_all('/(\w+)\(([^)]*)\)/', $rule, $calls, PREG_SET_ORDER)) {
            foreach ($calls as $call) {
                $func = $call[1];

                if (! preg_match_all('/`([^`]*)`/', $call[2], $argMatches)) {
                    continue;
                }
                $args = $argMatches[1];

                switch ($func) {
                    case 'Host':
                    case 'HostSNI':
                        foreach ($args as $arg) {
                            $fqdns[] = strtolower($arg);
                        }
                        break;
                    case 'Path':
                    case 'PathPrefix':
                        foreach ($args as $arg) {
                            $paths[] = $arg;
                        }
                        break;
                }
            }
        }

        if (empty($paths)) {
            $paths[] = '/';
        }

        return [array_values(array_unique($fqdns)), array_values(array_unique($paths))];
    }

    protected function fetchJson($url, $username, $password, $verifyTls, $timeout)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => $verifyTls,
            CURLOPT_SSL_VERIFYHOST => $verifyTls ? 2 : 0,
        ]);

        if ($username !== null && $username !== '') {
            curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }

        $body = curl_exec($ch);

        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception(sprintf('Failed to fetch %s: %s', $url, $error));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            throw new Exception(sprintf('Unexpected HTTP status %d fetching %s', $status, $url));
        }

        $data = json_decode($body);

        if (! is_array($data)) {
            throw new Exception(sprintf('Expected a JSON array from %s', $url));
        }

        return $data;
    }
}
