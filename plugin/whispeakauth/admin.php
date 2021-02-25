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
$form->addHeader($plugin->get_lang('ActionRegistryPerUser'));
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
$form->addRule('users', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('date', get_lang('ThisFieldIsRequired'), 'required');

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

    $tableHeaders = [get_lang('DateTime'), get_lang('Type'), get_lang('Item'), get_lang('Result')];
    $tableData = [];

    foreach ($logEvents as $i => $logEvent) {
        $type = '';

        switch (get_class($logEvent)) {
            case LogEventQuiz::class:
                $type = get_lang('Question');
                break;
            case LogEventLp::class:
                $type = get_lang('LearningPath');
                break;
        }

        $tableData[] = [
            api_convert_and_format_date($logEvent->getDatetime(), DATE_TIME_FORMAT_SHORT),
            $type,
            $logEvent->getTypeString(),
            $logEvent->getActionStatus() === LogEvent::STATUS_SUCCESS
                ? Display::span(get_lang('Success'), ['class' => 'text-success'])
                : Display::span(get_lang('Failed'), ['class' => 'text-danger']),
        ];
    }

    $table = new HTML_Table(['class' => 'data_table table table-bordered table-hover table-striped table-condensed']);
    $table->setHeaders($tableHeaders);
    $table->setData($tableData);
    $table->updateColAttributes(0, ['class' => 'text-center']);
    $table->updateColAttributes(3, ['class' => 'text-center']);

    $pageContent .= Display::page_subheader($user->getCompleteNameWithUsername(), null, 'h4');
    $pageContent .= $table->toHtml();
}

$interbreadcrumb[] = [
    'name' => get_lang('Administration'),
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
];

$actionsLeft = '';

if (!empty($results)) {
    $actionsLeft = Display::url(
        Display::return_icon('back.png', $plugin->get_lang('Back'), [], ICON_SIZE_MEDIUM),
        api_get_self()
    );
}

$actionsRight = Display::url(
    Display::return_icon('delete_terms.png', $plugin->get_lang('Revocation'), [], ICON_SIZE_MEDIUM),
    'revocation.php'
);

$template = new Template($plugin->get_title());
$template->assign('actions', Display::toolbarAction('whispeak_admin', [$actionsLeft, $actionsRight]));
$template->assign(
    'content',
    $form->returnForm().PHP_EOL.$pageContent
);
$template->display_one_col_template();
