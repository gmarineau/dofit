---
paths:
  - 'resources/views/**'
---

# Views

## Use heroicons generously in the UI
Any affordance, badge, stat tile or section heading gets a heroicon (blade-ui-kit/blade-heroicons). The user asked for icons everywhere rather than bare text.
`x-section-heading` and `x-stat` take an `icon` prop holding the name without the `heroicon-` prefix, e.g. `icon="o-scale"`. Reuse the icon the bottom nav already uses for the same concept (trainings = o-bolt, programs = o-rectangle-stack, metrics = o-scale) so the same thing always reads the same.

## One container, edge to edge
Every page fills the same 73.75rem container, top bar included, so the wordmark and every page line up on the same two edges. Do not narrow a page or its card — a card capped short of the container and hung off the left rail was tried and rejected as lopsided on desktop.
`layouts::app` draws one full-width card around the slot by default. A page that brings its own cards opts out from the component: `return $this->view()->title(...)->layout('layouts::app', ['card' => false]);`. Only the dashboard does today.
