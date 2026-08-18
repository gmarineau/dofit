---
paths:
  - 'resources/views/**'
---

# Views

## Use heroicons generously in the UI
Any affordance, badge, stat tile or section heading gets a heroicon (blade-ui-kit/blade-heroicons). The user asked for icons everywhere rather than bare text.
`x-section-heading` and `x-stat` take an `icon` prop holding the name without the `heroicon-` prefix, e.g. `icon="o-scale"`. Reuse the icon the bottom nav already uses for the same concept (trainings = o-bolt, programs = o-rectangle-stack, metrics = o-scale) so the same thing always reads the same.
