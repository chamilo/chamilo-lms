<?php

declare(strict_types=1);

// Only Chamilo 1.11.16

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(\E_ALL);

use Chamilo\PluginBundle\Entity\XApi\SharedStatement;
use Xabbuh\XApi\Model\Account;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\Context;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Result;
use Xabbuh\XApi\Model\Score;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\Uuid;
use Xabbuh\XApi\Model\Verb;
use Xabbuh\XApi\Serializer\Symfony\Serializer;
use Xabbuh\XApi\Serializer\Symfony\StatementSerializer;

require_once __DIR__.'/../../../../main/inc/global.inc.php';

echo '<h2>CRON XAPI 11.11.16</h2>';

// sendStudioSharedStatement();

function sendStudioSharedStatement(): void
{
    $plugin = XApiPlugin::create();
    $user = api_get_user_entity(api_get_user_id());
    $nowDate = api_get_utc_datetime(null, false, true)->format('c');
    $statementUrl = api_get_path(WEB_PATH);

    $registration = (string) Uuid::uuid4();

    $actor = new Agent(
        InverseFunctionalIdentifier::withAccount(
            new Account(
                $user->getCompleteName(),
                IRL::fromString($statementUrl)
            )
        ),
        $user->getCompleteName()
    );

    $verb = new Verb(
        IRI::fromString('http://adlnet.gov/expapi/verbs/interacted'),
        LanguageMap::create($plugin->getLangMap('interacted'))
    );

    /*IRI  -  The IRI used to identify this in an xAPI statement.
    http://adlnet.gov/expapi/verbs/interacted
    CONCEPT TYPE
    Verb
    CONCEPT NAME  -  English (en)
    interacted
    DESCRIPTION  -  English (en)
    Indicates the actor engaged with a physical or virtual object.*/

    $ResultAction = new Result(
        null,
        true,
        null,
        'edit page',
        null
    );

    $item = null;

    $customActivityId = $plugin->generateIri('interacted', 'studiotools');

    $activity = new Activity(
        $customActivityId,
        new Definition(
            LanguageMap::create(['Edit page 1']),
            LanguageMap::create(['Add column', 'Delete textblock', 'Add image'])
        )
    );

    $context = (new Context())
        ->withPlatform(
            api_get_setting('Institution').' - '.api_get_setting('siteName')
        )
        ->withLanguage(api_get_language_isocode())
        ->withRegistration($registration)
    ;

    $statementUuid = Uuid::uuid4();

    // $statementUuid = Uuid::uuid5(
    // $plugin->get(XApiPlugin::SETTING_UUID_NAMESPACE),
    // "studio"
    // );

    $statement = new Statement(
        StatementId::fromUuid($statementUuid),
        $actor,
        $verb,
        $activity,
        $ResultAction,
        null,
        api_get_utc_datetime(null, false, true),
        null,
        $context
    );

    // protected function saveSharedStatement(Statement $statement)

    $statementSerialized = serializeStatement($statement);

    $sharedStmt = new SharedStatement(
        json_decode($statementSerialized, true)
    );

    echo '<hr>';
    print_r($statementSerialized);

    $em = Database::getManager();
    $em->persist($sharedStmt);
    $em->flush();
    // echo '$em->commit();'.'<br>';
    // $em->commit();
}

function sendLogStudioToSharedStatement($user_id, $verbName, $title, $def, $result, $response, $date_event, $modetest)
{
    $plugin = XApiPlugin::create();
    $user = api_get_user_entity($user_id);
    $statementUrl = api_get_path(WEB_PATH);

    $registration = (string) Uuid::uuid4();

    $actor = new Agent(
        InverseFunctionalIdentifier::withAccount(
            new Account(
                $user->getCompleteName(),
                IRL::fromString($statementUrl)
            )
        ),
        $user->getCompleteName()
    );

    $verb = new Verb(
        IRI::fromString('http://adlnet.gov/expapi/verbs/'.$verbName),
        LanguageMap::create($plugin->getLangMap($verbName))
    );

    $stat_score = new Score(
        0,
        0,
        0,
        100
    );
    $stat_completion = false;

    if (true == $result || 1 == $result) {
        $stat_score = new Score(
            1,
            100,
            0,
            100
        );
        $stat_completion = true;
    }

    $ResultAction = new Result(
        $stat_score,
        $stat_completion,
        null,
        $response,
        null
    );

    $item = null;

    $customActivityId = $plugin->generateIri($verbName, 'studiotools');

    $activity = new Activity(
        $customActivityId,
        new Definition(
            LanguageMap::create([$title]),
            LanguageMap::create([$def])
        )
    );

    $context = (new Context())
        ->withPlatform(
            api_get_setting('Institution').' - '.api_get_setting('siteName')
        )
        ->withLanguage(api_get_language_isocode())
        ->withRegistration($registration)
    ;

    $statementUuid = Uuid::uuid4();

    // $statementUuid = Uuid::uuid5(
    //    $plugin->get(XApiPlugin::SETTING_UUID_NAMESPACE),
    //    "studio"
    // );

    $createdDateTime = api_get_utc_datetime(date('Y-m-d H:i:s', $date_event), false, true);
    // $createdDateTime = null;

    $statement = new Statement(
        StatementId::fromUuid($statementUuid),
        $actor,
        $verb,
        $activity,
        $ResultAction,
        null,
        $createdDateTime,
        api_get_utc_datetime(null, false, true),
        $context
    );

    // protected function saveSharedStatement(Statement $statement)

    $statementSerialized = serializeStatement($statement);

    $sharedStmt = new SharedStatement(
        json_decode($statementSerialized, true)
    );

    if ($modetest) {
        return print_r($statementSerialized, true).'<hr>';
    }
    $em = Database::getManager();
    $em->persist($sharedStmt);
    $em->flush();

    return true;
    // echo '$em->commit();'.'<br>';
    // $em->commit();
}

function serializeStatement(Statement $statement)
{
    $serializer = Serializer::createSerializer();
    $statementSerializer = new StatementSerializer($serializer);

    return $statementSerializer->serializeStatement($statement);
}
