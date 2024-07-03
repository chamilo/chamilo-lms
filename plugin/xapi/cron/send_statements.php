<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\SharedStatement;
use Xabbuh\XApi\Common\Exception\ConflictException;
use Xabbuh\XApi\Common\Exception\XApiException;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\Uuid;
use Xabbuh\XApi\Serializer\Symfony\Serializer;
use Xabbuh\XApi\Serializer\Symfony\StatementSerializer;

require_once __DIR__.'/../../../main/inc/global.inc.php';

if (php_sapi_name() !== 'cli') {
    exit;
}

echo 'XAPI: Cron to send statements.'.PHP_EOL;

$em = Database::getManager();
$serializer = Serializer::createSerializer();
$statementSerializer = new StatementSerializer($serializer);

$notSentSharedStatements = $em
    ->getRepository(SharedStatement::class)
    ->findBy(
        ['uuid' => null, 'sent' => false],
        null,
        100
    );
$countNotSent = count($notSentSharedStatements);

if ($countNotSent > 0) {
    echo '['.time().'] Trying to send '.$countNotSent.' statements to LRS'.PHP_EOL;

    $client = XApiPlugin::create()->getXapiStatementCronClient();

    /** @var SharedStatement $notSentSharedStatement */
    foreach ($notSentSharedStatements as $notSentSharedStatement) {
        $notSentStatement = $statementSerializer->deserializeStatement(
            json_encode($notSentSharedStatement->getStatement())
        );

        if (null == $notSentStatement->getId()) {
            $notSentStatement = $notSentStatement->withId(
                StatementId::fromUuid(Uuid::uuid4())
            );
        }

        try {
            echo '['.time()."] Sending shared statement ({$notSentSharedStatement->getId()})";

            $sentStatement = $client->storeStatement($notSentStatement);

            echo "\t\tStatement ID received: \"{$sentStatement->getId()->getValue()}\"";
        } catch (ConflictException $e) {
            echo $e->getMessage().PHP_EOL;

            continue;
        } catch (XApiException $e) {
            echo $e->getMessage().PHP_EOL;

            continue;
        }

        $notSentSharedStatement
            ->setUuid($sentStatement->getId()->getValue())
            ->setSent(true);

        $em->persist($notSentSharedStatement);

        echo "\t\tShared statement updated".PHP_EOL;
    }

    $em->flush();
} else {
    echo 'No statements to process.'.PHP_EOL;
}
