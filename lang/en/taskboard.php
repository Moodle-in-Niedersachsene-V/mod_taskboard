<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English strings for mod_taskboard.
 *
 * @package    mod_taskboard
 * @category   string
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addcard'] = 'Add card';
$string['addcolumn'] = 'Add column';
$string['allowimages'] = 'Allow images on cards';
$string['allowimages_help'] = 'Participants can give each card one cover image: upload it, choose it from a repository (such as the image pool, if installed) or paste it from the clipboard. Images are re-encoded when saved, which removes location and camera data (EXIF), and scaled down to at most 1600 pixels.';
$string['allowusersize'] = 'Participants may change the card size';
$string['allowusersize_help'] = 'If enabled, each participant can switch the card size for themselves on the board. The setting above is the default.';
$string['allowvideos'] = 'Allow PeerTube videos on cards';
$string['allowvideos_help'] = 'Cards can show a PeerTube video. Only videos from the PeerTube servers the site administrator allowed can be embedded. The option is locked while no server is allowed.';
$string['approval'] = 'Approve new cards';
$string['approval_help'] = 'New cards by participants are first visible only to their author and the teachers. Everyone else sees them once a teacher approved them.';
$string['approvalnotice'] = 'New cards are shown to the others once a teacher has approved them.';
$string['approvecard'] = 'Approve';
$string['board_course'] = 'Course board';
$string['board_group'] = 'Board of {$a}';
$string['board_personal'] = 'Board of {$a}';
$string['board_template'] = 'Template board';
$string['boardsettings'] = 'Board';
$string['boardtype'] = 'Board type';
$string['boardtype_course'] = 'One board shared by the course';
$string['boardtype_group'] = 'One board per group';
$string['boardtype_help'] = 'With one board per group, every group of the course (or of the chosen grouping) gets its own board; teachers switch between them. With personal boards, each participant works on their own board. Every new board starts as a copy of the template board.';
$string['boardtype_personal'] = 'One personal board per participant';
$string['cannotmovecolumn'] = 'The column cannot be moved further in this direction.';
$string['cardassignees'] = 'Assigned to';
$string['cardchecks'] = 'Checklist';
$string['cardchecks_help'] = 'One checklist item per line. Items that keep their text keep their ticks.';
$string['carddescription'] = 'Description';
$string['cardduedate'] = 'Due date';
$string['cardimage'] = 'Image';
$string['cardimage_help'] = 'One image shown at the top of the card. Location and camera data are removed when the image is saved.';
$string['cardimagealt'] = 'Image description (alternative text)';
$string['cardimagealt_help'] = 'Describe what the image shows, for people who cannot see it.';
$string['cardlabels'] = 'Labels';
$string['cardlinks'] = 'Links';
$string['cardlinks_help'] = 'One link per line, up to 10. Write only the address, or a title, a vertical bar and the address, for example: Weather data | https://example.org/weather';
$string['cardsize'] = 'Card size';
$string['cardsize_auto'] = 'Automatic (fits the content)';
$string['cardsize_help'] = 'With a fixed size (S, M or L) all cards have the same height: long titles are shortened and images cropped to fit. Open a card to see all of it. Automatic lets every card grow with its content.';
$string['cardsize_l'] = 'Fixed: large (title and large image)';
$string['cardsize_m'] = 'Fixed: medium (title and small image)';
$string['cardsize_s'] = 'Fixed: small (title only)';
$string['cardsizeshort_auto'] = 'Auto';
$string['cardsizeshort_l'] = 'L';
$string['cardsizeshort_m'] = 'M';
$string['cardsizeshort_s'] = 'S';
$string['cardtitle'] = 'Title';
$string['cardvideo'] = 'PeerTube video';
$string['cardvideo_help'] = 'Address of a video on an allowed PeerTube server, for example https://peertube.example.org/w/abc123.';
$string['checksaria'] = 'Checklist {$a->done} of {$a->total}';
$string['col_check'] = 'To check';
$string['col_create'] = 'Create';
$string['col_doing'] = 'In progress';
$string['col_done'] = 'Done';
$string['col_ideas'] = 'Ideas';
$string['col_open'] = 'Open';
$string['col_plan'] = 'Plan';
$string['col_present'] = 'Present';
$string['col_research'] = 'Research';
$string['col_todo'] = 'To do';
$string['columnactions'] = 'Actions for column {$a}';
$string['columnisdone'] = 'Cards here count as done';
$string['columnisdone_help'] = 'Cards in this column count towards activity completion and the automatic grade, and are marked as done.';
$string['columntemplate'] = 'Columns';
$string['columntemplate_help'] = 'The columns the template board starts with. Teachers can change the template board later; new boards copy it.';
$string['columntitle'] = 'Column title';
$string['completioncards'] = 'Cards in done columns';
$string['completioncards_help'] = 'The activity is complete for a participant once this many of their cards lie in a column marked as done. A card counts for its author and for everyone assigned to it. If the activity is graded with points, the grade is the share of this number that is reached.';
$string['completiondetail:cards'] = 'Move {$a} cards to done';
$string['createdby'] = 'Created by {$a}';
$string['defaultcardsize'] = 'Default card size';
$string['defaultcardsize_desc'] = 'The card size new task boards start with.';
$string['deletecard'] = 'Delete card';
$string['deletecardconfirm'] = 'Delete this card with its checklist and image?';
$string['deletecolumn'] = 'Delete column';
$string['deletecolumnconfirm'] = 'Delete this column and all cards in it?';
$string['donebadge'] = 'done';
$string['draghint'] = 'Drag cards with mouse, finger or pen. Click or tap a card to open it.';
$string['duedatearia'] = 'Due {$a}';
$string['editcard'] = 'Edit card';
$string['editcolumn'] = 'Edit column';
$string['eventcardapproved'] = 'Card approved';
$string['eventcardcreated'] = 'Card created';
$string['eventcarddeleted'] = 'Card deleted';
$string['eventcardmoved'] = 'Card moved';
$string['eventcardupdated'] = 'Card updated';
$string['eventcolumncreated'] = 'Column created';
$string['eventcolumndeleted'] = 'Column deleted';
$string['eventcolumnupdated'] = 'Column updated';
$string['imagealtrequired'] = 'Please describe the image.';
$string['imagetoolarge'] = 'The image is larger than {$a}.';
$string['imagewithalt'] = 'Image: {$a}';
$string['incolumn'] = 'Column {$a}';
$string['invalidcolumn'] = 'The column does not belong to this board.';
$string['invalidimage'] = 'The file is not a supported image (JPEG, PNG, GIF or WebP).';
$string['invalidlink'] = 'Line {$a} is not a valid web address (starting with http:// or https://).';
$string['invalidvideo'] = 'This is not a video on an allowed PeerTube server.';
$string['invalidwiplimit'] = 'Enter a number from 0 to 99.';
$string['keyboardhint'] = 'Space picks up the card, arrow keys move it, Space drops it and Escape cancels. Enter opens the card.';
$string['keyboardposition'] = '{$a->column}, position {$a->position} of {$a->total}';
$string['label_helpneeded'] = 'Help needed';
$string['label_idea'] = 'Idea';
$string['label_important'] = 'Important';
$string['label_presentation'] = 'Presentation';
$string['label_research'] = 'Research';
$string['linksaria'] = '{$a} links';
$string['maxbytes'] = 'Maximum image size';
$string['maxbytes_desc'] = 'Largest image participants can put on a card.';
$string['mediasettings'] = 'Images and videos';
$string['modulename'] = 'Task board';
$string['modulename_help'] = 'A task board shows tasks as cards in columns, following the Kanban method. Participants move cards from column to column with mouse, finger, pen or keyboard. Cards can have an image, a checklist, labels, a due date and assigned participants. There can be one board for the course, one per group or one per participant.';
$string['modulenameplural'] = 'Task boards';
$string['movecancelled'] = 'Moving cancelled.';
$string['movecolumnleft'] = 'Move left';
$string['movecolumnright'] = 'Move right';
$string['movedto'] = 'Moved to {$a->column}, position {$a->position}.';
$string['moveundone'] = 'Move undone.';
$string['noassignees'] = 'Nobody assigned';
$string['noboards'] = 'There are no task boards in this course.';
$string['nogroup'] = 'You are not in a group, so there is no board for you yet.';
$string['nopermissionboard'] = 'You cannot open this board.';
$string['nopermissioncard'] = 'You cannot change this card.';
$string['nopermissioncardsize'] = 'The card size is set by the teacher.';
$string['nopermissioncolumns'] = 'You cannot change the columns of this board.';
$string['opensinnewwindow'] = '(opens in a new window)';
$string['pastehint'] = 'Tip: paste an image from the clipboard (Ctrl+V) to use it as the card image.';
$string['peertubehosts'] = 'Allowed PeerTube servers';
$string['peertubehosts_desc'] = 'Host names of PeerTube servers whose videos may be embedded on cards, one per line, for example peertube.example.org. Leave empty to disable videos.';
$string['pendingapproval'] = 'Waiting for approval';
$string['pendingapprovalnotice'] = 'This card is visible only to you and the teachers until a teacher approves it.';
$string['pickedup'] = 'Card {$a} picked up.';
$string['pluginadministration'] = 'Task board administration';
$string['pluginname'] = 'Task board';
$string['pollinterval'] = 'Update interval';
$string['pollinterval_desc'] = 'How often open boards check for changes made by others.';
$string['privacy:cards'] = 'Cards';
$string['privacy:metadata:assignees'] = 'Users assigned to cards.';
$string['privacy:metadata:assignees:userid'] = 'The assigned user.';
$string['privacy:metadata:boards'] = 'Boards of the activity; personal boards belong to a user.';
$string['privacy:metadata:boards:timecreated'] = 'When the board was created.';
$string['privacy:metadata:boards:userid'] = 'The owner of a personal board.';
$string['privacy:metadata:cards'] = 'Cards on the boards.';
$string['privacy:metadata:cards:createdby'] = 'The author of the card.';
$string['privacy:metadata:cards:description'] = 'The description of the card.';
$string['privacy:metadata:cards:imagealt'] = 'The description of the card image.';
$string['privacy:metadata:cards:timecreated'] = 'When the card was created.';
$string['privacy:metadata:cards:timemodified'] = 'When the card was last changed.';
$string['privacy:metadata:cards:title'] = 'The title of the card.';
$string['privacy:metadata:cards:videourl'] = 'The address of a video on the card.';
$string['privacy:metadata:core_files'] = 'Card images are stored with the file system.';
$string['privacy:metadata:links'] = 'Links on cards.';
$string['privacy:metadata:links:title'] = 'The title of the link.';
$string['privacy:metadata:links:url'] = 'The address of the link.';
$string['privacy:metadata:preference:cardsize'] = 'The card size the user chose for a task board.';
$string['requirealt'] = 'Image description required';
$string['requirealt_help'] = 'Every card image needs a short description for people who use a screen reader.';
$string['rightssettings'] = 'Permissions of participants';
$string['savepastedimage'] = 'Save image';
$string['selectboard'] = 'Board';
$string['studentrights'] = 'Participants may';
$string['studentrights_all'] = 'Edit all cards and columns';
$string['studentrights_help'] = 'Only move cards: teachers prepare all cards on the template board, for example for station learning, and participants only move them. Add own cards: participants also create cards and edit their own. Edit all: participants edit every card and the columns of their board.';
$string['studentrights_move'] = 'Only move cards';
$string['studentrights_own'] = 'Add cards and edit their own';
$string['taskboard:addinstance'] = 'Add a new task board';
$string['taskboard:contribute'] = 'Move and add cards';
$string['taskboard:manage'] = 'Manage task boards';
$string['taskboard:view'] = 'View task boards';
$string['template_classic'] = 'Classic: To do, In progress, Done';
$string['template_ideas'] = 'With ideas: Ideas, To do, In progress, Done';
$string['template_project'] = 'Project in five phases';
$string['template_stations'] = 'Station learning';
$string['templatenotice'] = 'This is the template board. Every new board starts as a copy of its columns and cards.';
$string['toomanylinks'] = 'A card can hold at most {$a} links.';
$string['undo'] = 'Undo';
$string['wipexceeded'] = 'Limit exceeded: at most {$a} cards at the same time.';
$string['wiplimit'] = 'Work-in-progress limit';
$string['wiplimit_help'] = 'How many cards may be in progress at the same time. If more cards are moved into the column, it shows a warning; moving is still possible. 0 means no limit.';
