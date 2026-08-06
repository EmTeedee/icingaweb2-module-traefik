# How it works

This module ships a single Icinga Director **Import Source** hook, `Traefik
HTTP Routers`. It queries a Traefik instance's `/api/http/routers` endpoint
and turns each router's `rule` into an import row:

* `Host(...)` / `HostSNI(...)` matchers become the row's `name`/`address`
  (the FQDN).
* `Path(...)` / `PathPrefix(...)` matchers become the row's `http_urls` array
  property (paths only, not full URLs — build the full URL in an apply rule,
  see [Usage](04-Usage.md)). **If a router has no `Path`/`PathPrefix` matcher
  at all, `http_urls` still comes out as `["/"]`** rather than empty, so every
  imported host always has at least one entry to apply a check against.
* Routers with the same FQDN (e.g. an http->https redirect router plus the
  real one) are merged into a single row; their paths, router names,
  services and entrypoints are unioned.
* Internal Traefik routers (`...@internal`, e.g. `api@internal`,
  `dashboard@internal`) are skipped by default.
* The **"Exclude paths"** import source setting takes a comma-separated list
  of exact paths (e.g. `/metrics,/health`) to drop from `http_urls` globally,
  for every host coming from that source. A host whose only path(s) got
  excluded falls back to `["/"]`, same as a host with no `Path`/`PathPrefix`
  matcher at all.

## Import row columns

| Column        | Description                                                          |
|---------------|------------------------------------------------------------------------|
| `name`        | FQDN from the router's `Host()`/`HostSNI()` matcher; used as the key    |
| `address`     | Same FQDN, suitable for a host's `address`                             |
| `http_urls`   | Array of paths from `Path()`/`PathPrefix()` matchers, or `["/"]`       |
| `routers`     | Names of every Traefik router that contributed to this host            |
| `services`    | Names of every Traefik service backing this host                       |
| `entrypoints` | Union of entrypoints (e.g. `web`, `websecure`) across the routers      |
| `tls`         | `true` if any contributing router has TLS enabled                      |
| `provider`    | Traefik provider suffix from the router name (e.g. `docker`, `file`)   |

This only produces import rows — the actual Icinga objects are created by
wiring the source up in Icinga Director, see [Usage](04-Usage.md).
