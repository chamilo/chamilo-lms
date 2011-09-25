<?php
require_once 'reports.lib.php';

reports_clearAll();
echo Database::error();
reports_build();
echo Database::error();
reports_addDBKeys();
echo Database::error();
?>
