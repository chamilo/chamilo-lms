<?php

/* For licensing terms, see /license.txt */

// Replaced by the sessions plan of the Vue agenda.
$query = empty($_GET['year']) ? '' : '?year='.(int) $_GET['year'];

header('Location: /resources/ccalendarevent/sessions-plan'.$query);
exit;
