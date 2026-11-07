# Taskboard – Anleitung

Das Taskboard ist eine Moodle-Aktivität für die Arbeit mit Kanban-Boards. Aufgaben stehen als Karten in Spalten, zum Beispiel „Zu erledigen“, „In Arbeit“ und „Erledigt“. Wer an einer Aufgabe arbeitet, schiebt die Karte einfach weiter.

Diese Anleitung hat drei Teile: für **Lehrkräfte**, für **Lernende** und für die **Administration**.

---

## Teil 1: Für Lehrkräfte

### Taskboard anlegen

1. Bearbeiten im Kurs einschalten und **Aktivität oder Material anlegen** wählen.
2. **Taskboard** auswählen.
3. Einen Namen und bei Bedarf eine Beschreibung eintragen.
4. Die Einstellungen im Abschnitt **Board** festlegen (siehe unten) und speichern.

Jede Einstellung hat ein blaues Fragezeichen-Symbol. Ein Klick darauf zeigt eine kurze Erklärung.

### Die wichtigsten Einstellungen

**Board-Art**

| Einstellung | Wofür |
|---|---|
| Ein gemeinsames Board für den Kurs | Die ganze Klasse arbeitet auf einem Board. |
| Ein Board pro Gruppe | Jede Moodle-Gruppe hat ihr eigenes Board, zum Beispiel für Projektarbeit. Die Gruppen müssen vorher im Kurs angelegt sein. |
| Ein persönliches Board pro Person | Jede Schülerin und jeder Schüler plant für sich, zum Beispiel als Lernplan. |

**Spalten**

Wählen Sie eine Vorlage für die Spalten:

- Klassisch: Zu erledigen, In Arbeit, Erledigt
- Mit Ideenspalte: Ideen, Zu erledigen, In Arbeit, Erledigt
- Projektarbeit in fünf Phasen
- Stationenlernen

Die Spalten können Sie später auf dem Vorlage-Board ändern.

**Kartengröße**

- **Auto:** Jede Karte ist so groß wie ihr Inhalt.
- **S, M, L:** Alle Karten sind gleich hoch. Lange Titel werden gekürzt, Bilder verkleinert. Ein Klick auf die Karte zeigt alles.

Mit „Teilnehmende dürfen die Kartengröße ändern“ legen Sie fest, ob Lernende für sich umschalten dürfen.

**WIP-Limit**

Das WIP-Limit („work in progress“) legt fest, wie viele Karten gleichzeitig in der Spalte „In Arbeit“ liegen sollen. Liegen mehr Karten dort, zeigt die Spalte einen orangefarbenen Hinweis. Verschieben bleibt trotzdem möglich. 0 bedeutet kein Limit.

**Rechte der Teilnehmenden**

| Einstellung | Was Lernende dürfen |
|---|---|
| Karten nur verschieben | Sie legen alle Karten selbst an, die Lernenden ordnen sie nur zu. Gut für Stationenlernen. |
| Karten anlegen und eigene bearbeiten | Lernende legen Karten an und bearbeiten ihre eigenen. (Voreinstellung) |
| Alle Karten und Spalten bearbeiten | Lernende gestalten ihr Board komplett selbst. |

**Neue Karten freigeben**

Ist diese Option an, sehen zunächst nur Sie und die Person selbst eine neue Karte. Erst nach Ihrer Freigabe sieht sie die ganze Gruppe. Wartende Karten haben einen gestrichelten Rahmen.

**Bilder und Videos**

- *Bilder auf Karten erlauben:* Karten können ein Titelbild bekommen.
- *Bildbeschreibung verpflichtend:* Zu jedem Bild muss eine kurze Beschreibung für Screenreader eingegeben werden.
- *PeerTube-Videos auf Karten erlauben:* Karten können ein Video aus dem PeerTube-Repository zeigen. Die Option ist nur verfügbar, wenn die Administration einen PeerTube-Server freigegeben hat.

**Abschluss und Bewertung**

- Unter **Aktivitätsabschluss** können Sie festlegen: „Karten in Erledigt-Spalten: X“. Die Aktivität ist abgeschlossen, sobald so viele Karten einer Person in einer Erledigt-Spalte liegen. Eine Karte zählt für die Person, die sie angelegt hat, und für alle, denen sie zugewiesen ist.
- Wählen Sie unter **Bewertung** Punkte, berechnet Moodle die Note automatisch aus dem erreichten Anteil. Beispiel: Ziel 5 Karten, maximal 10 Punkte. Wer 3 Karten erledigt hat, bekommt 6 Punkte. Sie können die Note im Bewertungsbuch jederzeit ändern.
- Wird das Taskboard über den LTI-Hub eingebunden, meldet Moodle die Bewertung automatisch an den Schulkurs zurück.

### Das Vorlage-Board

Oben auf dem Board finden Sie die Auswahl **Board**. Dort steht als erster Eintrag **Vorlage-Board**.

Alles, was Sie auf dem Vorlage-Board anlegen, also Spalten, Karten, Checklisten, Bilder und Links, wird in jedes **neue** Board kopiert. So bereiten Sie zum Beispiel Stationen oder Projektschritte einmal vor, und jede Gruppe bekommt sie.

> **Wichtig:** Ein Board wird erzeugt, sobald eine Gruppe oder Person es zum ersten Mal öffnet. Bereiten Sie das Vorlage-Board deshalb **vor**, bevor die Lernenden das Taskboard öffnen. Spätere Änderungen an der Vorlage ändern bestehende Boards nicht.

### Zwischen Boards wechseln

Bei Gruppen-Boards und persönlichen Boards wählen Sie oben über **Board** die Gruppe oder die Person aus. So sehen Sie den Arbeitsstand jeder Gruppe.

### Spalten bearbeiten

Über die drei Punkte (⋮) im Spaltenkopf können Sie:

- die Spalte umbenennen und ihr WIP-Limit ändern,
- festlegen, ob Karten in dieser Spalte als **erledigt** gelten (wichtig für Abschluss und Bewertung),
- die Spalte nach links oder rechts verschieben,
- die Spalte löschen (mit allen Karten darin, nach Rückfrage).

Mit **Spalte hinzufügen** rechts neben der letzten Spalte legen Sie eine neue an.

### Karten freigeben

Öffnen Sie eine wartende Karte (gestrichelter Rahmen) und klicken Sie auf **Freigeben**.

---

## Teil 2: Für Lernende

### Eine Karte anlegen

1. Klicke unter einer Spalte auf **+ Karte hinzufügen**.
2. Gib einen **Titel** ein. Alles andere ist freiwillig:
   - **Beschreibung:** Worum geht es?
   - **Labels:** z. B. „Recherche“ oder „Hilfe nötig“
   - **Fällig am:** bis wann die Aufgabe fertig sein soll
   - **Zugewiesen an:** wer daran arbeitet
   - **Checkliste:** ein Punkt pro Zeile
   - **Links:** ein Link pro Zeile, siehe unten
   - **Bild** mit **Bildbeschreibung**
   - **PeerTube-Video**, wenn erlaubt
3. Klicke auf **Speichern**.

### Karten verschieben

**Mit Maus, Finger oder Stift:** Karte anfassen und in eine andere Spalte oder an eine andere Stelle ziehen. Eine gestrichelte Lücke zeigt, wo die Karte landet. Ziehst du an den Bildschirmrand, scrollt das Board mit.

**Mit der Tastatur:**

| Taste | Wirkung |
|---|---|
| Tab | zur Karte springen |
| Leertaste | Karte aufnehmen |
| Pfeil links / rechts | Karte in die Nachbarspalte schieben |
| Pfeil hoch / runter | Karte innerhalb der Spalte verschieben |
| Leertaste oder Enter | Karte ablegen |
| Escape | abbrechen, die Karte springt zurück |

**Verschoben aus Versehen?** Unten erscheint kurz eine Meldung mit **Rückgängig**.

### Eine Karte öffnen

Ein Klick (oder Antippen) auf die Karte zeigt sie vollständig: Bild, Beschreibung, Links, Video und Checkliste. Hier kannst du

- Punkte der **Checkliste abhaken**,
- die Karte **bearbeiten** oder **löschen**, wenn du darfst,
- ein **Bild einfügen**: Bild kopieren, Karte öffnen, **Strg+V** drücken, Beschreibung eintragen, **Bild speichern**.

### Links eintragen

Im Feld **Links** steht ein Link pro Zeile. Es gibt zwei Schreibweisen:

```
https://www.dwd.de
Glossar | https://example.org/glossar
```

Steht vor dem senkrechten Strich `|` ein Titel, zeigt die Karte diesen Titel. Sonst erscheint der Name der Webseite. Auf der Karte siehst du den ersten Link und die Zahl der weiteren, zum Beispiel „Glossar +1“. Öffnen kannst du die Links in der geöffneten Karte.

### Kartengröße umschalten

Oben rechts kannst du zwischen **Auto, S, M und L** wechseln, wenn deine Lehrkraft das erlaubt. Die Einstellung gilt nur für dich.

### Was bedeuten die Zeichen auf einer Karte?

| Zeichen | Bedeutung |
|---|---|
| Kalender mit Datum | Fälligkeitsdatum (rot = überfällig) |
| Kästchen mit Haken, z. B. 2/5 | Checkliste: 2 von 5 Punkten erledigt |
| Kettenglied mit Titel | Links auf der Karte |
| Dreieck (Play) | Die Karte enthält ein Video |
| Häkchen „erledigt“ | Karte liegt in einer Erledigt-Spalte |
| Kreise mit Buchstaben | Zugewiesene Personen (Initialen) |
| „Wartet auf Freigabe“ | Die Lehrkraft muss die Karte noch freigeben |
| Orange Warnung in der Spalte | Es liegen mehr Karten in Arbeit als erlaubt |

---

## Teil 3: Für die Administration

Einstellungen unter *Website-Administration › Plugins › Aktivitäten › Taskboard*:

| Einstellung | Bedeutung |
|---|---|
| Voreingestellte Kartengröße | Kartengröße für neue Taskboards |
| Aktualisierungsintervall | Wie oft offene Boards nach Änderungen anderer suchen (Standard 15 Sekunden, 0 = aus) |
| Maximale Bildgröße | Obergrenze für Kartenbilder |
| Freigegebene PeerTube-Server | Hostnamen, eine Zeile pro Server, z. B. `peertube.moo-ni.de`. Leer = keine Videos |

### Technische Hinweise

- Bilder werden beim Speichern neu kodiert. Dabei werden Standort- und Kameradaten (EXIF) entfernt und die Bilder auf höchstens 1600 Pixel verkleinert.
- Videos werden nur von freigegebenen PeerTube-Servern eingebettet, auch nicht gelistete Videos. Das PeerTube-Repository muss in der Dateiauswahl Links liefern.
- Bilder kommen über die normale Moodle-Dateiauswahl. Ist `repository_bildpool` installiert und aktiviert, erscheint der Bildpool dort automatisch.
- Datenschutz: Persönliche Boards werden mit den Daten einer Person gelöscht. Karten auf gemeinsamen Boards bleiben erhalten und verlieren nur den Namen der Person, die sie angelegt hat.
- Sicherung: Das Vorlage-Board wird immer gesichert, alle anderen Boards nur mit Nutzerdaten.

### Rollen und Rechte

| Recht | Standardrollen | Erlaubt |
|---|---|---|
| mod/taskboard:view | Schüler/in, Lehrkräfte, Manager | Boards ansehen |
| mod/taskboard:contribute | Schüler/in, Lehrkräfte, Manager | Karten verschieben und (je nach Einstellung) anlegen |
| mod/taskboard:manage | Lehrkräfte, Manager | Vorlage-Board, alle Boards sehen, Karten freigeben |
| mod/taskboard:addinstance | Lehrkräfte mit Bearbeitungsrecht, Manager | Taskboard anlegen |

---

*Taskboard (mod_taskboard), Moodle in Niedersachsen e. V. · Lizenz GNU GPL v3 oder neuer*
