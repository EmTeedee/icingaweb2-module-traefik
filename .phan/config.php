<?php

/**
 * Phan configuration for the Traefik Icinga Web 2 module.
 *
 * The module runs inside Icinga Web 2 / Icinga Director, so classes like
 * Icinga\Application\Modules\Module and Icinga\Module\Director\Hook\ImportSourceHook
 * are provided by the host application at runtime rather than by a composer
 * dependency. .phan/stubs declares minimal shapes for those so Phan can
 * resolve them; see .phan/stubs/icinga.php.
 */
return [
    'target_php_version' => '7.4',

    'directory_list' => [
        '.',
    ],

    'exclude_analysis_directory_list' => [
        'vendor/',
        '.phan/stubs/',
    ],
];
