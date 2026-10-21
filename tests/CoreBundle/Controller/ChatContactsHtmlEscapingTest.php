<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use SocialManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Regression test for the chat contact list advisory.
 *
 * The server builds the HTML of the contact list and the chat widget injects it as it is, so a
 * user who sets their own name to markup must not reach the screen of the people who list them.
 * The list is built by SocialManager::listMyFriendsBlock(), which takes the user explicitly.
 */
final class ChatContactsHtmlEscapingTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testContactNameIsEscapedInTheContactList(): void
    {
        self::bootKernel();
        Container::setContainer(self::getContainer());

        // The legacy helpers read the user and the language from the session of the current request.
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);

        Database::setManager($this->getEntityManager());

        $viewer = $this->createUser('chat_xss_viewer');
        $contact = $this->createUser('chat_xss_contact');
        $contact->setFirstname('<img src=x onerror=alert(1)>');
        $contact->setLastname('Contact"\'<b>');

        $em = $this->getEntityManager();
        $em->persist($contact);
        $em->persist(
            (new UserRelUser())
                ->setUser($viewer)
                ->setFriend($contact)
                ->setRelationType(UserRelUser::USER_RELATION_TYPE_FRIEND)
        );
        $em->flush();

        $html = SocialManager::listMyFriendsBlock((int) $viewer->getId(), '', true);

        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html, 'The name is shown, escaped.');
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertStringNotContainsString('<b>', $html);
    }
}
