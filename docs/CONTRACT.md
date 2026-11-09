# Installer ↔ starter-kit contract

`sahilphp/installer` creates a project by copying the starter-kit and then
running the starter kit's own setup command. The two packages communicate
through environment variables and the stub tree; the installer never copies
UI files itself.

## Environment variables

| Variable | Values | Meaning |
|---|---|---|
| `SAHILPHP_UI_KIT` | `firefiber`, `react`, `vue`, `svelte`, `none` | UI kit chosen for the project |
| `SAHILPHP_CSS` | `tailwind`, `bootstrap` | CSS framework chosen for the project |
| `SAHILPHP_INSTALLER` | `1` | Set when the process was launched by the installer |

The starter kit's `php sahil ui:setup` reads these variables before asking any
question. When both are set, no prompt is shown, so `sahilphp new` asks the
two questions exactly once.

Precedence inside `ui:setup`:

1. `--ui-kit` / `--css` flags
2. `SAHILPHP_UI_KIT` / `SAHILPHP_CSS`
3. interactive questions
4. defaults: `none` + `tailwind` (non-interactive only)

## Stub tree

The starter kit ships:

```
stubs/ui-kits/firefiber/
stubs/ui-kits/react/
stubs/ui-kits/vue/
stubs/ui-kits/svelte/
stubs/ui-kits/none/
stubs/css/tailwind/
stubs/css/bootstrap/
```

Each directory contains at least `package.json`, a Vite build config,
`resources/css/app.css`, `resources/js/app.js`, a layout view, and a welcome
view. `ui:setup` copies the chosen kit and CSS trees only, merges their
`package.json` dependencies, writes the final `vite.config.js`, and deletes
`stubs/`.

## Generated project requirements

- `package.json` contains only the chosen UI kit and CSS framework
  dependencies.
- `.sahil-ui.json` records `{"profile": "<css>", "ui_kit": "<kit>"}`.
- `stubs/` no longer exists after setup.
- `php sahil unda:auth` and `php sahil migrate` work in the created project.

## UI packages

`sahilphp/firefiber`, `sahilphp/react`, `sahilphp/vue` and
`sahilphp/svelte` are libraries (`"type": "library"`) and must never be used
as project skeletons. They are installed with `composer require` or chosen
through `sahilphp new`; `composer create-project` on them exits 1.
