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
 * German strings for mod_taskboard.
 *
 * @package    mod_taskboard
 * @category   string
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addcard'] = '+ Karte hinzufügen';
$string['addcolumn'] = 'Spalte hinzufügen';
$string['allowimages'] = 'Bilder auf Karten erlauben';
$string['allowimages_help'] = 'Jede Karte kann ein Titelbild bekommen: hochgeladen, aus einem Repository gewählt (z. B. dem Bildpool, falls installiert) oder aus der Zwischenablage eingefügt. Bilder werden beim Speichern neu kodiert. Dabei werden Standort- und Kameradaten (EXIF) entfernt und die Bilder auf höchstens 1600 Pixel verkleinert.';
$string['allowusersize'] = 'Teilnehmende dürfen die Kartengröße ändern';
$string['allowusersize_help'] = 'Wenn aktiviert, kann jede Person die Kartengröße auf dem Board für sich umschalten. Die Einstellung oben ist die Voreinstellung.';
$string['allowvideos'] = 'PeerTube-Videos auf Karten erlauben';
$string['allowvideos_help'] = 'Karten können ein PeerTube-Video zeigen. Eingebettet werden nur Videos von PeerTube-Servern, die die Administration freigegeben hat. Solange kein Server freigegeben ist, ist die Option gesperrt.';
$string['approval'] = 'Neue Karten freigeben';
$string['approval_help'] = 'Neue Karten von Teilnehmenden sehen zunächst nur sie selbst und die Lehrkräfte. Alle anderen sehen sie, sobald eine Lehrkraft sie freigegeben hat.';
$string['approvalnotice'] = 'Neue Karten sehen die anderen erst, wenn eine Lehrkraft sie freigegeben hat.';
$string['approvecard'] = 'Freigeben';
$string['board_course'] = 'Kurs-Board';
$string['board_group'] = 'Board von {$a}';
$string['board_personal'] = 'Board von {$a}';
$string['board_template'] = 'Vorlage-Board';
$string['boardsettings'] = 'Board';
$string['boardtype'] = 'Board-Art';
$string['boardtype_course'] = 'Ein gemeinsames Board für den Kurs';
$string['boardtype_group'] = 'Ein Board pro Gruppe';
$string['boardtype_help'] = 'Bei „Ein Board pro Gruppe“ bekommt jede Gruppe des Kurses (oder der gewählten Gruppierung) ein eigenes Board; Lehrkräfte wechseln zwischen den Boards. Bei persönlichen Boards arbeitet jede Person auf ihrem eigenen Board. Jedes neue Board startet als Kopie des Vorlage-Boards.';
$string['boardtype_personal'] = 'Ein persönliches Board pro Person';
$string['cannotmovecolumn'] = 'Die Spalte kann nicht weiter in diese Richtung verschoben werden.';
$string['cardassignees'] = 'Zugewiesen an';
$string['cardchecks'] = 'Checkliste';
$string['cardchecks_help'] = 'Ein Punkt pro Zeile. Punkte mit unverändertem Text behalten ihren Haken.';
$string['carddescription'] = 'Beschreibung';
$string['cardduedate'] = 'Fällig am';
$string['cardimage'] = 'Bild';
$string['cardimage_help'] = 'Ein Bild oben auf der Karte. Standort- und Kameradaten werden beim Speichern entfernt.';
$string['cardimagealt'] = 'Bildbeschreibung (Alternativtext)';
$string['cardimagealt_help'] = 'Beschreiben Sie, was auf dem Bild zu sehen ist, für Menschen, die es nicht sehen können.';
$string['cardlabels'] = 'Labels';
$string['cardlinks'] = 'Links';
$string['cardlinks_help'] = 'Ein Link pro Zeile, höchstens 10. Entweder nur die Adresse oder Titel, senkrechter Strich und Adresse, z. B.: Wetterdaten | https://example.org/wetter';
$string['cardsize'] = 'Kartengröße';
$string['cardsize_auto'] = 'Automatisch (passt sich dem Inhalt an)';
$string['cardsize_help'] = 'Bei fester Größe (S, M oder L) haben alle Karten dieselbe Höhe: Lange Titel werden gekürzt, Bilder passend zugeschnitten. Ein Klick auf die Karte zeigt sie vollständig. „Automatisch“ passt jede Karte an ihren Inhalt an.';
$string['cardsize_l'] = 'Fest: L (Titel und großes Bild)';
$string['cardsize_m'] = 'Fest: M (Titel und kleines Bild)';
$string['cardsize_s'] = 'Fest: S (nur Titel)';
$string['cardsizeshort_auto'] = 'Auto';
$string['cardsizeshort_l'] = 'L';
$string['cardsizeshort_m'] = 'M';
$string['cardsizeshort_s'] = 'S';
$string['cardtitle'] = 'Titel';
$string['cardvideo'] = 'PeerTube-Video';
$string['cardvideo_help'] = 'Adresse eines Videos auf einem freigegebenen PeerTube-Server, z. B. https://peertube.example.org/w/abc123.';
$string['checksaria'] = 'Checkliste {$a->done} von {$a->total}';
$string['col_check'] = 'Zur Kontrolle';
$string['col_create'] = 'Erstellen';
$string['col_doing'] = 'In Arbeit';
$string['col_done'] = 'Erledigt';
$string['col_ideas'] = 'Ideen';
$string['col_open'] = 'Offen';
$string['col_plan'] = 'Planen';
$string['col_present'] = 'Präsentieren';
$string['col_research'] = 'Recherchieren';
$string['col_todo'] = 'Zu erledigen';
$string['columnactions'] = 'Aktionen für Spalte {$a}';
$string['columnisdone'] = 'Karten hier gelten als erledigt';
$string['columnisdone_help'] = 'Karten in dieser Spalte zählen für den Aktivitätsabschluss und die automatische Bewertung und werden als erledigt markiert.';
$string['columntemplate'] = 'Spalten';
$string['columntemplate_help'] = 'Mit diesen Spalten startet das Vorlage-Board. Lehrkräfte können das Vorlage-Board später ändern; neue Boards kopieren es.';
$string['columntitle'] = 'Spaltentitel';
$string['completioncards'] = 'Karten in Erledigt-Spalten';
$string['completioncards_help'] = 'Die Aktivität ist für eine Person abgeschlossen, sobald so viele ihrer Karten in einer als erledigt markierten Spalte liegen. Eine Karte zählt für die Person, die sie angelegt hat, und für alle, denen sie zugewiesen ist. Wird die Aktivität mit Punkten bewertet, ergibt sich die Bewertung aus dem erreichten Anteil dieser Zahl.';
$string['completiondetail:cards'] = '{$a} Karten nach „Erledigt“ verschieben';
$string['createdby'] = 'Erstellt von {$a}';
$string['defaultcardsize'] = 'Voreingestellte Kartengröße';
$string['defaultcardsize_desc'] = 'Mit dieser Kartengröße starten neue Taskboards.';
$string['deletecard'] = 'Karte löschen';
$string['deletecardconfirm'] = 'Diese Karte mit Checkliste und Bild löschen?';
$string['deletecolumn'] = 'Spalte löschen';
$string['deletecolumnconfirm'] = 'Diese Spalte mit allen Karten darin löschen?';
$string['donebadge'] = 'erledigt';
$string['draghint'] = 'Karten mit Maus, Finger oder Stift ziehen. Klicken oder Antippen öffnet eine Karte.';
$string['duedatearia'] = 'Fällig am {$a}';
$string['editcard'] = 'Karte bearbeiten';
$string['editcolumn'] = 'Spalte bearbeiten';
$string['eventcardapproved'] = 'Karte freigegeben';
$string['eventcardcreated'] = 'Karte angelegt';
$string['eventcarddeleted'] = 'Karte gelöscht';
$string['eventcardmoved'] = 'Karte verschoben';
$string['eventcardupdated'] = 'Karte geändert';
$string['eventcolumncreated'] = 'Spalte angelegt';
$string['eventcolumndeleted'] = 'Spalte gelöscht';
$string['eventcolumnupdated'] = 'Spalte geändert';
$string['imagealtrequired'] = 'Bitte beschreiben Sie das Bild.';
$string['imagetoolarge'] = 'Das Bild ist größer als {$a}.';
$string['imagewithalt'] = 'Bild: {$a}';
$string['incolumn'] = 'Spalte {$a}';
$string['invalidcolumn'] = 'Die Spalte gehört nicht zu diesem Board.';
$string['invalidimage'] = 'Die Datei ist kein unterstütztes Bild (JPEG, PNG, GIF oder WebP).';
$string['invalidlink'] = 'Zeile {$a} ist keine gültige Webadresse (beginnt mit http:// oder https://).';
$string['invalidvideo'] = 'Das ist kein Video auf einem freigegebenen PeerTube-Server.';
$string['invalidwiplimit'] = 'Geben Sie eine Zahl von 0 bis 99 ein.';
$string['keyboardhint'] = 'Leertaste nimmt die Karte auf, Pfeiltasten verschieben sie, Leertaste legt sie ab, Escape bricht ab. Enter öffnet die Karte.';
$string['keyboardposition'] = '{$a->column}, Position {$a->position} von {$a->total}';
$string['label_helpneeded'] = 'Hilfe nötig';
$string['label_idea'] = 'Idee';
$string['label_important'] = 'Wichtig';
$string['label_presentation'] = 'Präsentation';
$string['label_research'] = 'Recherche';
$string['linksaria'] = '{$a} Links';
$string['maxbytes'] = 'Maximale Bildgröße';
$string['maxbytes_desc'] = 'Größtes Bild, das auf eine Karte gesetzt werden kann.';
$string['mediasettings'] = 'Bilder und Videos';
$string['modulename'] = 'Taskboard';
$string['modulename_help'] = 'Ein Taskboard zeigt Aufgaben als Karten in Spalten, nach der Kanban-Methode. Teilnehmende verschieben Karten mit Maus, Finger, Stift oder Tastatur von Spalte zu Spalte. Karten können ein Bild, eine Checkliste, Labels, ein Fälligkeitsdatum und zugewiesene Personen haben. Es gibt ein Board für den Kurs, eines pro Gruppe oder eines pro Person.';
$string['modulenameplural'] = 'Taskboards';
$string['movecancelled'] = 'Verschieben abgebrochen.';
$string['movecolumnleft'] = 'Nach links verschieben';
$string['movecolumnright'] = 'Nach rechts verschieben';
$string['movedto'] = 'Verschoben nach {$a->column}, Position {$a->position}.';
$string['moveundone'] = 'Verschieben rückgängig gemacht.';
$string['noassignees'] = 'Niemand zugewiesen';
$string['noboards'] = 'In diesem Kurs gibt es keine Taskboards.';
$string['nogroup'] = 'Sie sind in keiner Gruppe, daher gibt es noch kein Board für Sie.';
$string['nopermissionboard'] = 'Sie können dieses Board nicht öffnen.';
$string['nopermissioncard'] = 'Sie können diese Karte nicht ändern.';
$string['nopermissioncardsize'] = 'Die Kartengröße legt die Lehrkraft fest.';
$string['nopermissioncolumns'] = 'Sie können die Spalten dieses Boards nicht ändern.';
$string['opensinnewwindow'] = '(öffnet in neuem Fenster)';
$string['pastehint'] = 'Tipp: Ein Bild aus der Zwischenablage (Strg+V) einfügen, um es als Kartenbild zu verwenden.';
$string['peertubehosts'] = 'Freigegebene PeerTube-Server';
$string['peertubehosts_desc'] = 'Hostnamen der PeerTube-Server, deren Videos auf Karten eingebettet werden dürfen, einer pro Zeile, z. B. peertube.example.org. Leer lassen, um Videos abzuschalten.';
$string['pendingapproval'] = 'Wartet auf Freigabe';
$string['pendingapprovalnotice'] = 'Diese Karte sehen nur Sie und die Lehrkräfte, bis eine Lehrkraft sie freigibt.';
$string['pickedup'] = 'Karte {$a} aufgenommen.';
$string['pluginadministration'] = 'Taskboard-Administration';
$string['pluginname'] = 'Taskboard';
$string['pollinterval'] = 'Aktualisierungsintervall';
$string['pollinterval_desc'] = 'Wie oft offene Boards nach Änderungen anderer suchen.';
$string['privacy:cards'] = 'Karten';
$string['privacy:metadata:assignees'] = 'Personen, denen Karten zugewiesen sind.';
$string['privacy:metadata:assignees:userid'] = 'Die zugewiesene Person.';
$string['privacy:metadata:boards'] = 'Boards der Aktivität; persönliche Boards gehören einer Person.';
$string['privacy:metadata:boards:timecreated'] = 'Wann das Board angelegt wurde.';
$string['privacy:metadata:boards:userid'] = 'Die Person, der das persönliche Board gehört.';
$string['privacy:metadata:cards'] = 'Karten auf den Boards.';
$string['privacy:metadata:cards:createdby'] = 'Die Person, die die Karte angelegt hat.';
$string['privacy:metadata:cards:description'] = 'Die Beschreibung der Karte.';
$string['privacy:metadata:cards:imagealt'] = 'Die Beschreibung des Kartenbildes.';
$string['privacy:metadata:cards:timecreated'] = 'Wann die Karte angelegt wurde.';
$string['privacy:metadata:cards:timemodified'] = 'Wann die Karte zuletzt geändert wurde.';
$string['privacy:metadata:cards:title'] = 'Der Titel der Karte.';
$string['privacy:metadata:cards:videourl'] = 'Die Adresse eines Videos auf der Karte.';
$string['privacy:metadata:core_files'] = 'Kartenbilder werden im Dateisystem gespeichert.';
$string['privacy:metadata:links'] = 'Links auf Karten.';
$string['privacy:metadata:links:title'] = 'Der Titel des Links.';
$string['privacy:metadata:links:url'] = 'Die Adresse des Links.';
$string['privacy:metadata:preference:cardsize'] = 'Die Kartengröße, die eine Person für ein Taskboard gewählt hat.';
$string['requirealt'] = 'Bildbeschreibung verpflichtend';
$string['requirealt_help'] = 'Jedes Kartenbild braucht eine kurze Beschreibung für Menschen, die einen Screenreader nutzen.';
$string['rightssettings'] = 'Rechte der Teilnehmenden';
$string['savepastedimage'] = 'Bild speichern';
$string['selectboard'] = 'Board';
$string['studentrights'] = 'Teilnehmende dürfen';
$string['studentrights_all'] = 'Alle Karten und Spalten bearbeiten';
$string['studentrights_help'] = 'Nur verschieben: Lehrkräfte legen alle Karten auf dem Vorlage-Board an, z. B. für Stationenlernen; Teilnehmende verschieben sie nur. Eigene Karten anlegen: Teilnehmende legen auch Karten an und bearbeiten ihre eigenen. Alle bearbeiten: Teilnehmende bearbeiten alle Karten und die Spalten ihres Boards.';
$string['studentrights_move'] = 'Karten nur verschieben';
$string['studentrights_own'] = 'Karten anlegen und eigene bearbeiten';
$string['taskboard:addinstance'] = 'Neues Taskboard hinzufügen';
$string['taskboard:contribute'] = 'Karten verschieben und anlegen';
$string['taskboard:manage'] = 'Taskboards verwalten';
$string['taskboard:view'] = 'Taskboards ansehen';
$string['template_classic'] = 'Klassisch: Zu erledigen, In Arbeit, Erledigt';
$string['template_ideas'] = 'Mit Ideenspalte: Ideen, Zu erledigen, In Arbeit, Erledigt';
$string['template_project'] = 'Projektarbeit in fünf Phasen';
$string['template_stations'] = 'Stationenlernen';
$string['templatenotice'] = 'Dies ist das Vorlage-Board. Jedes neue Board startet als Kopie seiner Spalten und Karten.';
$string['toomanylinks'] = 'Eine Karte kann höchstens {$a} Links enthalten.';
$string['undo'] = 'Rückgängig';
$string['wipexceeded'] = 'Limit überschritten: höchstens {$a} Karten gleichzeitig.';
$string['wiplimit'] = 'WIP-Limit (gleichzeitig in Arbeit)';
$string['wiplimit_help'] = 'Wie viele Karten gleichzeitig in Arbeit sein sollen. Liegen mehr Karten in der Spalte, zeigt sie einen Hinweis; Verschieben bleibt möglich. 0 bedeutet kein Limit.';
