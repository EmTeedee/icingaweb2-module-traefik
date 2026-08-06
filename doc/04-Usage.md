# Usage

This walks through wiring the `Traefik HTTP Routers` import source up in
Icinga Director so that Traefik routers turn into monitored hosts and
services.

1. **Data Field**: under *Director > Data Fields*, create `http_urls` with
   data type `Array`, and associate it with your host template.
2. **Import Source**: under *Director > Import source*, create one per
   Traefik instance, source type `Traefik HTTP Routers`. Set the API base URL
   (e.g. `https://traefik1.internal:8080`) and, if the API is protected, the
   username/password. Enable "Skip TLS verification" for self-signed certs.
   See [Configuration](03-Configuration.md) for all available settings.
3. **Sync Rule**: create a Sync Rule of object type `Host`, add the import
   source(s) from step 2 (multiple Traefik instances can feed the same
   rule), set your desired host template, and map `address` -> host address
   and `http_urls` -> `vars.http_urls`.
4. **Service Apply Rule** ("Apply For"): since `http_urls` is now guaranteed
   non-empty for every Traefik-imported host, a single "Apply For" rule
   creates one service per URL — falling back to a single `/` service
   automatically for hosts where no explicit path was configured. See
   "Service apply rule details" below for the full walkthrough.

## Service apply rule details

Director's "Apply For" always exposes the current array item as a fixed
macro named `$config$` — you don't get to choose that name, it's the same
regardless of which field you loop over. That means one rule, looping over
`host.vars.http_urls`, already covers both "a service per configured path"
and "just root if nothing was configured", because the import source always
puts at least `"/"` in that array — there is no empty-array case left to
special-case in the apply rule itself.

1. **Add a String data field** (Director > Data Fields) called e.g.
   `http_url`, and associate it with the Service Template you'll use below
   (this is the field that will receive `$config$` on each iteration — see
   the field's own "Fields" tab).

   You can skip this and instead set `vars.http_uri` directly to `$config$`
   on the apply rule's "Custom properties" without a dedicated Data Field —
   the Data Field only matters if you want the field to show up as a
   validated form input elsewhere. Either way the resulting `vars.http_uri`
   value is the same.

2. **Create (or reuse) a Service Template**, e.g. `traefik-http-check`:
   - `check_command`: `http`
   - Fields tab: associate `http_url` (if you created it in step 1).

3. **Create the Service Apply Rule** (Director > Service Apply Rules > Add):
   - **Apply to**: `Host`
   - **Service name**: something that includes `$config$`, e.g.
     `http $config$` — every service generated for a given host must have a
     unique name, and since `$config$` is the only thing that varies per
     iteration (a path like `/` or `/api`), it has to be part of the name.
   - **Apply for**: pick `http_urls` (it's listed as `host.vars.http_urls`
     once at least one host has a non-empty value — if the dropdown is
     empty, run the import/sync once first so Director can see a populated
     array to offer).
   - **Assign filter**: `host.vars.http_urls` (true for any host where the
     import source set it — i.e. every Traefik-imported host; false/absent
     for everything else, so this rule won't touch unrelated hosts).
   - **Fields** tab: set `http_url` = `$config$` (or, if you skipped the
     dedicated field, add the custom property `http_uri` = `$config$`
     directly). Also set `http_vhost` = `$host.name$` so the check sends the
     correct `Host` header for Traefik to route on (`host.address` is only
     used for the TCP connection target).

4. **Verify** via the Sync Rule's or a single host's "Preview" / config
   preview in Director before deploying — it should render to
   (approximately) the following raw Icinga2 config, which you can use as a
   sanity check regardless of the exact UI field wording above:

   ```
   apply Service "http " + config for (config in host.vars.http_urls) {
     import "traefik-http-check"
     vars.http_uri = config
     vars.http_vhost = host.name
     assign where host.vars.http_urls
   }
   ```

   A host with `http_urls = ["/"]` gets exactly one service (`http /`); a
   host with `http_urls = ["/", "/api", "/health"]` gets three (`http /`,
   `http /api`, `http /health`).

## Excluding specific paths

For a fixed, global policy (e.g. "never check `/metrics` on any host from
this Traefik instance"), set **"Exclude paths"** on the import source itself
(comma-separated, exact match), so the path never shows up in `http_urls` in
the first place — nothing to configure on the apply rule side, and it
applies consistently across every Sync run.

If you instead want to exclude a path only for a particular apply rule (e.g.
two different Service Apply Rules over the same `http_urls`, each caring
about a different subset), do it there instead: Icinga2 supports `ignore
where <loop var> == ...` on `for`-based apply rules, but Director's Service
Apply Rule UI has no separate "Ignore" field for apply rules — that slot is
reserved for Director's own blacklist/override-template mechanism. The
workaround is to fold the exclusion into the single **Assign filter** as a
negated `AND` condition, referencing `$config$` directly:

```
assign where host.vars.http_urls && !(config in ["/metrics", "/health"])
```

In the Director filter editor: add an `AND` condition (`+` icon), type
`config in ["/metrics", "/health"]` as its expression, then click the
fast-forward/negate icon next to it to wrap it in `!(...)`.
