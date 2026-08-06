# Icinga Web 2 module for Traefik

[![Validate](https://github.com/EmTeedee/icingaweb2-module-traefik/actions/workflows/php.yml/badge.svg)](https://github.com/EmTeedee/icingaweb2-module-traefik/actions/workflows/php.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![Latest release](https://img.shields.io/github/v/release/EmTeedee/icingaweb2-module-traefik)](https://github.com/EmTeedee/icingaweb2-module-traefik/releases/latest)

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

## Releasing

Bump `Version:` in [module.info](module.info), commit, then push a tag
matching it (e.g. `v1.0.1` for `Version: 1.0.1`). The
[Release workflow](.github/workflows/release.yml) verifies the tag matches
`module.info` and publishes a GitHub release with a
`icingaweb2-module-traefik-<version>.tar.gz` asset containing just the
runtime module files.

## License

This module is licensed under the [MIT License](LICENSE.md).
