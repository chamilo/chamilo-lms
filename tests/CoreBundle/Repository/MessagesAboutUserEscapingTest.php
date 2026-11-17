<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use MessageManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * The follow-up messages written about a learner are shown to whoever opens the learner's
 * tracking page (other teachers, student bosses, HR managers) and to the learner. The sender
 * must not be able to run markup in those browsers.
 */
final class MessagesAboutUserEscapingTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testSubjectIsEscapedAndBodyIsSanitized(): void
    {
        Container::setContainer(self::getContainer());
        Database::setManager($this->getEntityManager());

        // The legacy helpers read the user and the language from the session of the current request.
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);

        $sender = $this->createUser('followup_sender');
        $learner = $this->createUser('followup_learner');

        $message = (new Message())
            ->setTitle('<img src=x onerror="subjectProbe()">')
            ->setContent('<p>Keep <strong>practicing</strong></p><img src=x onerror="bodyProbe()"><script>scriptProbe()</script>')
            ->setSender($sender)
            ->addReceiverTo($learner)
            ->setMsgType(Message::MESSAGE_TYPE_CONVERSATION)
        ;
        $em = $this->getEntityManager();
        $em->persist($message);
        $em->flush();

        $html = MessageManager::getMessagesAboutUserToString($learner);

        $this->assertStringContainsString('&lt;img src=x onerror=&quot;subjectProbe()&quot;&gt;', $html, 'The subject is plain text, shown escaped.');
        $this->assertStringContainsString('<strong>practicing</strong>', $html, 'The body keeps its formatting.');
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertStringNotContainsString('bodyProbe', $html);
        $this->assertStringNotContainsString('scriptProbe', $html);
    }
}
