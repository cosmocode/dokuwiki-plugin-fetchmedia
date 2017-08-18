<?php
/**
 * English language file for fetchmedia plugin
 *
 * @author Michael Große <dokuwiki@cosmocode.de>
 */

// menu entry for admin plugins
$lang['menu'] = 'Fetch external media-files';


$lang['label: namespace input'] = 'Namespace to download';
$lang['label: all media'] = 'Both types of media';
$lang['label: windows shares'] = 'Only Windows-File-Shares';
$lang['label: common media only'] = 'Only common media files';
$lang['label: button search'] = 'Search for external media files';

$lang['title: namespace input hint'] = 'Valid namespaces can consist of lowercase letters, numbers and the "_", "-" and ":" characters';

$lang['error: windows share missing'] = 'Windows share doesn\'t exist or the server hasn\'t sufficient access rights for it';
$lang['error: windows share not readable'] = 'Windows share is not readable.';
$lang['error: failed to open stream'] = 'Failed to open stream';
$lang['error: failed to download file'] = 'Failed to download file';
$lang['error: page is locked'] = 'Page is locked by %s';
$lang['error: windows share is directory'] = 'Link goes to a directory instead of file';

$lang['js']['table-heading: page'] = 'Page 📄';
$lang['js']['table-heading: links'] = 'Links 🔗';
$lang['js']['table-heading: results'] = 'Results';
$lang['js']['error: no links found'] = 'No links found in the given namespace.';
$lang['js']['error: error retrieving links'] = 'An error occured while retrieving the media links.';
$lang['js']['label: button download'] = 'Download Now';
$lang['js']['message: waiting for response'] = 'Waiting for a response...';


//Setup VIM: ex: et ts=4 :
