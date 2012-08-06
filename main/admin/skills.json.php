<?php
require '../inc/global.inc.php';
$skills = new Skill();
//$all = $skills->get_all(false,false,null,0);
$all = $skills->get_skills_tree_json();
echo $all;
