# Configuration

Configuration happens per Import Source, under *Director > Import source >
Add*, source type **Traefik HTTP Routers**.

| Field                     | Required | Description                                                                                                                       |
|---------------------------|----------|-------------------------------------------------------------------------------------------------------------------------------------|
| Traefik API base URL      | yes      | Base URL of the Traefik instance, reachable from the Icinga Director container, e.g. `https://traefik.example.com:8080`. `/api/http/routers` is appended automatically. |
| Username                  | no       | Optional HTTP Basic Auth username for the Traefik API.                                                                            |
| Password                  | no       | Optional HTTP Basic Auth password for the Traefik API.                                                                            |
| Skip TLS verification     | no       | Disable TLS certificate verification, e.g. for self-signed certificates.                                                          |
| Include internal routers  | no       | Also import Traefik-internal routers such as `api@internal` and `dashboard@internal` (skipped by default).                       |
| HTTP timeout              | no       | Request timeout in seconds. Defaults to `10`.                                                                                     |
| Exclude paths             | no       | Comma-separated list of paths to exclude from `http_urls` (exact match against the `Path()`/`PathPrefix()` values), e.g. `/metrics,/health`. A host whose only paths are excluded falls back to `/`. |

Multiple Import Sources can be configured, one per Traefik instance, and fed
into the same Sync Rule — see [Usage](04-Usage.md).
