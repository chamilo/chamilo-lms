<?php //$id$
/**
 * This script contains the data to fill (or empty) the database with using
 * the fillers in this directory.
 * Most names were inspired by Hobbit names from the Lord of the Rings.
 * See https://en.wikipedia.org/wiki/List_of_Hobbits
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 *
 */
/**
 * Initialisation section
 */
$users = array();
$users[] = array(
    'username' => 'ywarnier',
    'pass' => 'ywarnier',
    'firstname' => 'Yannick',
    'lastname' => 'Warnier',
    'status' => 1,
    'auth_source' => 'platform',
    'email' => 'yannick.warnier@example.com',
    'creator_id' => 1,
    'active' => 1,
);
$users[] = array(
    'username' => 'mmosquera',
    'pass' => 'mmosquera',
    'firstname' => 'Michela',
    'lastname' => 'Mosquera Guardamino',
    'status' => 1,
    'auth_source' => 'platform',
    'email' => 'michela.mosquera@example.com',
    'creator_id' => 1,
    'active' => 1,
);
$users[] = array(
    'username' => 'mlanoix',
    'pass' => 'mlanoix',
    'firstname' => 'Michel',
    'lastname' => 'Lanoix',
    'status' => 5,
    'auth_source' => 'platform',
    'email' => 'michel.lanoix@example.com',
    'creator_id' => 1,
    'active' => 1
);

$users[] = array(
    'username' => 'jmontoya',
    'pass' => 'jmontoya',
    'firstname' => 'Julio',
    'lastname' => 'Montoya',
    'status' => 1,
    'auth_source' => 'platform',
    'email' => 'julio.montoya@example.com',
    'creator_id' => 1,
    'active' => 1
);

$users[] = array(
    'username' => 'agarcia',
    'pass' => 'agarcia',
    'firstname' => 'Alan',
    'lastname' => 'Garcia',
    'status' => 1,
    'auth_source' => 'platform',
    'email' => 'agarcia@example.com',
    'creator_id' => 1,
    'active' => 1
);

$users[] = array(
    'username' => 'pperez',
    'pass' => 'pperez',
    'firstname' => 'Pedro',
    'lastname' => 'Perez',
    'status' => 5,
    'auth_source' => 'platform',
    'email' => 'pperez@example.com',
    'creator_id' => 1,
    'active' => 1
);

$users[] = array(
    'username' => 'ggerard',
    'pass' => 'ggerard',
    'firstname' => 'Gabrielle',
    'lastname' => 'Gérard',
    'status' => 5,
    'auth_source' => 'platform',
    'email' => 'ggerard@example.com',
    'creator_id' => 1,
    'active' => 1
);

$users[] = array(
    'username' => 'norizales',
    'pass' => 'norizales',
    'firstname' => 'Noa',
    'lastname' => 'Orizales',
    'status' => 5,
    'auth_source' => 'platform',
    'email' => 'norizales@example.com',
    'creator_id' => 1,
    'active' => 1
);

$users[] = array(
    'username' => 'fbaggins',
    'pass' => 'fbaggins',
    'firstname' => 'Frodo',
    'lastname' => 'Baggins',
    'status' => 5,
    'auth_source' => 'platform',
    'email' => 'fbaggins@example.com',
    'creator_id' => 1,
    'active' => 1
);


$users[] = array(
    'username' => 'bbaggins',
    'pass' => 'bbagins',
    'firstname' => 'Bilbo',
    'lastname' => 'Baggins',
    'status' => 5,
    'auth_source' => 'platform',
    'email' => 'bbaggins@example.com',
    'creator_id' => 1,
    'active' => 1
);

$users[] = array(
    'username' => 'sgamgee',
    'pass' => 'sgamgee',
    'firstname' => 'Samwise',
    'lastname' => 'Gamgee',
    'status' => 5,
    'auth_source' => 'platform',
    'email' => 'sgamgee@example.com',
    'creator_id' => 1,
    'active' => 1
);


$users[] = array('username' => 'mbrandybuck', 'pass' => 'mbrandybuck', 'firstname' => 'Meriadoc', 'lastname' => 'Brandybuck', 'status' => 5, 'auth_source' => 'platform', 'email' => 'mbrandybuck@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'amaurichard', 'pass' => 'amaurichard', 'firstname' => 'Anabelle', 'lastname' => 'Maurichard', 'status' => 5, 'auth_source' => 'platform', 'email' => 'amaurichard@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'ptook', 'pass' => 'ptook', 'firstname' => 'Peregrin', 'lastname' => 'Took', 'status' => 5, 'auth_source' => 'platform', 'email' => 'ptook@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'abaggins', 'pass' => 'abaggins', 'firstname' => 'Angelica', 'lastname' => 'Baggins', 'status' => 5, 'auth_source' => 'platform', 'email' => 'abaggins@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'bproudfoot', 'pass' => 'bproudfoot', 'firstname' => 'Bodo', 'lastname' => 'Proudfoot', 'status' => 5, 'auth_source' => 'platform', 'email' => 'bproudfoot@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'csackville', 'pass' => 'csackville', 'firstname' => 'Camelia', 'lastname' => 'Sackville', 'status' => 5, 'auth_source' => 'platform', 'email' => 'csackville@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'dboffin', 'pass' => 'dboffin', 'firstname' => 'Donnamira', 'lastname' => 'Boffin', 'status' => 5, 'auth_source' => 'platform', 'email' => 'dboffin@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'efairbairn', 'pass' => 'efairbairn', 'firstname' => 'Elfstan', 'lastname' => 'Fairbairn', 'status' => 5, 'auth_source' => 'platform', 'email' => 'efairbairn@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'fgreenholm', 'pass'=> 'fgreenholm', 'firstname' => 'Fastred', 'lastname' => 'of Greenholm', 'status' => 5, 'auth_source' => 'platform', 'email' => 'fgreenholm@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'gboffin', 'pass'=> 'gboffin', 'firstname' => 'Griffo', 'lastname' => 'Boffin', 'status' => 5, 'auth_source' => 'platform', 'email' => 'gboffin@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'hbrandybuck', 'pass'=> 'hbrandybuck', 'firstname' => 'Hilda', 'lastname' => 'Brandybuck', 'status' => 5, 'auth_source' => 'platform', 'email' => 'hbrandybuck@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'itook', 'pass'=> 'itook', 'firstname' => 'Isengar', 'lastname' => 'Took', 'status' => 5, 'auth_source' => 'platform', 'email' => 'itook@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'mcotillard', 'pass'=> 'mcotillard', 'firstname' => 'Marion', 'lastname' => 'Cotillard', 'status' => 5, 'auth_source' => 'platform', 'email' => 'mcotillard@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'jcotton', 'pass'=> 'jcotton', 'firstname' => 'Jolly', 'lastname' => 'Cotton', 'status' => 5, 'auth_source' => 'platform', 'email' => 'jcotton@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'lcotton', 'pass'=> 'lcotton', 'firstname' => 'Lily', 'lastname' => 'Cotton', 'status' => 5, 'auth_source' => 'platform', 'email' => 'lcotton@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'mburrows', 'pass'=> 'mburrows', 'firstname' => 'Milo', 'lastname' => 'Burrows', 'status' => 5, 'auth_source' => 'platform', 'email' => 'mburrows@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'obolger', 'pass'=> 'obolger', 'firstname' => 'Odovacar', 'lastname' => 'Bolger', 'status' => 5, 'auth_source' => 'platform', 'email' => 'obolger@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'pbolger', 'pass'=> 'pbolger', 'firstname' => 'Prisca', 'lastname' => 'Bolger', 'status' => 5, 'auth_source' => 'platform', 'email' => 'pbolger@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'rgardner', 'pass'=> 'rgardner', 'firstname' => 'Ruby', 'lastname' => 'Gardner', 'status' => 5, 'auth_source' => 'platform', 'email' => 'rgardner@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'stook', 'pass'=> 'stook', 'firstname' => 'Sigismond', 'lastname' => 'Took', 'status' => 5, 'auth_source' => 'platform', 'email' => 'stook@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'sgollum', 'pass'=> 'sgollum', 'firstname' => 'Sméagol', 'lastname' => 'Gollum', 'status' => 5, 'auth_source' => 'platform', 'email' => 'sgollum@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'tsandyman', 'pass'=> 'tsandyman', 'firstname' => 'Ted', 'lastname' => 'Sandyman', 'status' => 5, 'auth_source' => 'platform', 'email' => 'tsandyman@example.com', 'creator_id' => 1, 'active' => 1);
$users[] = array('username' => 'wgamwich', 'pass'=> 'wgamwich', 'firstname' => 'Wiseman', 'lastname' => 'Gamwich', 'status' => 5, 'auth_source' => 'platform', 'email' => 'wgamwich@example.com', 'creator_id' => 1, 'active' => 1);

