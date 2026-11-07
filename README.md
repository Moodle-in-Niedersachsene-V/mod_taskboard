# Task board (mod_taskboard)

A Kanban board activity for Moodle 5.1 to 5.3. Tasks are cards in columns; participants move
them with mouse, finger, pen or keyboard. Built by Moodle in Niedersachsen e. V. for schools,
and designed to work both inside a course and embedded in other Moodle sites via LTI 1.3.

## Features

- **Easy to move:** cards are dragged with pointer events, so mouse, touch and pen behave the same.
  A dashed placeholder shows where the card lands, the board scrolls at the edges, and every move
  can be undone. Keyboard: focus a card, Space picks it up, arrow keys move it, Space drops it,
  Escape cancels. Moves are announced to screen readers.
- **Same card size:** teachers choose a fixed card size (S, M, L) or automatic. With a fixed size
  all cards have the same height; long titles are shortened and images cropped. Participants may
  switch the size for themselves if the teacher allows it.
- **Images on cards:** one cover image per card, uploaded, chosen from any repository (for example
  the image pool `repository_bildpool`, if installed) or pasted from the clipboard. Images are
  re-encoded on save, which removes EXIF data such as location, and scaled to at most 1600 px.
  An image description (alternative text) can be required.
- **Board types:** one board for the course, one per group, or one personal board per participant.
  Every new board starts as a copy of the template board, so teachers can prepare cards.
- **Participant permissions:** only move cards, add and edit own cards, or edit all cards and
  columns. Optional approval of new cards by a teacher.
- **Cards:** title, description, labels, due date, assigned participants, checklist, image,
  PeerTube video (from servers the administrator allows).
- **Work-in-progress limit** per column with a warning when exceeded.
- **Completion and grading:** complete when a number of the participant's cards lie in done
  columns. With point grading, the grade is the share of that number reached; Moodle sends it
  back to the platform when the activity is used via LTI.
- **Live updates:** open boards check for changes of others at a configurable interval.
- Backup and restore (the template board always, other boards with user data), privacy API,
  events for every change.

## Requirements

- Moodle 5.1 or later (tested for 5.1, 5.2 and 5.3)
- PHP 8.2, 8.3 or 8.4 with the GD extension
- MariaDB, MySQL or PostgreSQL

## Installation

1. Copy the folder `taskboard` to `mod/taskboard` (Moodle 5.1+: `public/mod/taskboard`), or install
   the ZIP under *Site administration > Plugins > Install plugins*.
2. Visit *Site administration > Notifications* to finish the installation.
3. Optional settings under *Site administration > Plugins > Activity modules > Task board*:
   default card size, update interval, maximum image size, allowed PeerTube servers.

## Capabilities

| Capability | Default roles | Purpose |
|---|---|---|
| `mod/taskboard:addinstance` | editing teacher, manager | Add the activity |
| `mod/taskboard:view` | student, teacher, editing teacher, manager | Open boards |
| `mod/taskboard:contribute` | student, teacher, editing teacher, manager | Move and add cards, as the activity's participant permissions allow |
| `mod/taskboard:manage` | teacher, editing teacher, manager | Edit the template board, see all boards, approve cards |

## Privacy

Personal boards are deleted with their owner's data. Cards on shared boards are group work: when
their author's data is deleted, the card stays and loses its author; assignments of the user are
removed.

## Development

- JavaScript sources are in `amd/src`; build them with `grunt amd`.
- Tests: `vendor/bin/phpunit --testsuite mod_taskboard_testsuite`.

## License

GNU GPL v3 or later. Copyright 2026 Moodle in Niedersachsen e. V.
