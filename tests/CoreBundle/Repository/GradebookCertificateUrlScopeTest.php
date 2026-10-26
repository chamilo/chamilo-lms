<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * The completed, incomplete and restartable pages of the administration (admin-dashboard/completed, ...)
 * list the learners of the current access URL. A course can be shared between URLs, so the users of
 * another URL must not be listed.
 */
final class GradebookCertificateUrlScopeTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private AccessUrl $mainUrl;
    private AccessUrl $otherUrl;
    private Course $course;
    private User $mainUrlLearner;
    private User $otherUrlLearner;

    public function testOnlyTheCertificatesOfTheCurrentUrlUsersAreListed(): void
    {
        $category = $this->createCategory($this->course);
        $this->createCertificate($category, $this->mainUrlLearner);
        $this->createCertificate($category, $this->otherUrlLearner);

        $this->assertSame(
            [$this->mainUrlLearner->getId()],
            $this->certificateLearnerIds($this->repository()->findCertificatesWithContext($this->mainUrl->getId(), 0, 1000))
        );
        $this->assertSame(
            [$this->otherUrlLearner->getId()],
            $this->certificateLearnerIds($this->repository()->findCertificatesWithContext($this->otherUrl->getId(), 0, 1000))
        );
    }

    public function testOnlyTheCurrentUrlUsersAreListedAsIncomplete(): void
    {
        $session = $this->createSessionWithBothLearners(new DateTime('-1 day'), null);

        $learnerIds = fn (int $urlId): array => array_values(array_unique(array_map(
            static fn (SessionRelUser $subscription): int => $subscription->getUser()->getId(),
            array_filter(
                $this->repository()->findIncompleteCertificates($urlId),
                static fn (SessionRelUser $subscription): bool => $subscription->getSession()->getId() === $session->getId()
                    && Session::STUDENT === $subscription->getRelationType()
            )
        )));

        $this->assertSame([$this->mainUrlLearner->getId()], $learnerIds($this->mainUrl->getId()));
        $this->assertSame([$this->otherUrlLearner->getId()], $learnerIds($this->otherUrl->getId()));
    }

    public function testOnlyTheCurrentUrlUsersAreListedAsRestartable(): void
    {
        $session = $this->createSessionWithBothLearners(new DateTime('-10 days'), new DateTime('-1 day'));

        $learnerIds = fn (int $urlId): array => array_values(array_map(
            static fn (SessionRelCourseRelUser $subscription): int => $subscription->getUser()->getId(),
            array_filter(
                $this->repository()->findRestartableSessions($urlId, 0, 1000),
                static fn (SessionRelCourseRelUser $subscription): bool => $subscription->getSession()->getId() === $session->getId()
            )
        ));

        $this->assertSame([$this->mainUrlLearner->getId()], $learnerIds($this->mainUrl->getId()));
        $this->assertSame([$this->otherUrlLearner->getId()], $learnerIds($this->otherUrl->getId()));
    }

    /**
     * A course shared by both URLs, and one learner registered on each URL only.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mainUrl = $this->getAccessUrl();
        $this->otherUrl = $this->createOtherAccessUrl();

        $this->course = $this->createCourse('Shared course');
        $this->otherUrl->addCourse($this->course);

        $this->mainUrlLearner = $this->createUser('main_url_learner');
        $this->otherUrlLearner = $this->createUser('other_url_learner');
        // createUser() registers the user on the main URL; this one only belongs to the other URL.
        $this->otherUrlLearner->getPortals()->clear();
        $this->otherUrl->addUser($this->otherUrlLearner);
        $this->getEntityManager()->flush();
    }

    private function repository(): GradebookCertificateRepository
    {
        return self::getContainer()->get(GradebookCertificateRepository::class);
    }

    private function createSessionWithBothLearners(DateTime $start, ?DateTime $end): Session
    {
        $session = $this->createSession('Shared session');
        $session
            ->setAccessStartDate($start)
            ->setAccessEndDate($end)
            ->addCourse($this->course)
        ;
        foreach ([$this->mainUrlLearner, $this->otherUrlLearner] as $learner) {
            $session->addUserInSession(Session::STUDENT, $learner);
            $session->addUserInCourse(Session::STUDENT, $learner, $this->course);
        }
        self::getContainer()->get(SessionRepository::class)->update($session);

        return $session;
    }

    /**
     * @param GradebookCertificate[] $certificates
     *
     * @return int[]
     */
    private function certificateLearnerIds(array $certificates): array
    {
        $course = $this->course;

        return array_values(array_map(
            static fn (GradebookCertificate $certificate): int => $certificate->getUser()->getId(),
            array_filter(
                $certificates,
                static fn (GradebookCertificate $certificate): bool => $certificate->getCategory()->getCourse()->getId() === $course->getId()
            )
        ));
    }

    private function createOtherAccessUrl(): AccessUrl
    {
        $url = (new AccessUrl())
            ->setUrl('https://certificate-scope-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($this->getAdmin())
            ->setSuperior($this->getAccessUrl())
        ;
        self::getContainer()->get(AccessUrlRepository::class)->create($url);

        return $url;
    }

    private function createCategory(Course $course): GradebookCategory
    {
        $category = (new GradebookCategory())
            ->setTitle('Shared course')
            ->setCourse($course)
            ->setWeight(100)
            ->setVisible(true)
            ->setLocked(0)
            ->setGenerateCertificates(true)
        ;
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();

        return $category;
    }

    private function createCertificate(GradebookCategory $category, User $learner): void
    {
        $certificate = (new GradebookCertificate())
            ->setCategory($category)
            ->setUser($learner)
            ->setScoreCertificate(100)
            ->setCreator($learner)
        ;
        $this->getEntityManager()->persist($certificate);
        $this->getEntityManager()->flush();
    }
}
