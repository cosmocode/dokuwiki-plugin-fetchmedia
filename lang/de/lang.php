<?php
/**
 * German language file for fetchmedia plugin
 *
 * @author Michael Große <dokuwiki@cosmocode.de>
 */

// menu entry for admin plugins
$lang['menu'] = 'Externe Mediendateien herunterladen';

$lang['label: namespace input'] = 'Namensraum';
$lang['label: all media'] = 'Beide Arten von externen Medienlinks';
$lang['label: windows shares'] = 'Nur Windows-File-Shares';
$lang['label: common media only'] = 'Nur gewöhnlich eingebundene Mediendateien';
$lang['label: button search'] = 'externe Medienlinks suchen';

$lang['title: namespace input hint'] = 'Ein Namensraum kann aus a-z, Zahlen und "_","-" und ":" bestehen';

$lang['error: windows share missing'] = 'Windows-File-Share existiert nicht oder der Server hat nicht die notwendigen Zugriffsrechte für diese Datei';
$lang['error: windows share not readable'] = 'Windows-File-Share kann nicht gelesen werden.';
$lang['error: failed to open stream'] = 'Konnte Stream zum Dateidownload nicht öffen';
$lang['error: failed to download file'] = 'Herunterladen der Datei ist fehlgeschlagen';
$lang['error: page is locked'] = 'Seite ist gesperrt von %s';
$lang['error: windows share is directory'] = 'Es wurde ein Ordner statt einer Datei verlinkt.';

$lang['js']['table-heading: page'] = 'Seite 📄';
$lang['js']['table-heading: links'] = 'Links 🔗';
$lang['js']['table-heading: results'] = 'Ergbnisse';
$lang['js']['error: no links found'] = 'Im angegebenen Namensraum wurden keine externen Links gefunden.';
$lang['js']['error: error retrieving links'] = 'Beim Suchen der Medienlinks ist ein Fehler aufgetreten.';
$lang['js']['label: button download'] = 'Jetzt herunterladen';
$lang['js']['message: waiting for response'] = 'Wartet auf Antwort...';


//Setup VIM: ex: et ts=4 :
