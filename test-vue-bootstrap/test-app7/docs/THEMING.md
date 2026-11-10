# Theme Tokens & UI Customization

**Status:** Frontend integration Phase 7 implementation.
**Command:** `ui:theme`.
**Implementation:** `Sahil\Theme\*` (`src/Theme/`).
**Related:** [`UI_ASSETS.md`](UI_ASSETS.md), [`VIEW_ASSETS.md`](VIEW_ASSETS.md).

Phase 7 adds an **optional** theme layer on top of the installed frontend
profile. The neutral theme document is the source of truth; framework files
are generated from it at build time. The framework runtime does not depend on
the generator, Sass, Tailwind, or Bootstrap.

The theme layer is opt-in:

- `ui:install` keeps generating a complete, unthemed profile.
- `ui:theme` generates the theme files and adds a replaceable import block to
  the profile's stylesheet entry.
- Generated files can be regenerated at any time; user token and override
  files are preserved.

---

## 1. Quick start

```bash
php sahil ui:install tailwind     # or bootstrap / both / none
php sahil ui:theme                # reads theme/tokens.json
php sahil ui:build
```

On the first run, if `theme/tokens.json` does not exist, `ui:theme` writes the
neutral sample theme. Edit it and run `ui:theme` again:

```bash
$EDITOR theme/tokens.json
php sahil ui:theme
php sahil ui:build
```

Use a different token file without creating `theme/tokens.json`:

```bash
php sahil ui:theme --source=theme/brand.json
```

The profile is resolved from `.sahil-ui.json` exactly like `ui:build`. An
explicit profile is also accepted:

```bash
php sahil ui:theme bootstrap
php sahil ui:theme --profile=both --source=theme/brand.json
```

Inspect the plan without writing:

```bash
php sahil ui:theme --dry-run
php sahil ui:theme --json
```

---

## 2. Neutral token format

`theme/tokens.json` is a JSON object with this shape:

```json
{
  "$schema": "https://sahilphp.dev/schemas/theme-tokens-v1.json",
  "version": 1,
  "name": "sahil-neutral",
  "description": "Optional description for humans.",
  "colors": {
    "primary": { "light": "#2563eb", "dark": "#60a5fa" },
    "primary-foreground": { "light": "#ffffff", "dark": "#0b1120" },
    "surface": { "light": "#ffffff", "dark": "#0f172a" },
    "text": { "light": "#0f172a", "dark": "#e2e8f0" }
  },
  "typography": {
    "font-sans": "ui-sans-serif, system-ui, sans-serif",
    "font-mono": "ui-monospace, monospace",
    "sizes": { "sm": "0.875rem", "base": "1rem", "lg": "1.125rem" },
    "line-heights": { "tight": "1.25", "normal": "1.5" },
    "weights": { "normal": "400", "semibold": "600", "bold": "700" }
  },
  "spacing": {
    "base": "0.25rem",
    "xs": "0.5rem",
    "md": "1rem",
    "lg": "1.5rem"
  },
  "borders": {
    "style": "solid",
    "widths": { "thin": "1px", "thick": "2px" }
  },
  "radii": { "sm": "0.375rem", "md": "0.5rem", "lg": "0.75rem" },
  "breakpoints": { "sm": "40rem", "md": "48rem", "lg": "64rem" }
}
```

### 2.1 Root fields

| Field | Required | Rules |
|---|---|---|
| `version` | no | Must be `1` when present; omitted means `1`. |
| `name` | no | Non-empty string; default `sahil-theme`. |
| `description` | no | String used only in generated comments. |
| `colors` | **yes** | Non-empty object. |
| `typography` | no | Object with only the documented keys. |
| `spacing` | no | Object of CSS lengths. |
| `borders` | no | `style` plus an object of width lengths. |
| `radii` | no | Object of CSS lengths. |
| `breakpoints` | no | Object of CSS lengths. |

### 2.2 Color tokens

A color value is either:

```json
"primary": "#2563eb"
```

or an object:

```json
"primary": { "light": "#2563eb", "dark": "#60a5fa" }
```

- A plain string is used for both light and dark.
- If an object omits `dark`, the light value is reused.
- Color names use lowercase letters, digits and hyphens, for example
  `primary`, `primary-foreground`, `success-surface`.
- Supported color syntaxes are hex, `rgb()`, `rgba()`, `hsl()`, `hsla()`,
  `hwb()`, `lab()`, `lch()`, `oklab()`, `oklch()`, `color()`,
  `color-mix()`, `light-dark()`, named colors, `currentColor` and
  `transparent`.

The sample defines the semantic tokens used by the generated example
layouts: `primary`, `primary-hover`, `primary-foreground`, `surface`,
`surface-muted`, `text`, `text-muted`, `border`, `success`,
`success-surface`, `warning`, `warning-surface`, `danger` and
`danger-surface`. Custom color names are allowed and are exposed to CSS and
Tailwind; Bootstrap only receives the documented subset.

### 2.3 Typography, spacing, borders, radii and breakpoints

| Token | Accepted values |
|---|---|
| `typography.font-sans`, `font-mono` | CSS font-family list |
| `typography.sizes.*` | CSS length or `clamp()`/`calc()`/`min()`/`max()` |
| `typography.line-heights.*` | `normal`, unitless number, or CSS length |
| `typography.weights.*` | `normal`, `bold`, `bolder`, `lighter`, or `100`–`900` |
| `spacing.*` | CSS length |
| `borders.style` | `solid`, `dashed`, `dotted`, `double`, `none` |
| `borders.widths.*` | CSS length |
| `radii.*` | CSS length or percentage |
| `breakpoints.*` | CSS length |

Names use the same lowercase/digit/hyphen rule as colors. Keep `spacing.base`
as the base unit Tailwind multiplies for its spacing utilities. Keep
`typography.sizes.base` and `radii.md` when Bootstrap mapping is desired.

### 2.4 Validation and safety

`ui:theme` validates the whole document **before writing any file**. It
rejects:

- invalid JSON, non-object roots, unsupported versions and unknown keys;
- unknown keys inside `typography` or `borders`;
- malformed token names;
- empty values and CSS values containing `;`, `{`, `}`, newlines, `<`, `>`,
  `/*`, `*/`, `url(` or `expression(`;
- colors that do not use a supported CSS color syntax;
- lengths that are not a CSS length;
- font weights outside the accepted set;
- invalid border styles.

The goal is safe generated CSS, not a general-purpose CSS parser. A valid
value still needs to be meaningful in both frameworks; the mapping rules below
explain where a token is only partially represented.

---

## 3. Generated files

Running `ui:theme` writes generated files per profile:

| Profile | Generated files | User-owned files |
|---|---|---|
| `tailwind` | `resources/css/theme.tokens.generated.css`, `resources/css/theme.tailwind.generated.css` | `theme/tokens.json`, `resources/css/theme.overrides.css` |
| `bootstrap` | `resources/css/theme.tokens.generated.css`, `resources/css/theme.bootstrap.generated.css`, `resources/scss/_sahil-theme.generated.scss` | `theme/tokens.json`, `resources/css/theme.overrides.css`, `resources/scss/_sahil-theme.overrides.scss` |
| `both` | all of the Tailwind and Bootstrap generated files | all user-owned files |
| `none` | `resources/css/theme.tokens.generated.css` | `theme/tokens.json`, `resources/css/theme.overrides.css` |

Generated files start with a comment saying they are managed by `ui:theme`.
They are replaced on every run. User-owned files are created once and then
preserved. Commit `theme/tokens.json` and the override files. Generated files
may be committed for reproducible builds or regenerated in CI before
`ui:build`; do not hand-edit them.

`ui:theme` edits these entry stylesheets inside a marker block:

```css
/* @sahil-theme:start */
@import "./theme.tokens.generated.css";
@import "./theme.tailwind.generated.css";
@import "./theme.overrides.css";
/* @sahil-theme:end */
```

- Tailwind: `resources/css/tailwind.css`, inserted after
  `@import "tailwindcss";`.
- Bootstrap: `resources/css/bootstrap.css`, inserted after the Bootstrap
  import.
- Plain CSS: `resources/css/app.css`, inserted at the top.

Every file outside the marker block is left untouched. Re-running the command
replaces only the block, so application CSS and custom imports survive. The
Bootstrap Sass override file is not automatically imported; see section 6.

The base generated stylesheets also keep fallback values, so an installed
profile still works before `ui:theme` is run.

---

## 4. Light and dark themes

The neutral token layer emits both themes without forcing a visual design:

```css
:root {
  color-scheme: light;
  --sahil-color-primary: #2563eb;
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]):not([data-bs-theme="light"]) {
    color-scheme: dark;
    --sahil-color-primary: #60a5fa;
  }
}

[data-theme="dark"],
[data-bs-theme="dark"] {
  color-scheme: dark;
  --sahil-color-primary: #60a5fa;
}
```

- With no attribute, the operating-system preference controls the theme.
- `data-theme="light"` or `data-bs-theme="light"` disables automatic dark
  mode.
- `data-theme="dark"` forces dark mode for the neutral variables.
- `data-bs-theme="dark"` forces dark mode for both the neutral variables and
  Bootstrap's own dark-mode variables.

For a Bootstrap-only application, prefer `data-bs-theme` so Bootstrap's
unmapped component variables switch too. Automatic
`prefers-color-scheme: dark` changes the neutral variables and the bridged
Bootstrap variables, but Bootstrap only activates its complete dark component
palette when `data-bs-theme="dark"` is present. A small theme toggle should set
that attribute (and `data-theme` when the same page also uses Tailwind).
For a Tailwind or plain application, `data-theme` is enough.

The generated CSS never assumes a particular brand. Replace the sample color
values in `theme/tokens.json` with your own.

---

## 5. Tailwind CSS v4 mapping

The Tailwind file contains one `@theme inline` block. `inline` is deliberate:
utilities resolve the neutral CSS variables at runtime, so dark-mode changes
and overrides work without rebuilding.

| Neutral output | Tailwind variable | Example utility |
|---|---|---|
| `--sahil-color-primary` | `--color-primary` | `bg-primary`, `text-primary` |
| `--sahil-font-sans` | `--font-sans` | `font-sans` |
| `--sahil-text-base` | `--text-base` | `text-base` |
| `--sahil-leading-normal` | `--leading-normal` | `leading-normal` |
| `--sahil-weight-semibold` | `--font-weight-semibold` | `font-semibold` |
| `--sahil-space-base` | `--spacing` | `p-4` multiplies the base |
| `--sahil-space-md` | `--spacing-md` | custom spacing token; see limitation below |
| `--sahil-radius-md` | `--radius-md` | `rounded-md` |
| `--sahil-breakpoint-md` | `--breakpoint-md` | `md:` variants |

Breakpoint values are emitted as literals, not `var(...)`, because CSS media
queries do not accept custom properties. Border style has no stock Tailwind
theme namespace; border widths are exposed as neutral CSS variables and a
`--border-width-*` custom namespace. Stock Tailwind border utilities use
their own width scale, so named border widths and styles are mainly for
custom CSS/components.

---

## 6. Bootstrap 5 mapping

Bootstrap 5.3 is primarily a Sass-compiled framework. There are two generated
Bootstrap outputs because the two mechanisms solve different problems.

### 6.1 CSS-variable bridge (used by the default precompiled profile)

`resources/css/theme.bootstrap.generated.css` maps only variables that can be
safely derived at runtime:

| Bootstrap variable | Neutral source |
|---|---|
| `--bs-body-bg` | `surface` |
| `--bs-body-color` | `text` |
| `--bs-secondary-color` | `text-muted` |
| `--bs-border-color` | `border` |
| `--bs-primary` | `primary` |
| `--bs-link-color` | `primary` |
| `--bs-link-hover-color` | `primary-hover` |
| `--bs-border-width` | `borders.widths.thin` |
| `--bs-border-radius` | `radii.md` |
| `--bs-border-radius-sm` / `-lg` | `radii.sm` / `radii.lg` |
| `--bs-body-font-family` | `typography.font-sans` |
| `--bs-body-font-size` | `typography.sizes.base` |
| `--bs-body-line-height` | `typography.line-heights.normal` (or another unitless token) |

The bridge works with the precompiled Bootstrap CSS and does not require
Sass. It intentionally does **not** invent `--bs-primary-rgb` or contrast
variables: Bootstrap derives those from Sass functions, and a CSS color such
as `oklch()` cannot be converted to RGB channels with a CSS custom property.
Components that use those derived variables keep Bootstrap's default values
unless you opt into the Sass mapping.

### 6.2 Optional Sass mapping

`resources/scss/_sahil-theme.generated.scss` sets supported Bootstrap Sass
variables:

```scss
$primary: #2563eb;
$body-bg: #ffffff;
$body-color: #0f172a;
$border-radius: 0.5rem;
$font-family-sans-serif: ui-sans-serif, system-ui, sans-serif;
$grid-breakpoints: (
  xs: 0,
  sm: 40rem,
  md: 48rem,
  lg: 64rem,
  xl: 80rem,
  xxl: 1400px,
);
$enable-dark-mode: true;
```

Bootstrap derives its `$theme-colors` map from `$primary`, `$success`,
`$warning` and `$danger`, so the generator does not replace the whole map and
does not remove `secondary`, `info`, `light` or `dark`. Standard Bootstrap
breakpoints omitted from the token document keep their Bootstrap defaults
(for example `xxl: 1400px` when only `sm`–`xl` are themed).

This file is **not** imported by the default Bootstrap profile. To use it,
compile Bootstrap from Sass instead of the precompiled distribution:

1. Install Sass: `npm install -D sass`.
2. Create `resources/scss/bootstrap.scss` (path is your choice):

   ```scss
   @import "sahil-theme.generated";
   @import "sahil-theme.overrides";
   @import "bootstrap/scss/bootstrap";
   ```

3. Point the Bootstrap entry at it. For example, change
   `resources/js/bootstrap.js`:

   ```js
   import '../scss/bootstrap.scss';
   import * as bootstrap from 'bootstrap';
   ```

   Or add the SCSS file as the Vite input and remove the CSS import from the
   JavaScript entry.

4. Run `php sahil ui:build`.

Import order matters: Sass variables are resolved with `!default`, so the
theme file must come before `bootstrap/scss/bootstrap`. The generated file
uses the **light** color values because Sass variables are compile-time. Use
hex, `rgb()` or `hsl()` colors for the Sass mapping; Bootstrap's color
functions do not understand every modern CSS color space. The precompiled
CSS-variable bridge can use any color syntax accepted by the token validator.
For dark mode, keep the CSS-variable bridge and `data-bs-theme`, or compile a
separate dark stylesheet if your application switches entire stylesheets.

The Sass file is optional and generated even for the default profile so it is
available when you choose to switch. It is not a runtime dependency.

### 6.3 Bootstrap limitations

- Sass variables and CSS variables are not interchangeable. The bridge covers
  runtime-safe values; the Sass file covers compile-time values.
- Bootstrap's core `$primary`, `$success`, `$warning`, `$danger` colors are
  mapped, but `$primary-text-emphasis`, `$primary-bg-subtle`, RGB channel
  variables and automatically calculated contrast colors are not all mapped.
  Use the Sass override file to set them explicitly when needed.
- Bootstrap has no equivalent of every Tailwind typography scale, spacing
  token or radius. Only the documented base tokens are mapped.
- Bootstrap's grid breakpoints are compile-time Sass map values. The neutral
  breakpoint variables are available to custom CSS but cannot replace
  Bootstrap's media queries at runtime. Standard breakpoints not present in
  the token file fall back to Bootstrap's defaults.

---

## 7. Overriding generated CSS

There are two supported override paths.

### 7.1 Change the source tokens

Edit `theme/tokens.json` and run:

```bash
php sahil ui:theme
```

This regenerates every framework file from the shared tokens. It is the
recommended way to keep Tailwind and Bootstrap consistent.

### 7.2 Use the override file

`resources/css/theme.overrides.css` is imported after the generated files and
is never replaced:

```css
:root {
  --sahil-color-primary: #0f766e;
  --sahil-radius-md: 0.625rem;
}

[data-theme="dark"] {
  --sahil-color-primary: #2dd4bf;
}
```

Because Tailwind's `@theme inline` utilities and Bootstrap's bridge both
reference the neutral variables, these overrides affect both generated
frameworks. They also work with the plain profile.

For Bootstrap Sass, use `resources/scss/_sahil-theme.overrides.scss` after the
generated Sass file.

Regression rule: generated files are disposable; `theme/tokens.json` and the
override files are yours. Do not put long-lived application CSS in a
`.generated` file.

---

## 8. Accessibility and contrast guidance

The generator cannot know your final font sizes, backgrounds, component
states or brand requirements, so contrast remains an application
responsibility. Use the tokens deliberately and test both themes.

- Aim for WCAG 2.2 AA: at least **4.5:1** for normal text and **3:1** for
  large text (18pt/24px regular or 14pt/18.66px bold), icons and meaningful UI
  boundaries.
- Check `text` against `surface` and `surface-muted`, and
  `primary-foreground` against `primary`, in both light and dark values.
- Check alert text against its surface token (`success` on `success-surface`,
  etc.), not just against the page background.
- Keep a visible focus indicator on every interactive control. The plain
  sample uses a two-pixel outline derived from `primary`; preserve an
  equivalent in custom components.
- Do not communicate validation or status by color alone. Pair color with
  text, an icon, and `aria-invalid`/`role="alert"` where appropriate.
- Test with forced-colors mode and at 200% zoom. CSS color tokens should not
  be the only source of boundaries or state.
- The generated sample themes are examples, not certified accessible
  palettes. Run an automated contrast check and a keyboard test before
  shipping.

The `ui-kit` example pages include forms, validation errors, alerts, tables,
navigation and responsive grids for each profile; use them as structural
starting points, not as proof that a particular palette passes contrast.

---

## 9. Mapping rules and limitations

| Concern | Tailwind v4 | Bootstrap 5 | Rule |
|---|---|---|---|
| Colors | `--color-*` theme namespace | Sass colors plus runtime CSS variables | All colors map to neutral CSS variables and Tailwind; only the documented subset maps to Bootstrap runtime/Sass. |
| Typography scale | `--text-*`, `--leading-*`, `--font-weight-*` | Base font family/size/line height | Arbitrary size and weight scales are not all Bootstrap variables. |
| Spacing | `--spacing` base plus named custom properties | `$spacer` | The named Tailwind spacing tokens are not guaranteed to create stock utilities. |
| Borders | Neutral variables plus `--border-width-*` namespace | `$border-width`, `$border-color`, `$border-radius-*` | Tailwind's border utilities use their own width scale; Bootstrap's radius tokens are mapped by size. |
| Radii | `--radius-*` | `$border-radius*` | `md` is the Bootstrap base radius; `sm`/`lg` map when present. |
| Breakpoints | `--breakpoint-*` with literal values | `$grid-breakpoints` Sass map | Media queries cannot read CSS custom properties. Missing standard Bootstrap breakpoints keep Bootstrap defaults. Changing breakpoints requires regeneration and a rebuild. |
| Dark mode | `data-theme`, media query | `data-bs-theme` plus bridge | Tailwind utilities use runtime variables; Bootstrap Sass is compiled light-first. |
| `both` profile | Tailwind output | Bootstrap output | Both consume the same neutral tokens, but each framework's component model remains different. The tokens do not make the frameworks visually identical. |

There is no attempt to emulate Tailwind utilities in Bootstrap or Bootstrap
components in Tailwind. The shared layer is the token vocabulary and the
generated CSS variables, not a cross-framework component runtime.

---

## 10. Command reference

```text
php sahil ui:theme [profile] [options]
```

| Option | Meaning |
|---|---|
| `--profile=NAME` or positional profile | `tailwind`, `bootstrap`, `both`, `none` |
| `--path=DIR` | project root to operate on |
| `--source=FILE` | token file relative to the project (default `theme/tokens.json`) |
| `--dry-run` | print the plan; write nothing |
| `--json` | machine-readable report |

Exit status is `0` on success and `1` for validation, missing-entry or
filesystem errors. Validation happens before any write. If an entry stylesheet
is missing, run `ui:install` for that profile first.

The command does not install npm packages, start Vite or run a build. Use
`ui:build` afterwards.

---

## 11. Tests

- `tests/Theme/ThemeTokensTest.php` — token validation, JSON errors, color and
  length rules, light/dark normalization.
- `tests/Theme/ThemeGeneratorTest.php` — per-profile output, Tailwind mapping,
  Bootstrap bridge and Sass mapping, entry ordering and determinism.
- `tests/Theme/ThemeInstallerTest.php` — created files, marker replacement,
  user-file preservation, dry runs, custom sources and failure-before-write.
- `tests/Console/UiThemeCommandTest.php` — end-to-end CLI output and JSON.
- `tests/Console/UiBuildIntegrationTest.php` — real Vite builds with a
  generated theme for every supported profile (skipped when `node_modules` is
  unavailable).

PHP-only validation:

```bash
vendor/bin/phpunit tests/Theme tests/Console/UiThemeCommandTest.php
vendor/bin/phpstan analyse src/Theme src/Ui/UiConsole.php src/Ui/ProfilePlan.php
```
