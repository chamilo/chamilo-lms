<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\WhispeakAuth\LogEvent;
use Chamilo\PluginBundle\Entity\WhispeakAuth\LogEventLp;
use Chamilo\PluginBundle\Entity\WhispeakAuth\LogEventQuiz;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = WhispeakAuthPlugin::create();

api_protect_admin_script(true);

$plugin->protectTool();

$form = new FormValidator('frm_filter', 'GET');
$slctUsers = $form->addSelectAjax(
    'users',
    get_lang('Users'),
    [],
    [
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
        'id' => 'user_id',
        'multiple' => true,
    ]
);
$form->addDatePicker('date', get_lang('Date'));
$form->addButtonSearch(get_lang('Search'));

$results = [];

if ($form->validate()) {
    $formValues = $form->exportValues();
    $userIds = $formValues['users'] ?: [];
    /** @var \DateTime $date */
    $starDate = api_get_utc_datetime($formValues['date'], true, true);
    $endDate = clone $starDate;
    $endDate->modify('next day');

    $em = Database::getManager();
    $repo = $em->getRepository('ChamiloPluginBundle:WhispeakAuth\LogEvent');

    foreach ($userIds as $userId) {
        $qb = $em->createQueryBuilder();
        $results[$userId] = $qb
            ->select('event')
            ->from('ChamiloPluginBundle:WhispeakAuth\LogEvent', 'event')
            ->where(
                $qb->expr()->gte('event.datetime', ':start_date')
            )
            ->andWhere(
                $qb->expr()->lt('event.datetime', ':end_date')
            )
            ->andWhere(
                $qb->expr()->eq('event.user', ':user')
            )
            ->setParameters(
                [
                    'start_date' => $starDate->format('Y-m-d H:i:s'),
                    'end_date' => $endDate->format('Y-m-d H:i:s'),
                    'user' => $userId,
                ]
            )
            ->getQuery()
            ->getResult();
    }
}

$pageContent = '';

/**
 * @var int              $userId
 * @var array|LogEvent[] $logEvents
 */
foreach ($results as $userId => $logEvents) {
    if (empty($logEvents)) {
        continue;
    }

    $user = $logEvents[0]->getUser();

    $slctUsers->addOption($user->getCompleteNameWithUsername(), $user->getId());

    $table = new HTML_Table(['class' => 'table table-hover']);
    $table->setHeaderContents(0, 0, get_lang('DateTime'));
    $table->setHeaderContents(0, 1, get_lang('Type'));
    $table->setHeaderContents(0, 2, get_lang('Status'));

    foreach ($logEvents as $i => $logEvent) {
        $row = $i + 1;

        $type = '';

        switch (get_class($logEvent)) {
            case LogEventQuiz::class:
                $type = '<span class="label label-info">'.get_lang('Question').'</span>'.PHP_EOL;
                break;
            case LogEventLp::class:
                $type = '<span class="label label-info">'.get_lang('LearningPath').'</span>'.PHP_EOL;
                break;
        }

        $table->setCellContents(
            $row,
            0,
            [
                api_convert_and_format_date($logEvent->getDatetime(), DATE_TIME_FORMAT_SHORT),
                $type.PHP_EOL.$logEvent->getTypeString(),
                $logEvent->getActionStatus() === LogEvent::STATUS_SUCCESS ? get_lang('Success') : get_lang('Failed'),
            ]
        );
    }

    $table->updateColAttributes(0, ['class' => 'text-center']);
    $table->updateColAttributes(2, ['class' => 'text-center']);

    $pageContent .= Display::page_header($user->getCompleteNameWithUsername());
    $pageContent .= $table->toHtml();
}

$template = new Template($plugin->get_title());
$template->assign(
    'content',
    $form->returnForm().PHP_EOL.$pageContent
);
$template->display_one_col_template();
