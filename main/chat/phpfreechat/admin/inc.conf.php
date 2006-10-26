<?php
session_start();
$lang = isset($_POST["lang"]) ? $_POST["lang"] : (isset($_SESSION["lang"]) ? $_SESSION["lang"] : "en_US" ); $_SESSION["lang"] = $lang;
?>