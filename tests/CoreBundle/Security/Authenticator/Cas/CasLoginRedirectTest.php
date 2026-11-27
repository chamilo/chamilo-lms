<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Security\Authenticator\Cas;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\Authenticator\Cas\CasAuthenticator;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * A CAS login must send the user where the password login does, not always to the home page.
 */
final class CasLoginRedirectTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testCasLoginUsesTheConfiguredProfileRedirect(): void
    {
        self::createClient();
        $this->setLoginRedirect('{"STUDENT":"sessions","COURSEMANAGER":"courses"}');
        $user = $this->createUser('cas_redirect_student');

        $user->setRoles(['ROLE_STUDENT']);
        self::assertSame('/sessions', $this->runOnAuthenticationSuccess($user)->getTargetUrl());

        $user->setRoles(['ROLE_TEACHER']);
        self::assertSame('/courses', $this->runOnAuthenticationSuccess($user)->getTargetUrl());
    }

    public function testCasLoginWithoutAProfileRedirectGoesToTheHomePage(): void
    {
        self::createClient();
        $this->setLoginRedirect('{"COURSEMANAGER":"courses"}');
        $user = $this->createUser('cas_redirect_none');
        $user->setRoles(['ROLE_STUDENT']);

        self::assertSame('/', $this->runOnAuthenticationSuccess($user)->getTargetUrl());
    }

    private function runOnAuthenticationSuccess(User $user): RedirectResponse
    {
        $authenticator = self::getContainer()->get(CasAuthenticator::class);

        $response = $authenticator->onAuthenticationSuccess(
            Request::create('/cas/check'),
            new PostAuthenticationToken($user, 'main', $user->getRoles()),
            'main',
        );
        $this->assertInstanceOf(RedirectResponse::class, $response);

        return $response;
    }

    private function setLoginRedirect(string $setting): void
    {
        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('registration.redirect_after_login', $setting)
        ;
    }
}
