<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\State;
use Xabbuh\XApi\Model\Uuid;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);

$request = HttpRequest::createFromGlobals();

$originIsLearnpath = api_get_origin() === 'learnpath';

$user = api_get_user_entity(api_get_user_id());

$em = Database::getManager();

$toolLaunch = $em->find(
    ToolLaunch::class,
    $request->query->getInt('id')
);

if (null === $toolLaunch
    || $toolLaunch->getCourse()->getId() !== api_get_course_entity()->getId()
) {
    api_not_allowed(true);
}

$plugin = XApiPlugin::create();

$activity = new Activity(
    IRI::fromString($toolLaunch->getActivityId())
);
$actor = new Agent(
    InverseFunctionalIdentifier::withMbox(
        IRI::fromString('mailto:'.$user->getEmail())
    ),
    $user->getCompleteName()
);
$state = new State(
    $activity,
    $actor,
    $plugin->generateIri('tool-'.$toolLaunch->getId(), 'state')->getValue()
);

$cidReq = api_get_cidreq();

try {
    $stateDocument = $plugin
        ->getXApiStateClient(
            $toolLaunch->getLrsUrl(),
            $toolLaunch->getLrsAuthUsername(),
            $toolLaunch->getLrsAuthPassword()
        )
        ->getDocument($state);
} catch (NotFoundException $notFoundException) {
    $stateDocument = null;
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message($exception->getMessage(), 'error')
    );

    header('Location: '.api_get_course_url());
    exit;
}

$formTarget = $originIsLearnpath ? '_self' : '_blank';

$frmNewRegistration = new FormValidator(
    'launch_new',
    'post',
    "launch.php?$cidReq",
    '',
    ['target' => $formTarget],
    FormValidator::LAYOUT_INLINE
);
$frmNewRegistration->addHidden('attempt_id', Uuid::uuid4());
$frmNewRegistration->addHidden('id', $toolLaunch->getId());
$frmNewRegistration->addButton(
    'submit',
    $plugin->get_lang('LaunchNewAttempt'),
    'external-link fa-fw',
    'success'
);

if ($stateDocument) {
    $row = 0;

    $table = new HTML_Table(['class' => 'table table-hover table-striped']);
    $table->setHeaderContents($row, 0, $plugin->get_lang('ActivityFirstLaunch'));
    $table->setHeaderContents($row, 1, $plugin->get_lang('ActivityLastLaunch'));
    $table->setHeaderContents($row, 2, get_lang('Actions'));

    $row++;

    $langActivityLaunch = $plugin->get_lang('ActivityLaunch');

    foreach ($stateDocument->getData()->getData() as $attemptId => $attempt) {
        $firstLaunch = api_convert_and_format_date(
            $attempt[XApiPlugin::STATE_FIRST_LAUNCH],
            DATE_TIME_FORMAT_LONG
        );
        $lastLaunch = api_convert_and_format_date(
            $attempt[XApiPlugin::STATE_LAST_LAUNCH],
            DATE_TIME_FORMAT_LONG
        );

        $frmLaunch = new FormValidator(
            "launch_$row",
            'post',
            "launch.php?$cidReq",
            '',
            ['target' => $formTarget],
            FormValidator::LAYOUT_INLINE
        );
        $frmLaunch->addHidden('attempt_id', $attemptId);
        $frmLaunch->addHidden('id', $toolLaunch->getId());
        $frmLaunch->addButton(
            'submit',
            $langActivityLaunch,
            'external-link fa-fw',
            'default'
        );

        $table->setCellContents($row, 0, $firstLaunch);
        $table->setCellContents($row, 1, $lastLaunch);
        $table->setCellContents($row, 2, $frmLaunch->returnForm());

        $row++;
    }

    $table->setColAttributes(0, ['class' => 'text-center']);
    $table->setColAttributes(1, ['class' => 'text-center']);
    $table->setColAttributes(2, ['class' => 'text-center']);
}

$interbreadcrumb[] = ['url' => '../start.php', 'name' => $plugin->get_lang('ToolTinCan')];

$pageTitle = $toolLaunch->getTitle();
$pageContent = '';

if ($toolLaunch->getDescription()) {
    $pageContent .= PHP_EOL;
    $pageContent .= "<p class='lead'>{$toolLaunch->getDescription()}</p>";
}

if ($toolLaunch->isAllowMultipleAttempts()
    || empty($stateDocument)
) {
    $pageContent .= Display::div(
        $frmNewRegistration->returnForm(),
        ['class' => 'exercise_overview_options']
    );
}

if ($stateDocument) {
    $pageContent .= $table->toHtml();
}

$actions = '';

if (!$originIsLearnpath) {
    $actions = Display::url(
        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
        '../start.php?'.api_get_cidreq()
    );
}

$view = new Template($pageTitle);
$view->assign('header', $pageTitle);

if ($actions) {
    $view->assign(
        'actions',
        Display::toolbarAction(
            'xapi_actions',
            [$actions]
        )
    );
}
$view->assign('content', $pageContent);
$view->display_one_col_template();
