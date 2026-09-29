<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security;

use Chamilo\CoreBundle\Framework\Container as LegacyContainer;
use Chamilo\CoreBundle\Security\PasswordPolicyValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Security::getPasswordRequirements() (called internally by PasswordPolicyValidator) reads
 * the "security.password_requirements" setting through the legacy Container bridge
 * (Chamilo\CoreBundle\Framework\Container), which outside an HTTP request is normally
 * populated by LegacyListener on kernel.request -- a plain KernelTestCase never fires that,
 * so it is set manually here, the same way non-HTTP console commands
 * (e.g. SendNotificationsCommand) already do. With no custom override configured, it falls
 * back to Chamilo's classic defaults: min length 5, 2 digits, 1 special character, no case
 * requirements.
 */
final class PasswordPolicyValidatorTest extends KernelTestCase
{
    private function getValidator(): PasswordPolicyValidator
    {
        self::bootKernel();
        LegacyContainer::setContainer(self::getContainer());

        /** @var PasswordPolicyValidator $validator */
        return self::getContainer()->get(PasswordPolicyValidator::class);
    }

    private function getTranslator(): TranslatorInterface
    {
        return self::getContainer()->get(TranslatorInterface::class);
    }

    public function testPasswordMeetingDefaultRequirementsHasNoViolations(): void
    {
        $validator = $this->getValidator();

        $this->assertSame([], $validator->validate('abcd12#'));
    }

    public function testPasswordTooShortIsRejected(): void
    {
        $validator = $this->getValidator();

        $errors = $validator->validate('a1#');
        $expected = $this->getTranslator()->trans('Password must be at least %length% characters long.', ['%length%' => 5]);

        $this->assertNotEmpty($errors);
        $this->assertSame($expected, $errors[0]);
    }

    public function testPasswordWithoutEnoughDigitsIsRejected(): void
    {
        $validator = $this->getValidator();

        $errors = $validator->validate('abcdef#');
        $expected = $this->getTranslator()->trans('Password must contain at least %count% numerical (0-9) characters.', ['%count%' => 2]);

        $this->assertSame([$expected], $errors);
    }

    public function testPasswordWithoutASpecialCharacterIsRejected(): void
    {
        $validator = $this->getValidator();

        $errors = $validator->validate('abcd12');
        $expected = $this->getTranslator()->trans('Password must contain at least %count% special characters.', ['%count%' => 1]);

        $this->assertSame([$expected], $errors);
    }
}
