<?php

/* For licensing terms, see /license.txt */

exit;

/**
 * Find LP progress = 100 and total_time = 0
 */

require_once '../../main/inc/global.inc.php';

$sql = '
SELECT lp_view_id, v.user_id, username, code, session_id, lp_id, progress, sum(total_time) total_time
FROM c_lp_view v
inner join c_lp_item_view vi on v.c_id = vi.c_id and v.iid = vi.lp_view_id
inner join course c on v.c_id = c.id
inner join user u on u.id = v.user_id
WHERE progress = 100
group by v.user_id, lp_id, v.c_id, session_id, lp_view_id
HAVING sum(total_time) = 0
ORDER BY code;';

$items = Database::store_result(
    Database::query($sql),
    'ASSOC'
);

$minutes = 18;
$seconds = $minutes*60;

$count = 1;
foreach ($items as $row) {
    echo $count.PHP_EOL;
    $lpViewId = $row['lp_view_id'];
    $sql = " SELECT iid, total_time, score from c_lp_item_view WHERE lp_view_id = $lpViewId";
    echo $sql.PHP_EOL;
    $data = Database::fetch_array(Database::query($sql));
    if ($data && $data['total_time'] == 0) {
        $iid = $data['iid'];
        $sql = "UPDATE c_lp_item_view SET total_time = $seconds WHERE iid = $iid";
        //Database::query($sql);
        echo $sql;
        echo PHP_EOL;
    }
    $count++;
}
