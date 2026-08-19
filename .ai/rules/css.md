---
paths:
  - resources/css/app.css
---

# Css

## Two hues, resolved through --ui-* tokens
Colours are never written inline. `:root` / `.dark` define `--ui-*`, `@theme` maps them to `--color-*`, so one `dark` class flips the whole app. Blue (`accent`) marks what is active, primary or carrying data; terracotta (`warm`) marks a movement the user earned — a trend, a personal record; red is destructive only. Text runs `ink` → `ink-soft` → `ink-muted` → `ink-faint`; borders run `line` (card edges) → `line-soft` (separators inside a card). Bar charts use the `bar`/`bar-fade` and `accent`/`accent-fade` gradient pairs.
