<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Component\Badge;

use Chamilo\CoreBundle\Component\Badge\OpenBadge2;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

use const JSON_THROW_ON_ERROR;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
final class OpenBadge2Test extends TestCase
{
    public static array $settings = [];

    protected function setUp(): void
    {
        require_once __DIR__.'/fixtures/legacy_api.php';

        if (!\defined('WEB_CODE_PATH')) {
            \define('WEB_CODE_PATH', 'WEB_CODE_PATH');
        }
        if (!\defined('WEB_PATH')) {
            \define('WEB_PATH', 'WEB_PATH');
        }
        self::$settings = ['Institution' => 'Test issuer', 'emailAdministrator' => 'issuer@example.org'];
    }

    public function testCompleteAssertionUsesTheIssueIdAndDoesNotPublishTheRecipientEmail(): void
    {
        $issue = $this->issue();
        $assertion = OpenBadge2::assertion($issue);
        self::assertSame('https://w3id.org/openbadges/v2', $assertion['@context']);
        self::assertSame('https://example.org/main/skills/assertion.php?issue=42', $assertion['id']);
        self::assertSame('Assertion', $assertion['type']);
        self::assertSame('2026-10-28T12:00:00+00:00', $assertion['issuedOn']);
        self::assertSame(['type' => 'hosted'], $assertion['verification']);
        self::assertTrue($assertion['recipient']['hashed']);
        self::assertSame('sha256$'.hash('sha256', 'learner@example.org'.$assertion['recipient']['salt']), $assertion['recipient']['identity']);
        self::assertStringNotContainsString('learner@example.org', json_encode($assertion, JSON_THROW_ON_ERROR));
        self::assertSame(OpenBadge2::badge($issue->getSkill()), $assertion['badge']);
        self::assertSame(OpenBadge2::issuer(), $assertion['badge']['issuer']);
        self::assertSame(['narrative' => 'Pass the assessment.'], $assertion['badge']['criteria']);
        self::assertSame($assertion, OpenBadge2::assertion($issue));
    }

    public function testSeparateIssuesOfTheSameBadgeHaveSeparateIdentities(): void
    {
        $first = OpenBadge2::assertion($this->issue(42));
        $second = OpenBadge2::assertion($this->issue(43));
        self::assertNotSame($first['id'], $second['id']);
        self::assertNotSame($first['recipient']['identity'], $second['recipient']['identity']);
    }

    public function testRemovedIssuePublishesRevocationWithoutPersonalMetadata(): void
    {
        $issue = $this->issue()->setStatus(SkillRelUser::STATUS_REMOVED);
        $issue->getUser()->setEmail('invalid');
        self::$settings = [];
        $assertion = OpenBadge2::assertion($issue);
        self::assertTrue($assertion['revoked']);
        self::assertArrayNotHasKey('recipient', $assertion);
        self::assertArrayNotHasKey('badge', $assertion);
    }

    public function testOtherPortalsCannotServeAnIssueEvenWhenItIsRevoked(): void
    {
        $issue = $this->issue()->setStatus(SkillRelUser::STATUS_REMOVED);
        $issue->getSkill()->setAccessUrlId(2);
        $this->expectException(RuntimeException::class);
        OpenBadge2::assertion($issue);
    }

    public function testMissingSkillIsRejected(): void
    {
        $issue = $this->issue()->setSkill(null);
        $this->expectException(RuntimeException::class);
        OpenBadge2::assertion($issue);
    }

    public function testInvalidRecipientIsRejected(): void
    {
        $issue = $this->issue();
        $issue->getUser()->setEmail('invalid');
        $this->expectException(RuntimeException::class);
        OpenBadge2::assertion($issue);
    }

    public function testMissingCriteriaAreNotSilentlyInvented(): void
    {
        $issue = $this->issue();
        $issue->getSkill()->setCriteria('');
        $this->expectException(RuntimeException::class);
        OpenBadge2::assertion($issue);
    }

    public function testMissingIssuerEmailIsRejected(): void
    {
        self::$settings['emailAdministrator'] = '';
        $this->expectException(RuntimeException::class);
        OpenBadge2::issuer();
    }

    private function issue(int $id = 42): SkillRelUser
    {
        // Real entity methods, without constructors that initialize unrelated Doctrine collections.
        $skill = (new ReflectionClass(Skill::class))->newInstanceWithoutConstructor();
        (new ReflectionClass(Skill::class))->getProperty('id')->setValue($skill, 7);
        $skill->setTitle('Compétence')->setDescription('Demonstrated skill.')->setCriteria('Pass the assessment.')->setAccessUrlId(1);
        $user = (new ReflectionClass(User::class))->newInstanceWithoutConstructor();
        $user->setEmail('learner@example.org');
        $issue = (new ReflectionClass(SkillRelUser::class))->newInstanceWithoutConstructor();
        (new ReflectionClass(SkillRelUser::class))->getProperty('id')->setValue($issue, $id);
        $issue->setSkill($skill)->setUser($user)->setStatus(SkillRelUser::STATUS_ACQUIRED)->setAcquiredSkillAt(new DateTime('2026-10-28T12:00:00+00:00'));

        return $issue;
    }
}
