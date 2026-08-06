# Icinga Web 2 module for Traefik

[![PHP Lint](https://github.com/EmTeedee/icingaweb2-module-traefik/actions/workflows/php.yml/badge.svg)](https://github.com/EmTeedee/icingaweb2-module-traefik/actions/workflows/php.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)

This module provides an [Icinga Director](https://github.com/Icinga/icingaweb2-module-director)
**Import Source** that queries one or more [Traefik](https://traefik.io/)
instances' HTTP API and turns each router into an import row: the router's
FQDN becomes the host name/address, and any `Path`/`PathPrefix` matchers
become an `http_urls` array property, ready to feed a Sync Rule and a
Service Apply Rule that create/update hosts and HTTP checks automatically as
your Traefik routers change.

## Documentation

* [Installation](doc/01-Installation.md)
* [How it works](doc/02-How-it-works.md)
* [Configuration](doc/03-Configuration.md)
* [Usage](doc/04-Usage.md) — full Director walkthrough (Sync Rule, Service Apply Rule)

## License

This module is licensed under the [MIT License](LICENSE.md).
