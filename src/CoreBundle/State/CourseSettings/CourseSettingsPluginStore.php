<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSettings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Settings\CourseSettingPolicyManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Default persistence for third-party course settings sections.
 * Complex plugins may override the provider's getValues/saveValues to use their own tables.
 */
final readonly class CourseSettingsPluginStore
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CourseSettingPolicyManager $policies,
    ) {}

    public function get(Course $course, string $key, mixed $default = ''): mixed
    {
        $items = $this->entityManager->getRepository(CCourseSetting::class)->findBy(
            ['cId' => (int) $course->getId(), 'variable' => $key],
            ['iid' => 'ASC'],
        );
        $stored = null;
        foreach ($items as $item) {
            if ($item instanceof CCourseSetting && null !== $item->getValue() && '-1' !== $item->getValue()) {
                $stored = $item->getValue();

                break;
            }
        }

        return $this->policies->effectiveValue($key, $stored, $default, 'plugin');
    }

    public function save(Course $course, string $key, mixed $value): void
    {
        if (!$this->policies->isEditable($key, 'plugin')) {
            return;
        }
        $value = \is_bool($value) ? ($value ? '1' : '0') : (\is_array($value) ? implode(',', array_map('strval', $value)) : (string) $value);
        $items = $this->entityManager->getRepository(CCourseSetting::class)->findBy(
            ['cId' => (int) $course->getId(), 'variable' => $key],
            ['iid' => 'ASC'],
        );
        if ([] === $items) {
            $this->entityManager->persist((new CCourseSetting())
                ->setCId((int) $course->getId())
                ->setVariable($key)
                ->setTitle($key)
                ->setCategory('plugin')
                ->setValue($value));

            return;
        }
        foreach ($items as $item) {
            if (!$item instanceof CCourseSetting) {
                continue;
            }
            $item->setValue($value);
            if (null === $item->getCategory() || '' === $item->getCategory()) {
                $item->setCategory('plugin');
            }
            $this->entityManager->persist($item);
        }
    }
}
