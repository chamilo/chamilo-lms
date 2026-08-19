<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookCertificateSearch;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateGenerator;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<GradebookCertificateSearch>
 */
final readonly class GradebookCertificateSearchProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
        private GradebookCertificateGenerator $certificateGenerator,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookCertificateSearch
    {
        if (!$this->isSettingEnabled('certificate.allow_certificates_search')) {
            throw new AccessDeniedHttpException('Certificates search is not available.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new AccessDeniedHttpException('The current request is required.');
        }

        $accessUrlId = (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0);
        if ($accessUrlId <= 0) {
            throw new AccessDeniedHttpException('A valid access URL is required.');
        }

        $firstname = mb_substr(trim((string) $request->query->get('firstname', '')), 0, 255);
        $lastname = mb_substr(trim((string) $request->query->get('lastname', '')), 0, 255);
        $userId = $request->query->getInt('userId');

        $resource = new GradebookCertificateSearch();

        if ($userId > 0) {
            $user = $this->findUserInAccessUrl($userId, $accessUrlId);
            if (!$user instanceof User) {
                $resource->message = 'No user';

                return $resource;
            }

            $resource->selectedUser = $this->normalizeUser($user);
            $this->loadPublicCertificates($resource, $user, $accessUrlId);
            if ([] === $resource->courseCertificates && [] === $resource->sessionCertificates) {
                $resource->message = 'No results found';
            }

            return $resource;
        }

        if ('' === $firstname && '' === $lastname) {
            return $resource;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT u')
            ->from(User::class, 'u')
            ->join('u.portals', 'portal')
            ->join('portal.url', 'url')
            ->where('url.id = :urlId')
            ->andWhere('u.active != :softDeleted')
            ->setParameter('urlId', $accessUrlId, Types::INTEGER)
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
        ;

        if ('' !== $firstname) {
            $queryBuilder
                ->andWhere('LOWER(u.firstname) LIKE :firstname')
                ->setParameter('firstname', '%'.mb_strtolower($firstname).'%')
            ;
        }

        if ('' !== $lastname) {
            $queryBuilder
                ->andWhere('LOWER(u.lastname) LIKE :lastname')
                ->setParameter('lastname', '%'.mb_strtolower($lastname).'%')
            ;
        }

        /** @var list<User> $users */
        $users = $queryBuilder->getQuery()->getResult();
        $resource->users = array_map(
            fn (User $user): array => $this->normalizeUser($user),
            $users,
        );
        if ([] === $resource->users) {
            $resource->message = 'No results found';
        }

        return $resource;
    }

    private function findUserInAccessUrl(int $userId, int $accessUrlId): ?User
    {
        $user = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->join('u.portals', 'portal')
            ->join('portal.url', 'url')
            ->where('u.id = :userId')
            ->andWhere('url.id = :urlId')
            ->andWhere('u.active != :softDeleted')
            ->setParameter('userId', $userId, Types::INTEGER)
            ->setParameter('urlId', $accessUrlId, Types::INTEGER)
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $user instanceof User ? $user : null;
    }

    private function loadPublicCertificates(
        GradebookCertificateSearch $resource,
        User $user,
        int $accessUrlId,
    ): void {
        if (!$this->isSettingEnabled('certificate.allow_public_certificates')) {
            return;
        }

        $certificates = $this->entityManager->getRepository(GradebookCertificate::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
        );
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        foreach ($certificates as $certificate) {
            if (!$certificate instanceof GradebookCertificate) {
                continue;
            }

            $category = $certificate->getCategory();
            if (!$category instanceof GradebookCategory
                || null !== $category->getParent()
                || !$category->getGenerateCertificates()
                || (!$certificate->getPublish() && !$isAdmin)
            ) {
                continue;
            }

            $course = $category->getCourse();
            if (!$this->courseBelongsToAccessUrl($course, $accessUrlId)
                || !$this->isCoursePublicCertificateEnabled($course)
            ) {
                continue;
            }

            $session = $category->getSession();
            if (null === $session) {
                $subscription = $this->entityManager->getRepository(CourseRelUser::class)->findOneBy([
                    'course' => $course,
                    'user' => $user,
                ]);
                if (!$subscription instanceof CourseRelUser) {
                    continue;
                }
            } else {
                $subscription = $this->entityManager->getRepository(SessionRelCourseRelUser::class)->findOneBy([
                    'course' => $course,
                    'session' => $session,
                    'user' => $user,
                ]);
                if (!$subscription instanceof SessionRelCourseRelUser) {
                    continue;
                }
            }

            $summary = $this->certificateGenerator->normalizeCertificate($certificate, false);
            $row = [
                'id' => (int) $certificate->getId(),
                'course' => [
                    'id' => (int) $course->getId(),
                    'code' => (string) $course->getCode(),
                    'title' => (string) $course->getTitle(),
                ],
                'score' => (float) $certificate->getScoreCertificate(),
                'issuedAt' => $certificate->getCreatedAt()->format(DATE_ATOM),
                'certificate' => $summary,
            ];

            if (null === $session) {
                $resource->courseCertificates[] = $row;

                continue;
            }

            $row['session'] = [
                'id' => (int) $session->getId(),
                'title' => (string) $session->getTitle(),
            ];
            $row['certificate']['downloadUrl'] = '';
            $resource->sessionCertificates[] = $row;
        }
    }

    /**
     * @return array{id: int, firstname: string, lastname: string, completeName: string}
     */
    private function normalizeUser(User $user): array
    {
        $firstname = (string) $user->getFirstname();
        $lastname = (string) $user->getLastname();

        return [
            'id' => (int) $user->getId(),
            'firstname' => $firstname,
            'lastname' => $lastname,
            'completeName' => trim($firstname.' '.$lastname),
        ];
    }

    private function courseBelongsToAccessUrl(Course $course, int $accessUrlId): bool
    {
        foreach ($course->getUrls() as $relation) {
            if ((int) $relation->getUrl()->getId() === $accessUrlId) {
                return true;
            }
        }

        return false;
    }

    private function isCoursePublicCertificateEnabled(Course $course): bool
    {
        $settings = $this->entityManager->getRepository(CCourseSetting::class)->findBy(
            [
                'cId' => (int) $course->getId(),
                'variable' => 'allow_public_certificates',
            ],
            ['iid' => 'ASC'],
        );

        foreach ($settings as $setting) {
            if (!$setting instanceof CCourseSetting || null === $setting->getValue()) {
                continue;
            }

            $value = strtolower(trim((string) $setting->getValue()));
            if ('-1' === $value) {
                continue;
            }

            return !\in_array($value, ['', '0', 'false', 'no', 'off'], true);
        }

        // Legacy api_get_course_setting() returns -1 when no course override exists.
        // Public certificate visibility only rejects an explicit disabled course setting.
        return true;
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }
}
