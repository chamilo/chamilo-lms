<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Xabbuh\XApi\Common\Exception\XApiException;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\StatementsFilter;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$request = HttpRequest::createFromGlobals();

$course = api_get_course_entity();
$session = api_get_session_entity();

if (!$request->isXmlHttpRequest()
    || !api_is_allowed_to_edit()
    || !$course
) {
    echo Display::return_message(get_lang('NotAllowed'), 'error');
    exit;
}

$plugin = XApiPlugin::create();
$em = Database::getManager();

$toolLaunch = $em->find(
    ToolLaunch::class,
    $request->request->getInt('tool')
);

$attempt = $request->request->get('attempt');

if (!$toolLaunch || !$attempt) {
    echo Display::return_message(get_lang('NoResults'), 'error');
    exit;
}

$cidReq = api_get_cidreq();

$xapiStatementClient = $plugin->getXApiStatementClient();

$activity = new Activity(
    IRI::fromString($toolLaunch->getActivityId())
);

$filter = new StatementsFilter();
$filter
    ->byRegistration($attempt);

try {
    $result = $xapiStatementClient->getStatements($filter);
} catch (XApiException $xApiException) {
    echo Display::return_message($xApiException->getMessage(), 'error');
    exit;
} catch (Exception $exception) {
    echo Display::return_message($exception->getMessage(), 'error');
    exit;
}

$statements = $result->getStatements();

if (count($statements) <= 0) {
    echo Display::return_message(get_lang('NoResults'), 'warning');
    exit;
}

$table = new HTML_Table(['class' => 'table table-condensed table-bordered table-striped table-hover']);
$table->setHeaderContents(0, 0, get_lang('CreatedAt'));
$table->setHeaderContents(0, 1, $plugin->get_lang('Actor'));
$table->setHeaderContents(0, 2, $plugin->get_lang('Verb'));
$table->setHeaderContents(0, 3, $plugin->get_lang('ActivityId'));

$i = 1;

$languageIso = api_get_language_isocode(api_get_interface_language());

foreach ($statements as $statement) {
    $timestampStored = $statement->getCreated() ? api_convert_and_format_date($statement->getCreated()) : '-';
    $actor = $statement->getActor()->getName();
    $verb = XApiPlugin::extractVerbInLanguage($statement->getVerb()->getDisplay(), $languageIso);
    $activity = '';

    $statementObject = $statement->getObject();

    if ($statementObject instanceof Activity) {
        if (null !== $statementObject->getDefinition()) {
            $definition = $statementObject->getDefinition();

            if (null !== $definition->getName()) {
                $activity = XApiPlugin::extractVerbInLanguage($definition->getName(), $languageIso).'<br>';
            }
        }

        $activity .= Display::tag(
            'small',
            $statementObject->getId()->getValue(),
            ['class' => 'text-muted']
        );
    }

    $table->setCellContents($i, 0, $timestampStored);
    $table->setCellContents($i, 1, $actor);
    $table->setCellContents($i, 2, $verb);
    $table->setCellContents($i, 3, $activity);

    $i++;
}

$table->display();
