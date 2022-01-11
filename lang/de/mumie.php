<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for mod_mumie
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'MUMIE Task';
$string['modulename'] = 'MUMIE Task';
$string['modulename_help'] = '<p>Dieses Aktivität-Modul ermöglicht die Nutzung von Inhalten der MUMIE-Plattform auf Moodle und eine automatische Synchronisierung der dort erzielten Noten.
Ein MUMIE Task steht hierbei für eine einzelne benote Aufgabe eines MUMIE-Kurses.</p>
<p><strong>Was ist MUMIE?</strong></p>
<p>

MUMIE ist eine E-Learning-Plattform zum Lernen und Lehren von Mathematik und Informatik.
Sie wurde ursprünglich entwickelt, um das praxisorientierte Unterrichten an der Schnittstelle zwischen Gymnasium und Universität zu verbessern.
Die MUMIE zeichnet sich durch ihr hohes Maß an Flexibilität aus und kann unkompliziert in andere Lern- und Content-Management-Systeme eingebunden werden.
MUMIE-Kurse und deren hochwertige Kursmaterialien können ganz nach Ihren Wünschen an Ihre pädagogischen Anforderungen angepasst werden.
Sie bietet Lern- und Trainingsumgebungen und Wiki-artige soziale Netzwerke für virtuelle Tutorien und selbstorganisiertes Lernen und zur Verbesserung von kognitiven und metakognitiven Fähigkeiten.
Leistungsstarke Autorenwerkzeuge ermöglichen die Erstellung von neuen Inhalten. Dies öffnet die Tür für neue, herausfordernde und effizientere pädagogische Szenarien.

<br /><br /> Besuchen Sie unsere <a href="https://www.mumie.net/platform/" target="_blank" rel="nofollow noreferrer noopener">Website</a> für weitere Informationen.</p>
<p><strong>Key-Features der MUMIE Task</strong></p>
<ul>
<li><strong>Benutzen Sie MUMIE Tasks in Moodle</strong><br /> Fügen Sie Ihrem Kurs beliebig viele MUMIE Taks mit einem leicht zu bedienenden Menü hinzu.</li>
<li><strong>Erhalten Sie immer die neusten Inhalte</strong><br /> Sobald auf einem MUMIE-Server neue Inhalte verfügar sind, stehen Ihnen diese sofort zur Verfügung. Dafür ist kein weiteres Update notwendig!</li>
<li><strong>Mehrsprachig</strong><br /> Die meisten Inhalte auf MUMIE-Servern sind in mehreren Sprachen verfügbar. Sie können die Anzeigesprache mit einem simplen Knopfdruck ändern.</li>
<li><strong>Automatische Synchronisierung von Noten</strong><br /> Alle MUMIE Tasks werden automatisch benotet und die so erziehlten Noten werden in das Moodle-Gradebook aufgenommen.</li>
<li><strong>Single-Sign-On und automatisches Sign-Out</strong><br /> Studierende müssen keinen neuen Account für die MUMIE-Plattform erstellen oder sich einloggen. Dies geschieht automatisch, sobald sie eine MUMIE Task starten.
Aus Sicherheitsgründen werden sie zudem auch auf MUMIE-Servern ausgeloggt, wenn sie sich in Moodle ausloggen.</li>
</ul>';
$string['modulenameplural'] = 'MUMIE Tasks';
$string['pluginadministration'] = 'MUMIE-Administration';
$string['mumieintro'] = 'Aktivitätsbeschreibung';

// Used in index.php.
$string['nomumieinstance'] = 'In diesem Kurs gibt es keine MUMIE-Instanzen';
$string['name'] = 'Name';

// These strings are used in add activity form.
$string['mumie_form_activity_header'] = 'Allgemein';
$string['mumie_form_activity_name'] = 'Name';
$string['mumie_form_activity_server'] = 'MUMIE-Server';
$string['mumie_form_activity_server_help'] = 'Bitte wählen Sie einen MUMIE-Server, um eine aktuelle Auswahl von verfügbaren Kursen und Aufgaben zu erhalten.';
$string['mumie_form_activity_course'] = 'MUMIE-Kurs';
$string['mumie_form_activity_problem'] = "MUMIE-Aufgabe";
$string['mumie_form_activity_problem_help'] = "Eine MUMIE-Aufgabe ist eine einzelne benotete Aufgabe in MUMIE.<br><br><b>Achtung:</b><br>Wenn Sie für eine bereits existierende MUMIE-Task eine andere Aufgabe auswählen, dann werden die Bewertungen für diese Aktivität zurückgesetzt";
$string['mumie_form_activity_container'] = 'Startcontainer';
$string['mumie_form_activity_container_help'] = 'Bitte wählen Sie, ob diese Aktivität in die Moodle-Umgebung eingebunden oder in einem neuen Browser-Tab geöffnet werden soll.<br><br>Bitte beachten Sie, dass eingebettete MUMIE Tasks aus technischen Gründen nicht mit Safari bearbeitet werden können. Für diese User wird diese MUMIE Task daher im gesamten Tab dargestellt.';
$string['mumie_form_activity_container_embedded'] = 'Eingebunden';
$string['mumie_form_activity_container_window'] = 'Neuer Browser-Tab';
$string['mumie_form_activity_language'] = 'Sprache';
$string['mumie_form_activity_language_help'] = 'Bitte wählen Sie die Sprache, in der diese MUMIE Task angezeigt werden soll.';
$string['mumie_form_server_added'] = 'MUMIE-Server wurde hinzugefügt';
$string['mumie_form_add_server_button'] = 'MUMIE-Server hinzufügen';
$string['mumie_form_coursefile'] = 'Path to MUMIE course meta file';
$string['mumie_form_points'] = 'Maximal erreichbare Punkte';
$string['mumie_form_points_help'] = 'Bitte geben Sie die maximal erreichbaren Punkte ein, die ein Student durch das Bearbeiten dieser Aufgabe erreichen kann.<br>Die Noten werden automatisch in das Moodle-Gradebook übertragen.';
$string['mumie_form_missing_server'] = 'Es konnte keine Kofiguration für die MUMIE-Server-URL gefunden werden, die zum Erstellen dieser MUMIE Task benutzt wurde.<br><br>
Die Felder <i>MUMIE-Server</i>, <i>MUMIE-Kurs</i>, <i>MUMIE-Aufgabe</i> und <i>Sprache</i> können nicht verändert werden bis ein MUMIE-Server für die folgende URL gespeichert wurde:<br><br>';
$string['mumie_form_cant_change_isgraded'] = 'Sie können bei einer bestehenden MUMIE Task nicht von bewerteten zu unbewerteten Aufgaben wechseln. Erstellen Sie stattdessen bitte eine neue MUMIE Task.';


$string['mumie_form_no_server_conf'] = 'Es konnte keine MUMIE-Server-Konfiguration gefunden werden.';
// This is taken and adapted from mod_quiz version 2018051400.
$string['completionpass'] = ' Bewertung für Bestehen notwendig';
$string['completionpass_help'] = 'Wenn diese Option aktiviert ist, wird die Aktivität als abgeschlossen betrachtet, sobald eine Bewertung vorliegt. Icons für die erfolgreiche oder erfolglose Bearbeitung können angezeigt werden,
wenn dafür eine Bewertungsgrenze festgelegt wurde.
<br><br><b>Bitte beachten Sie:</b><br>
Noten für MUMIE Tasks werden nur aktualisiert, wenn sie im Moodle-Gradebook angezeigt werden. Wenn Sie aktuelle Informationen über den Aktivitätsabschluss aller Studierneden sehen möchten,
sollten zu daher zuvor die Bewerterübersicht öffnen.';
$string['gradetopassnotset'] = 'Dieser Test hat keine Bestehensgrenze.';
$string['gradetopassmustbeset'] = 'Die Bestehensgrenze kann nicht 0 sein, da der Aktivitätsabschluss vom Erreichen dieser Grenze abhängig ist. Bitte geben Sie daher einen Wert über 0 ein.';
$string['mumie_due_date_help'] = 'Falls diese Option aktiviert ist, werden keine Noten, die nach dem gewählten Datum erzielt wurden, mit Moodle synchronisiert.';
$string['mumie_form_due_date_must_be_future'] = 'Das Datum der Abgabefrist kann nicht in der Vergangenheit liegen!';
$string['mumie_form_grade_pool'] = 'Punkte mit anderen Kursen teilen';
$string['mumie_form_grade_pool_help'] = 'Bestimmen Sie, ob MUMIE-Punktzahlen mit anderen Kursen geteilt werden.
<br>Falls diese Option aktiviert ist, werden Punkte, die für das Bearbeiten von MUMIE-Aufgaben in anderen MOODLE-Kursen vergeben wurden, auch in diesen Kurs übernommen.
<br>Falls diese Option deaktiviert ist, werden weder Punkte aus anderen MOODLE-Kursen übernommen noch werden hier erziehlte Punkte mit anderen Kursen geteilt.';
$string['mumie_form_grade_pool_warning'] = '<b style="color:red">Achtung:</b><br> Diese Entscheidung ist <b>endgültig</b> und gilt auch für alle anderen MUMIE Tasks in diesem Kurs';
$string['mumie_form_grade_pool_note'] = '<b>Hinweis:</b><br> Diese Entscheidung war <b>endgültig</b> und gilt auch für alle anderen MUMIE Tasks in diesem Kurs';
$string['mumie_form_filter'] = 'MUMIE-Tasks filtern';
$string['mumie_form_complete_course'] = 'Ganzen Kurs verlinken';
$string['mumie_form_complete_course_help'] = 'Nutzer werden nur eingeloggt und dann zur Kursübersicht weitergeleitet. Leistungsdaten werden für diese MUMIE-Task <b>nicht</b> synchronisiert.';
$string['mumie_form_launchcontainer_info'] = 'Eingebettete MUMIE-Task führen auf manchen Browsern und Betriebssystemen zu technischen Problemen. Daher empfehlen wir MUMIE-Tasks in einem neuen Fenster zu öffnen.';
$string['mumie_form_prb_selector_btn'] = 'Aufgabenauswahl öffnen';
$string['mumie_form_updated_selection'] = 'Aufgabe erfolgreich ausgewählt';
$string['mumie_form_tasks_edit'] = 'In anderen MUMIE-Tasks übernehmen';
$string['mumie_form_tasks_edit_info'] = 'Sie können einige der oben gewählten Einstellungen auch automatisch in anderen MUMIE-Tasks dieses Kurses übernehmen';
$string['mumie_form_tasks_selection_info'] = 'Bestimmen Sie die MUMIE-Tasks, für die die ausgewählten Eigenschaften ebenfalls übernommen werden sollen.';
$string['mumie_form_task_properties_selection_info'] = 'Wählen Sie die Eigenschaften dieser MUMIE-Task aus, die in anderen MUMIE-Tasks übernommen werden sollen.';
$string['mumie_form_properties'] = 'Eigenschaften';
$string['mumie_form_topic'] = 'Thema: {$a}';
$string['mumie_no_other_task_found'] = 'Keine andere MUMIE Tasks in Kurs gefunden';
$string['mumie_form_wiki_link'] = 'Bitte besuchen Sie unser <a target="_blank" href="https://wiki.mumie.net/wiki/MUMIE-Moodle-integration-for-teachers">Wiki</a> für weiterführende Informationen!';
$string['mumie_form_no_course_on_server'] = 'MUMIE-Server <b>{$a}</b> konnte nicht erreicht werden! Dieser Server wurde daher vorrübergehend aus der Auswahl entfernt.';

// Used in duedate form.
$string['mumie_duedate_form'] = 'Abgabefrist verlängern';

// Used in view.
$string['mumie_duedate_extension'] = 'Abfabefristverlängerung';
$string['mumie_duedate_not_set'] = 'Keine allgemeine Abgabefrist für diese MUMIE-Task festgelegt!';
$string['mumie_general_duedate'] = 'Allgemeine Abgabefrist:';
$string['mumie_grading_settings'] = "Individuelle Bewertungseinstellungen";
$string['mumie_grade_overridden'] = 'Bewertung erfolgreich gespeichert!';
$string['mumie_grade_invalid'] = 'Bewertung ungültig!';
$string['mumie_open_task'] = 'MUMIE-Task öffnen';

// Used in mumie_grader.
$string['mumie_submissions'] = 'Abgaben';
$string['mumie_override_gradebook'] = 'In Moodle-Notenbuch übernehmen';
$string['mumie_submission_date'] = 'Datum der Abgabe';
$string['mumie_grade_percentage'] = 'Bewerung in %';
$string['mumie_submissions_by'] = 'Abgaben von {$a}';
$string['mumie_submissions_info'] = 'Neue Abgaben werden automatisch in das Moodle-Notenbuch übernommen, sofern die Abgabefrist noch nicht abgelaufen ist.
Um eine andere Abgabe für die Bewertung zu verwenden, können Sie auf die entsprechende Schaltfläche in der Tabelle klicken.<br><br>
Diese Bewertung wird auch durch zukünftige Abgaben des Studierenden nicht ersetzt werden. Sie wird außerdem <b>nicht</b> automatisch angepasst, wenn Sie nachträglich die Maximalpunktzahl der MUMIE-Task ändern.';
$string['mumie_no_submissions'] = 'Keine Antworten abgegeben';

// Used in course view.
$string['mumie_due_date'] = 'Abgabefrist';
$string['mumie_tag_disabled'] = 'Deaktiviert';
$string['mumie_tag_disabled_help'] = 'Diese Aktivität ist gerade deaktiviert, da die Konfiguration noch nicht vollständig ist. Bitte öffnen Sie die Einstellungen dieser MUMIE-Task.';
$string['mumie_task_updated'] = 'Es wurde eine weitere MUMIE-Task aktualisiert';
$string['mumie_tasks_updated'] = 'Es wurden {$a} weitere MUMIE-Tasks aktualisiert';

// Used for drag&drop functionality.
$string['parameter_missing'] = 'Die hochgeladene Datei ist nicht kompatibel!';
$string['dndupload_message'] = 'Als neue MUMIE Task hinzufügen';
$string['server_config_missing'] = '<br><br>Es konnte keine Kofiguration für die MUMIE-Server-URL gefunden werden, die zum Erstellen dieser MUMIE Task benutzt wird. Bitten Sie Ihren Administrator eine MUMIE-Server-Konfiguration für die folgende URL zu erstellen:<br><br><b>{$a}</b>';
$string['dnd_addmessage'] = 'MUMIE-Task hinzufügen';
$string['dnd_addmessage_multiple'] = 'MUMIE-Tasks hinzufügen';

// Capabilities.
$string['mumie:addinstance'] = 'Neue MUMIE Task hinzufügen';
$string['mumie:viewgrades'] = 'Alle Noten einer MUMIE Task in einem Kurs sehen';
$string['mumie:grantduedateextension'] = 'Die Abgabefrist einer MUMIE-Task für individuelle Studierende verlängern';
$string['mumie:revokeduedateextension'] = 'Die Verlängerung der Abgabefrist einer MUMIE-Task für einen Studenten zurücknehmen';
$string['mumie:overridegrades'] = 'Überschreiben einer Moodle-Bewertung für einen Studenten mit den Punkten einer beliebigen Abgabe für die ausgewählte MUMIE-Task';

// Used in calendar.
$string['mumie_calendar_duedate_name'] = 'Abgabefirst: {$a}';
$string['mumie_calendar_duedate_desc'] = 'Später Abgaben werden nicht in die Moodle-Bewertungen übernommen';
$string['mumie_calendar_duedate_extension'] = 'Verlängerte Abgabefrist: {$a}';

// Privacy.
$string['privacy:metadata:mod_mumie_duedate_extensions:mumie'] = 'ID der MUMIE-Task, für die Abgabefrist verlängert wurde';
$string['privacy:metadata:mod_mumie_duedate_extensions:duedate'] = 'Zeitpunkt der Fristverlängerung';
$string['privacy:metadata:mod_mumie_duedate_extensions:userid'] = 'User ID des Nutzers, für den die Fristverlängerung gilt';
$string['privacy:metadata:mod_mumie_duedate_extensions:tableexplanation'] = 'Abgabefristverlängerungen';
