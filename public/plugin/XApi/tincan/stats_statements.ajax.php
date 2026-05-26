<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$request = Container::getRequest();

$course = api_get_course_entity();

if (
    !$request->isXmlHttpRequest()
    || !api_is_allowed_to_edit()
    || !$course
) {
    echo Display::return_message(get_lang('Not allowed'), 'error');
    exit;
}

$plugin = XApiPlugin::create();
$em = Database::getManager();

$toolLaunch = $em->find(
    XApiToolLaunch::class,
    $request->request->getInt('tool')
);

$attempt = trim((string) $request->request->get('attempt'));

if (!$toolLaunch || '' === $attempt) {
    echo Display::return_message(get_lang('No results found'), 'error');
    exit;
}

try {
    $statements = $plugin->fetchStatementsByRegistration(
        $attempt,
        $toolLaunch->getLrsUrl(),
        $toolLaunch->getLrsAuthUsername(),
        $toolLaunch->getLrsAuthPassword()
    );
} catch (Exception $exception) {
    echo Display::return_message($exception->getMessage(), 'error');
    exit;
}

if (empty($statements)) {
    echo Display::return_message(get_lang('No results found'), 'warning');
    exit;
}

usort(
    $statements,
    static function (array $left, array $right): int {
        $leftDate = $left['stored'] ?? $left['timestamp'] ?? $left['created'] ?? '';
        $rightDate = $right['stored'] ?? $right['timestamp'] ?? $right['created'] ?? '';

        return strtotime((string) $rightDate) <=> strtotime((string) $leftDate);
    }
);

$languageSource = function_exists('api_get_interface_language')
    ? api_get_interface_language()
    : api_get_setting('platformLanguage');

$languageIso = !empty($languageSource)
    ? api_get_language_isocode($languageSource)
    : 'en';

$table = new HTML_Table(['class' => 'table table-condensed table-bordered table-striped table-hover']);
$table->setHeaderContents(0, 0, get_lang('Created at'));
$table->setHeaderContents(0, 1, $plugin->get_lang('Actor'));
$table->setHeaderContents(0, 2, $plugin->get_lang('Verb'));
$table->setHeaderContents(0, 3, $plugin->get_lang('Activity ID'));

$row = 1;

foreach ($statements as $statement) {
    if (!is_array($statement)) {
        continue;
    }

    $timestampValue = '';
    foreach (['stored', 'timestamp', 'created'] as $candidate) {
        if (!empty($statement[$candidate]) && is_string($statement[$candidate])) {
            $timestampValue = $statement[$candidate];
            break;
        }
    }

    $timestampStored = '' !== $timestampValue
        ? api_convert_and_format_date($timestampValue, DATE_TIME_FORMAT_LONG)
        : '-';

    $actor = '-';
    if (!empty($statement['actor']) && is_array($statement['actor'])) {
        $actorData = $statement['actor'];

        if (!empty($actorData['name']) && is_string($actorData['name'])) {
            $actor = $actorData['name'];
        } elseif (!empty($actorData['mbox']) && is_string($actorData['mbox'])) {
            $actor = $actorData['mbox'];
        } elseif (!empty($actorData['account']['name']) && is_string($actorData['account']['name'])) {
            $actor = $actorData['account']['name'];
        }
    }

    $verb = '-';
    if (!empty($statement['verb']['display'])) {
        $verb = XApiPlugin::extractVerbInLanguage(
            $statement['verb']['display'],
            $languageIso
        );
    }

    $activity = '-';
    if (!empty($statement['object']) && is_array($statement['object'])) {
        $objectData = $statement['object'];
        $activityName = '';

        if (!empty($objectData['definition']['name'])) {
            $activityName = XApiPlugin::extractVerbInLanguage(
                $objectData['definition']['name'],
                $languageIso
            );
        }

        $activityId = !empty($objectData['id']) && is_string($objectData['id'])
            ? $objectData['id']
            : '';

        if ('' !== $activityName && '' !== $activityId) {
            $activity = $activityName.'<br>'.Display::tag(
                    'small',
                    $activityId,
                    ['class' => 'text-muted']
                );
        } elseif ('' !== $activityId) {
            $activity = Display::tag(
                'small',
                $activityId,
                ['class' => 'text-muted']
            );
        } elseif ('' !== $activityName) {
            $activity = $activityName;
        }
    }

    $table->setCellContents($row, 0, $timestampStored);
    $table->setCellContents($row, 1, $actor);
    $table->setCellContents($row, 2, $verb ?: '-');
    $table->setCellContents($row, 3, $activity);

    $row++;
}

if (1 === $row) {
    echo Display::return_message(get_lang('No results found'), 'warning');
    exit;
}

$table->display();
