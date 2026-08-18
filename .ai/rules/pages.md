---
paths:
  - 'resources/views/pages/**'
---

# Pages

## Where the "add" action lives in a list
Short lists read in flow order (activities in a session, sets in an activity, programs) put adding at the END of the list with `<x-add-row :href="..." :label="..." />` — no header button, no FAB, one affordance only.
Long reverse-chronological lists (trainings index, metrics index) keep the header button plus the mobile `x-fab`: their add row would sit past a year of history.
The page header carries at most one action, and it is the page's own next step (e.g. "Finish session"), never "add".

## Where the "add" action lives in a list
`<x-add-row :href="..." :label="..." />` is the only add affordance on a list page — the page header carries no "add" button, only the page's own next step (e.g. "Finish session").
It goes where the new item will appear: at the END of a list read in flow order (activities in a session, sets in an activity, programs), with `class="mt-4"`; at the TOP of a reverse-chronological one (trainings, metrics), with a bottom margin.
The mobile `x-fab` stays only on the lists that outgrow a screen (trainings, metrics), so adding does not mean scrolling back up through months of history.
