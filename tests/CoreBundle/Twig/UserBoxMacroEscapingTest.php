<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Twig;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;

/**
 * Regression test for the user information advisory.
 *
 * The reporting user box turns autoescape off for its whole body, so the name, e-mail, official
 * code and phone of a user must be escaped one by one: a user whose name or code holds markup
 * must not run it in the browser of the administrator who opens their information page.
 */
final class UserBoxMacroEscapingTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testUserDataIsEscapedInTheReportingUserBox(): void
    {
        Container::setContainer(self::getContainer());
        Database::setManager($this->getEntityManager());

        // The legacy helpers used by the filters read the language from the session of the request.
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);

        $user = $this->createUser('user_box_xss');
        $user->setFirstname('<img src=x onerror=alert(1)>');
        $user->setLastname('<svg onload=alert(2)>');
        $user->setOfficialCode('<script>alert(3)</script>');
        $user->setPhone('<b>555</b>');
        $user->setEmail('box"<i>@example.com');
        $this->getEntityManager()->flush();

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        $html = $twig->createTemplate(
            "{% import '@ChamiloCore/Macros/box.html.twig' as display %}{{ display.reporting_user_box_v2(user) }}"
        )->render(['user' => $user]);

        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html, 'The name is shown, escaped.');
        $this->assertStringContainsString('&lt;script&gt;alert(3)&lt;/script&gt;', $html, 'The code is shown, escaped.');
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertStringNotContainsString('<svg onload', $html);
        $this->assertStringNotContainsString('<script>alert(3)', $html);
        $this->assertStringNotContainsString('<b>555', $html);
        $this->assertStringNotContainsString('<i>@example.com', $html);
    }
}
