# Runtime storage

Everything in this directory is runtime data: create it, read it, delete it,
but never commit it (see `.gitignore`).

- `logs/` — structured application logs (`app.log`), written by the logger;
- `framework/views/` — compiled templates, written by the view compiler and
  safe to delete at any time (the next render regenerates them);
- `framework/sessions/` — file session payloads;
- `uploads/private/` — the default **private** storage disk. Outside the
  public document root by design, mode `0700`/`0600`, and never served by a
  web server. `StorageContract::url()` returns `null` for this disk;
- `cache/` — reserved for later phases.

Public files do **not** live here: the `public` disk writes to
`public/storage/`, which a web server may serve as static content only (see
`public/storage/.htaccess`).

Deleting either upload root is safe from the framework's point of view — the
directory is recreated on first use — but it deletes every stored file, so
prefer `StorageContract::delete()` / `deleteDirectory()` for selective
cleanup. See "File storage & uploads" in the README.
