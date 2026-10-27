<?php

/* For licensing terms, see /license.txt */

// Replaced by the list view of the Vue agenda.
$type = $_GET['type'] ?? null;
$query = [];

if ('personal' !== $type) {
    foreach (['cid', 'sid', 'gid'] as $key) {
        $value = (int) ($_GET[$key] ?? 0);
        if ($value > 0) {
            $query[$key] = $value;
        }
    }
}

if ('admin' === $type || 'platform' === $type) {
    $query['type'] = 'global';
}

header('Location: /resources/ccalendarevent/list'.($query ? '?'.http_build_query($query) : ''));
exit;
