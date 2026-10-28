<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Component\Badge;

use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Framework\Container;
use DateTimeInterface;
use RuntimeException;

use const FILTER_VALIDATE_EMAIL;

final class OpenBadge2
{
    public static function assertionUrl(SkillRelUser $issue): string
    {
        return api_get_path(WEB_CODE_PATH).'skills/assertion.php?issue='.(int) $issue->getId();
    }

    public static function assertCurrentSite(Skill $skill): void
    {
        if ($skill->getAccessUrlId() !== (int) api_get_current_access_url_id()) {
            throw new RuntimeException('The badge does not belong to this site.');
        }
    }

    public static function issuer(): array
    {
        $name = trim((string) api_get_setting('Institution'));
        if ('' === $name) {
            throw new RuntimeException('Configure the institution name before issuing badges.');
        }
        $issuer = [
            '@context' => 'https://w3id.org/openbadges/v2',
            'id' => api_get_path(WEB_CODE_PATH).'skills/issuer.php',
            'type' => 'Profile',
            'name' => $name,
            'url' => api_get_path(WEB_PATH),
        ];
        $email = trim((string) api_get_setting('emailAdministrator'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Configure a valid institution contact email before issuing badges.');
        }
        $issuer['email'] = $email;

        return $issuer;
    }

    public static function badge(Skill $skill): array
    {
        self::assertCurrentSite($skill);
        $name = trim(strip_tags($skill->getTitle()));
        $description = trim(strip_tags($skill->getDescription()));
        if ('' === $name || '' === $description) {
            throw new RuntimeException('Configure the badge title and description before issuing it.');
        }
        $base = api_get_path(WEB_CODE_PATH).'skills/';
        $criteria = trim(strip_tags((string) $skill->getCriteria()));
        if ('' === $criteria) {
            throw new RuntimeException('Configure the badge criteria before issuing it.');
        }

        return [
            '@context' => 'https://w3id.org/openbadges/v2',
            'id' => $base.'class.php?id='.(int) $skill->getId(),
            'type' => 'BadgeClass',
            'name' => $name,
            'description' => $description,
            'image' => $base.'image.php?id='.(int) $skill->getId(),
            'criteria' => ['narrative' => $criteria],
            'issuer' => self::issuer(),
        ];
    }

    public static function assertion(SkillRelUser $issue): array
    {
        $skill = $issue->getSkill();
        if (null === $skill || null === $issue->getId()) {
            throw new RuntimeException('The badge issue is incomplete.');
        }
        self::assertCurrentSite($skill);
        $assertion = [
            '@context' => 'https://w3id.org/openbadges/v2',
            'id' => self::assertionUrl($issue),
            'type' => 'Assertion',
        ];
        if (SkillRelUser::STATUS_ACQUIRED !== $issue->getStatus()) {
            return $assertion + ['revoked' => true];
        }
        $email = trim((string) $issue->getUser()?->getEmail());
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid recipient email is required to issue a badge.');
        }
        // Public per-issue salt keeps the hosted and baked representations consistent.
        $salt = hash('sha256', self::assertionUrl($issue));

        return $assertion + [
            'recipient' => [
                'type' => 'email',
                'hashed' => true,
                'salt' => $salt,
                'identity' => 'sha256$'.hash('sha256', $email.$salt),
            ],
            'issuedOn' => $issue->getAcquiredSkillAt()->format(DateTimeInterface::ATOM),
            'verification' => ['type' => 'hosted'],
            'badge' => self::badge($skill),
        ];
    }

    public static function image(Skill $skill): string
    {
        self::assertCurrentSite($skill);
        if ($skill->hasAsset()) {
            $content = Container::getAssetRepository()->getAssetContent($skill->getAsset());
        } else {
            $content = @file_get_contents(api_get_path(SYS_PUBLIC_PATH).'img/icons/32/badges-default.png');
        }
        if (!\is_string($content) || '' === $content) {
            throw new RuntimeException('The badge image is unavailable.');
        }
        OpenBadgePng::validate($content);

        return $content;
    }
}
