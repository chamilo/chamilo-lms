<?php

/* For licensing terms, see /license.txt */

// Replaced by the Vue agenda.
$type = $_GET['type'] ?? null;
$query = [];

if ('admin' === $type || 'platform' === $type) {
    $query['type'] = 'global';
} elseif ('personal' !== $type) {
    foreach (['cid', 'sid', 'gid'] as $key) {
        $value = (int) ($_GET[$key] ?? 0);
        if ($value > 0) {
            $query[$key] = $value;
        }
    }
}

header('Location: /resources/ccalendarevent'.($query ? '?'.http_build_query($query) : ''));
exit;
