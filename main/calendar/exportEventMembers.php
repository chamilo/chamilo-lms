<?php
/* For licensing terms, see /license.txt */

/**
 * This file exclusively export event members for invitation or subscription.
 *
 * @author Nicolas Ducoulombier <nicolas.ducoulombier@beeznest.com>
 */
// setting the global file that gets the general configuration, the databases, the languages, ...
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_MYAGENDA;
api_block_anonymous_users();

$type = 'personal';
$id = (int) explode('_', $_REQUEST['id'])[1];
$action = $_REQUEST['a'] ?? null;

if (empty($id)) {
    exit;
}
$agenda = new Agenda($type);

switch ($action) {
    case 'export_invitees':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }
        $data = $agenda->exportEventMembersToCsv($id, "Invitee");
        Export::arrayToCsv($data);
        break;
    case 'export_subscribers':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }
        $data = $agenda->exportEventMembersToCsv($id, "Subscriber");
        Export::arrayToCsv($data);
    break;
}
exit;
