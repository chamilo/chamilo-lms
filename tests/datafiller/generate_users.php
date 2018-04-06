<?php
/**
 * Use this script (uncommenting the die(); line) to generate a file in
 * /tmp/data_users.php to replace the data_users.php file here with a much
 * larger number of (less realistic) users for testing purposes
 */
die();
$string = "\$users[] = array('username' => 'aaaX', 'pass' => 'aaaX', 'firstname' => 'AAAX', 'lastname' => 'BBBX', 'status' => 5, 'auth_source' => 'platform', 'email' => 'aaaX@example.com', 'creator_id' => 1, 'active' => 1);\n";
$output = '';
$outputFile = '/tmp/data_users.php';
$fh = fopen($outputFile, 'a+');
for ($i=1; $i < 5000; $i++) {
    fwrite($fh, str_replace('X', $i, $string));
}
fclose($fh);
