# Writing these docs

These docs are **usage documentation** — they teach a developer *how to do
something*, not how the code is implemented. Follow these rules so the docs stay
useful and don't rot every time the code changes.

## Principles

1. **Usage over internals.** Document what a reader *does* and *sees*, not how
   it's built. Do **not** include: class names / FQCNs, base-class inheritance,
   constructor signatures, method signatures, return types, DB schemas/columns,
   internal file paths, or "what it renders" markup walkthroughs. If a fact would
   change when someone refactors the code without changing behaviour, leave it out.
2. **Show, briefly.** Lead with a short, copy-pasteable usage example. Keep
   snippets to the smallest thing that works.
3. **Describe options by purpose, not by type.** Say what an option *does for the
   reader*, not its PHP type/default/signature.
4. **No tables for props/options.** Use a clean bold-term list:
   - **`option`** — what it does.
   Reserve tables only for genuine matrices where a list reads worse.
5. **Link, don't repeat.** Each shared concept has ONE canonical page. Link to it
   instead of re-explaining. See the canonical pages below.
6. **Correct on the surface.** Every tag, attribute, helper, command, config key,
   and route you show must really exist — usage examples must run.

## Canonical pages (link here instead of repeating)

- **How components work** (tag syntax, shared form-field attributes like `name` /
  `title` / `value` / `validation` / `hint`, attribute pass-through, which inputs
  wire up JS) → [Components overview](/components/overview).
- **Asset & init system** (`init=`, `hashed_asset`, the build step, vendor
  stacks) → [Asset & Init System](/frontend/asset-system).
- **JS translations** (`__()`) → [JS Translations](/frontend/translations).
- **Settings / the `setting()` helper** → [Settings](/foundation/settings).
- **Client-side validation** (the `validation` attribute) →
  [RedotValidator](/frontend/plugins/redot-validator).

## Page templates

**Component**

```
# Name
One line: what it's for / when to reach for it.

## Usage
<minimal Blade snippet with the common attributes>

## Options
- **`option`** — what it does.

## Examples
### <task-titled example>
<snippet>

## Related
- links (always include the relevant canonical page)
```

**Helper / model / trait / cast / rule / package sub-topic** — same shape:
one-line intro → `## Usage` → `## Options` (bold-term list) → `## Examples` →
`## Related`. Group large helper sets by task.

**Package overview**

```
# Package
What you can build with it (outcomes, not architecture).

## Quick start
<smallest working example>

## Common tasks
- links to the task pages

## Related
```

## Before / after

❌ *Before (reference / coupled to code):*
> The component is backed by `App\View\Components\Input` (extends
> `App\View\Components\Component`) and the view `resources/components/input.blade.php`.
> `isPassword` is set to `true` when `type` is `password`… Constructor parameters:
> a `| Prop | Type | Default |` table.

✅ *After (usage):*
> `<x-input>` is the standard text field. Set `type="password"` to get a built-in
> show/hide toggle.
> ## Options
> - **`title`** — label shown above the field.
> - **`floating`** — float the label inside the field instead of above it.
