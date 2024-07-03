<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$test = true;

$em = Database::getManager();
$table = Database::get_main_table(TABLE_MAIN_SESSION);
$sql = "SELECT * FROM $table ";
$result = Database::query($sql);
$now = api_get_utc_datetime();

$line = PHP_SAPI === 'cli' ? PHP_EOL : '<br />';
echo 'Today is : '.$now.$line;

while ($session = Database::fetch_array($result, 'ASSOC')) {
    $id = $session['id'];
    $start = $session['display_start_date'];
    $end = $session['display_end_date'];
    //$userCount = (int) $session['nbr_users'];
    $userCount = (int) SessionManager::get_users_by_session($id, 0, true);

    // 1. Si une session a lieu dans le futur, c’est à dire que la date de début est supérieur à la date du
    //jour alors elle est prévue
    $status = 0;
    if ($start > $now) {
        $status = SessionManager::STATUS_PLANNED;
    }

    // 2. Si une session a plus de 2 apprenants et que la date de début est inférieur ou égale à la date
    // du jour et que la date de fin n'est pas passée alors mettre le statut en cours
    if ($userCount >= 2 && $start <= $now && $end > $now) {
        $status = SessionManager::STATUS_PROGRESS;
    }

    // 3. Si une session n’a pas d’apprenant et que la date de début est passée alors mettre le statut à
    //annulée.
    if ($userCount === 0 && $now > $start) {
        $status = SessionManager::STATUS_CANCELLED;
    }

    // 4. Si la date de fin d'une session est dépassée et qu'elle a plus de 2 apprenants alors passer le
    //statut à terminée
    if ($now > $end && $userCount >= 2) {
        $status = SessionManager::STATUS_FINISHED;
    }

    $params = [
        'status' => $status,
    ];
    if ($test != true) {
        Database::update($table, $params, ['id = ?' => $id]);
    }

    echo "Session #$id updated. Status = ".SessionManager::getStatusLabel($status)."($status) User count= $userCount: Start date: $start - End date: $end".$line;
}
