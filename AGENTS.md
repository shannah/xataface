# Codebase Style Guide

This document summarizes the non-obvious, codebase-specific conventions and patterns observed in the provided examples. It focuses on the distinctive or less common choices this codebase uses (not general best practices).

---

## JavaScript

- Wrap modules in an IIFE and initialize inside jQuery ready:
  - Pattern: 
    (function(){ jQuery(document).ready(function($){ /* module body */ }); })();
  - Purpose: isolates scope and injects jQuery as `$` inside the ready callback.

- Use jQuery as the primary DOM API (selectors, traversal, manipulation, events).
  - Cache selections into local variables (e.g. `var resultList = $('table#result_list');`).
  - Use chainable jQuery construction for DOM elements: `var tr = $('<tr>');`, ` $('<th>').attr('colspan',n).text(label)`.

- Access raw DOM nodes when needed via `.get(0)` or `.get()`:
  - Common when integrating with non-jQuery APIs (e.g. `iframe.contentWindow`).
  - Example: `var iframe = $('<iframe>').attr(...).on('load', fn).attr('src', url).get(0);`

- Prefer `.on('event', handler)` for programmatic elements and `.click(fn)` shorthand for simpler cases.
  - Both are used; `.on('load', ...)` is used on dynamically created iframes.

- Manipulating iframes:
  - Create iframe with jQuery and then use `.get(0)` to obtain the DOM window/frame to access `contentWindow`.
  - Example pattern to wire callbacks into the iframe controller:
    var iframe = $('<iframe>').attr(...).on('load', function(){ 
      var showHideController = iframe.contentWindow.xataface.controllers.ShowHideColumnsController;
      showHideController.saveCallbacks.push(function(data){ ... });
    }).get(0);

- Data in HTML attributes:
  - Use `data-` attributes but read them via `.attr('data-...')` (not `.data()`).
  - Some attributes contain serialized JS (legacy): `data-xataface-query` may hold a JS object literal string — the code uses `eval('window.xataface.query = '+queryJson+';')` to restore it.
    - Note: this codebase intentionally uses `eval` for legacy formatted data-attributes. Consider `JSON.parse` only if the attribute is guaranteed to be valid JSON.

- Global "namespace" pattern:
  - Create a global `window.xataface` object if undefined and attach sub-objects: `if (typeof(window.xataface) == 'undefined') window.xataface = {}; window.xataface.query = {};`
  - Read/write to `window.xataface` for cross-frame/controller state.

- Event handling quirks:
  - Stop bubbling where necessary, for example preventing inner link clicks from triggering header click handlers:
    `$('a', th).click(function(e){ e.stopPropagation(); });`

- DOM layout animation/resizing:
  - Measure container dimensions and animate input width via `$('input', this).animate({width: width-2}, 500);`
  - Use `.css({...})` with an object to set multiple CSS properties.

- Small but consistent practices:
  - Use `clone()` to duplicate DOM nodes for reuse into other table sections (thead → tfoot).
  - Use `each` loops with `function(){ var self = this; ... }` when DOM node reference is required inside deeper scopes.

---

## PHP

- Class naming:
  - Controller/action classes are lowercase with underscores, e.g. `class dataface_actions_copy_replace { ... }`.
  - Internal method names use camelCase (e.g. `getFieldsForRecord`, `getKeysForRecord`, `getTableForm`).

- Legacy/compatibility constructs:
  - Frequent use of references and old-style assignments (`=&`) and returning by reference (`function &getTableForm(...)`) — keep these patterns when interacting with legacy APIs.
  - `@` operator is used to suppress warnings in many places (e.g. `@$_POST['-copy_replace:submit']`).

- Translation function:
  - Calls use `df_translate(key, defaultString)` throughout. Always provide the key and a fallback default string.

- Form names & naming conventions:
  - Long, namespaced input names to avoid collisions. Example QuickForm naming pattern:
    - Form name: `'copy_replace_form'`
    - Element names: `-copy_replace_form:replace[FIELDNAME]`
    - Hidden control names: `-copy_replace:fields`, `-copy_replace:copy`, `-copy_replace:submit`
  - Use these prefixed names consistently to group action-specific form fields.

- QuickForm & renderer usage:
  - Build fields via a per-table QuickForm builder (`getTableForm`) and attach rendered templates with `setElementTemplate`.
  - Use `$form->setDefaults()` and `$form->addElement('hidden', ...)` to propagate upstream query parameters into the form.

- Data access & permissions:
  - Use `df_get_selected_records($query)` and fallback to fetching whole result sets with `-limit` adjustments when no selection.
  - Check permissions per-record (`$record->checkPermission('edit')`, `'copy'`) and per-field (`$record->checkPermission('edit', array('field'=>$key))`) and collect warnings.

- Error handling:
  - Use PEAR-style checking: `if (PEAR::isError($res)) { ... }`.
  - Use `Dataface_Error::permissionDenied()` to create permission errors.

- Legacy array syntax:
  - Use `array()` for arrays (not short `[]` syntax).

- Hints for maintainers:
  - When adding new features, prefer to follow existing reference-return and QuickForm integration patterns for compatibility with other Dataface components.

---

## CSS

- Icon font & ligatures:
  - Use @font-face to load Material Icons with multiple fallbacks and `local()` lookups.
  - Use a `.material-icons` class and enable `font-feature-settings: 'liga';` to allow ligature-based icons.

- Vendor fallbacks & progressive enhancement:
  - Include both vendor-prefixed and standard properties for compatibility:
    - border-radius: `-moz-`, `-webkit-` and standard `border-radius`.
    - box-sizing: `-webkit-box-sizing`, `-moz-box-sizing`, and `box-sizing`.
    - box-shadow and opacity fallbacks: `.yui-ac-shadow` uses both `-moz-opacity`/`opacity` and `filter:alpha(opacity=10)`.

- Advanced selectors and features used:
  - Attribute selectors: `input:not([type="submit"])` for styling input types excluding submit.
  - Child selectors `>` and combinators to scope styles tightly (e.g. `.mobile-list-settings-wrapper > div`).
  - Pseudo-element content used as small UI bits: `:before` / `:after` with `content`.
  - Media query for prints: `@media print { ... }`.
  - Flexbox for compact mobile controls: `display:flex` on `.mobile-list-settings-wrapper > div`.

- CSS utility and behavioral classes:
  - `.stop-scrolling` toggles overflow to freeze body (used during modal dialog).
  - `.hiddenStructure` is an accessibility/visually-hidden utility class that preserves content for assistive tech.
  - `.expanded` / `.collapsed` toggle display states for text reveal controls.

- Layout and theming patterns:
  - Mixed use of background images plus colors for button and header visuals (legacy support).
  - Many list and menu patterns implemented via absolute-positioned dropdown containers (e.g. `.xf-dropdown ul`).

---

## HTML / Data Conventions

- Data attributes:
  - Use HTML attributes prefixed with `data-` for carrying meta info (e.g. `data-xataface-query`, `data-search-column`, `data-column`).
  - Reading is done with jQuery `.attr('data-...')` rather than `.data()` to preserve raw string content (important when the value is a JS literal string).

- Form field naming:
  - Namespaced names (example `-copy_replace_form:replace[FIELDNAME]`) to isolate action-specific values and facilitate server-side parsing.

- Table structure:
  - Tables for result lists include `thead`, `tbody`, `tfoot` and template rows (class `template`) in `tfoot` that are removed / processed by JS.

---

## Inter-frame & Controller Integration

- Cross-frame communication:
  - Controllers expose arrays of callbacks on the iframe window object (e.g. `iframe.contentWindow.xataface.controllers.ShowHideColumnsController.saveCallbacks.push(fn)`).
  - The parent page registers callbacks by accessing `contentWindow` after iframe load.

- Dialog interaction:
  - jQuery UI Dialog is used to host iframes; the code attaches to dialog `create`, `beforeClose`, and `buttons` to coordinate actions (e.g. clicking a Save button in parent triggers a button inside iframe).

---

## Legacy / Compatibility Notes (do not change lightly)

- The codebase intentionally retains several legacy constructs for compatibility:
  - `eval()` used to parse legacy JS literal strings stored in attributes.
  - `&` reference semantics and `=&` assignments in PHP.
  - PEAR style error objects and Dataface helper functions.
  - Old vendor-prefixed CSS alongside modern properties.

When updating or refactoring, keep backward compatibility with older browsers/framework components in mind unless a coordinated upgrade is planned.

---

If you want, I can:
- Produce quick linting rules or ESLint/PHP-CS-Fixer configuration snippets to begin enforcing these conventions.
- Extract the most important "do not change" items into a short checklist for reviewers.