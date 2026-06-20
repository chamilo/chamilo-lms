<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Enums\GradebookCalculationMode;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * Guards the new configurable-grading schema: the category calculation mode
 * (default WEIGHTED_AVERAGE, settable to POINTS_SUM) and the forum participation
 * points (pointsOne/pointsMany) must round-trip through Doctrine unchanged.
 *
 * If the enum mapping, default, or the new columns regress, these assertions fail.
 */
class GradebookCalculationModeTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCalculationModeDefaultsToWeightedAverage(): void
    {
        $category = new GradebookCategory();

        // A brand-new category must keep the legacy behavior so existing gradebooks are untouched.
        $this->assertSame(GradebookCalculationMode::WEIGHTED_AVERAGE, $category->getCalculationMode());
    }

    public function testPointsSumAndForumPointsRoundTrip(): void
    {
        $em = $this->getEntityManager();
        $course = $this->createCourse('points-sum');
        $owner = $this->createUser('rubric-owner');

        $category = (new GradebookCategory())
            ->setTitle('rubric')
            ->setUser($owner)
            ->setCourse($course)
            ->setWeight(100.00)
            ->setVisible(true)
            ->setCalculationMode(GradebookCalculationMode::POINTS_SUM)
        ;
        $this->assertHasNoEntityViolations($category);

        $forumLink = (new GradebookLink())
            ->setType(11) // LINK_FORUM_PARTICIPATION
            ->setRefId(1)
            ->setCourse($course)
            ->setCategory($category)
            ->setWeight(1.00)
            ->setVisible(1)
            ->setLocked(0)
            ->setPointsOne('1.5000')
            ->setPointsMany('2.1400')
        ;
        $this->assertHasNoEntityViolations($forumLink);

        $category->getLinks()->add($forumLink);

        $em->persist($category);
        $em->persist($forumLink);
        $em->flush();

        $categoryId = $category->getId();
        $linkId = $forumLink->getId();

        // Force a real reload from the database, not the identity-map instance.
        $em->clear();

        $reloadedCategory = $em->getRepository(GradebookCategory::class)->find($categoryId);
        $reloadedLink = $em->getRepository(GradebookLink::class)->find($linkId);

        $this->assertSame(GradebookCalculationMode::POINTS_SUM, $reloadedCategory->getCalculationMode());
        $this->assertSame('1.5000', $reloadedLink->getPointsOne());
        $this->assertSame('2.1400', $reloadedLink->getPointsMany());
    }
}
