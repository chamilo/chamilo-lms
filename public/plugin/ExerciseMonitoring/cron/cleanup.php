<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\TrackEExerciseRepository;
use Chamilo\PluginBundle\ExerciseMonitoring\Entity\Log;
use Chamilo\PluginBundle\ExerciseMonitoring\Repository\LogRepository;
use League\Flysystem\FilesystemOperator;

require_once __DIR__.'/../../../main/inc/global.inc.php';

if ('cli' !== PHP_SAPI) {
    exit('For security reasons, this script can only be launched from cron or from the command line');
}

//exit;

$plugin = ExerciseMonitoringPlugin::create();
$em = Database::getManager();
/** @var LogRepository $repo */
$repo = $em->getRepository(Log::class);
/** @var TrackEExerciseRepository $trackExeRepo */
$trackExeRepo = $em->getRepository(TrackEExercise::class);

$lifetimeDays = (int) $plugin->get(ExerciseMonitoringPlugin::SETTING_SNAPSHOTS_LIFETIME);

if (empty($lifetimeDays)) {
    logging("There is no set time limit");
    exit;
}

$timeLimit = api_get_utc_datetime(null, false, true);
$timeLimit->modify("-$lifetimeDays day");

logging(
    sprintf("Deleting snapshots taken before than %s", $timeLimit->format('Y-m-d H:i:s'))
);

/** @var FilesystemOperator $pluginsFilesystem */
$pluginsFilesystem = Container::$container->get('oneup_flysystem.plugins_filesystem');

$logs = findLogsBeforeThan($timeLimit);

foreach ($logs as $log) {
    if (!$pluginsFilesystem->fileExists($log['image_filename'])) {
        logging(
            sprintf("File %s not exists", $log['image_filename'])
        );

        continue;
    }

    $pluginsFilesystem->delete($log['image_filename']);

    Database::update(
        'plugin_exercisemonitoring_log',
        ['removed' => true],
        ['id = ?' => $log['log_id']]
    );

    logging(
        sprintf(
            "From exe_id %s; deleting filename %s created at %s",
            $log['exe_id'],
            $log['image_filename'],
            $log['created_at']
        )
    );
}

function findLogsBeforeThan(DateTime $timeLimit): array
{
    $sql = "SELECT tee.exe_id, l.id AS log_id, l.image_filename, tee.exe_user_id
        FROM plugin_exercisemonitoring_log l
        INNER JOIN chamilo.track_e_exercises tee on l.exe_id = tee.exe_id
        WHERE l.created_at <= '".$timeLimit->format('Y-m-d H:i:s')."'
            AND l.removed IS FALSE";

    $result = Database::query($sql);

    $rows = [];

    while ($row = Database::fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

function logging(string $message): void
{
    $time = time();

    printf("[%s] %s \n", $time, $message);
}
