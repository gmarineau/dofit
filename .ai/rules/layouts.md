---
paths:
  - 'resources/views/layouts/**'
---

# Layouts

## @fonts is what actually loads the typeface
Declaring a family in `vite.config.js` via `bunny()` only writes `public/fonts-manifest*.json` — nothing reaches the page until `@fonts` sits in the layout `<head>`, above `@vite`. It was missing for a long time and the app silently rendered in the system fallback, so check the computed `font-family` in the browser (or `document.fonts.size`) rather than trusting the config. Both `layouts/app` and `layouts/guest` need it. Swapping the family means three files: `vite.config.js`, `--font-sans` in `app.css`, and `Chart.defaults.font.family` in `app.js`.
