<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

use const JSON_THROW_ON_ERROR;

/**
 * Reads the platform's course-setting policies from the main access URL.
 * This deliberately bypasses session-cached settings: a new request must see
 * newly imposed restrictions, including when served from another access URL.
 */
final class CourseSettingPolicyManager
{
    /**
     * @var null|array<string, array{editable: bool, default: string}>
     */
    private ?array $cachedPolicies = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * @return array<string, array{editable: bool, default: string}>
     */
    public function getPolicies(): array
    {
        if (null !== $this->cachedPolicies) {
            return $this->cachedPolicies;
        }

        $row = $this->findRow();

        return $this->cachedPolicies = CourseSettingPolicy::parse($row?->getSelectedValue());
    }

    public function isEditable(string $variable, ?string $category = null): bool
    {
        return CourseSettingPolicy::isEditable($this->getPolicies(), $variable, $category);
    }

    public function effectiveValue(
        string $variable,
        mixed $courseValue,
        mixed $originalDefault,
        ?string $category = null,
    ): mixed {
        return CourseSettingPolicy::effectiveValue($this->getPolicies(), $variable, $courseValue, $originalDefault, $category);
    }

    /**
     * @param array<string, array{editable: bool, default: string}> $policies
     */
    public function savePolicies(array $policies): void
    {
        $mainUrl = $this->getMainUrl();
        $row = $this->findRow();
        if (null === $row) {
            $row = (new SettingsCurrent())
                ->setUrl($mainUrl)
                ->setVariable(CourseSettingPolicy::SETTING)
                ->setCategory('course')
                ->setTitle('Course settings policies')
                ->setComment('Control whether course settings can be changed and define their platform default.')
                ->setAccessUrlChangeable(0)
                ->setAccessUrlLocked(1)
            ;
        }

        $row->setSelectedValue(json_encode($policies, JSON_THROW_ON_ERROR));
        $this->entityManager->persist($row);
        $this->entityManager->flush();
        $this->cachedPolicies = $policies;
    }

    public function getMainUrlId(): int
    {
        return (int) $this->getMainUrl()->getId();
    }

    private function getMainUrl(): AccessUrl
    {
        $url = $this->entityManager->getRepository(AccessUrl::class)->find(1);
        if (!$url instanceof AccessUrl) {
            throw new RuntimeException('Main access URL is not configured.');
        }

        return $url;
    }

    private function findRow(): ?SettingsCurrent
    {
        return $this->entityManager->getRepository(SettingsCurrent::class)->findOneBy([
            'url' => $this->getMainUrl(),
            'category' => 'course',
            'variable' => CourseSettingPolicy::SETTING,
        ]);
    }
}
