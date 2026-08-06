# Installation

## Requirements

* Icinga Web 2 (>= 2.9)
* [Icinga Director](https://github.com/Icinga/icingaweb2-module-director)
* PHP >= 7.4 with the `curl` and `json` extensions
* Network access from the Icinga Director container/host to the Traefik
  instance(s) you want to import from (Traefik's API must be reachable,
  e.g. via `--api.insecure=true` in a trusted network, or exposed behind
  auth)

## Installing

Clone (or extract a release tarball of) this repository into your Icinga Web 2
modules directory as `traefik`:

```bash
git clone https://github.com/EmTeedee/icingaweb2-module-traefik.git \
    /usr/share/icingaweb2/modules/traefik
```

Then enable the module, either via the Icinga Web 2 UI (*Configuration >
Modules > traefik > Enable*) or on the command line:

```bash
icingacli module enable traefik
```

### Docker / Compose based setups

If your Icinga Web 2 / Director containers use a bind-mounted modules
directory, clone this repository to a path on the host and bind-mount it into
both the `icingaweb2` and `director` containers at
`/usr/share/icingaweb2/modules/traefik`, then add `traefik` to whatever
mechanism your setup uses to enable modules (e.g. an
`icingaweb.enabledModules` environment variable).

## Next steps

Continue with [Configuration](03-Configuration.md) to set up an Import
Source, then [Usage](04-Usage.md) for the full Director walkthrough (Sync
Rule and Service Apply Rule).
