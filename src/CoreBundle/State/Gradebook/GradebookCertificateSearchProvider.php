<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookCertificateSearch;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateGenerator;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<GradebookCertificateSearch>
 */
final readonly class GradebookCertificateSearchProvider implements ProviderInterface
{
    private const int MAX_USERS = 50;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
        private GradebookCertificateGenerator $certificateGenerator,
        private Security $security,
        private GradebookCertificateRepository $certificateRepository,
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
        // Only an administrator finds the holders of unpublished certificates.
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        if ($userId > 0) {
            $user = $this->certificateRepository->findCertificateHolder($userId, $accessUrlId, $isAdmin);
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

        // One extra row tells whether the search exceeds the limit.
        $users = $this->certificateRepository->findCertificateHolders(
            $accessUrlId,
            $firstname,
            $lastname,
            $isAdmin,
            self::MAX_USERS + 1,
        );
        $isTruncated = \count($users) > self::MAX_USERS;
        $resource->users = array_map(
            fn (User $user): array => $this->normalizeUser($user),
            \array_slice($users, 0, self::MAX_USERS),
        );
        if ([] === $resource->users) {
            $resource->message = 'No results found';
        }
        if ($isTruncated) {
            $resource->message = 'Too many results. Please refine your search.';
        }

        return $resource;
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
                || !$this->certificateGenerator->isPublicCertificateEnabledForCourse($course)
            ) {
                continue;
            }

            if (!$this->certificateGenerator->isUserSubscribedToCategory($user, $category)) {
                continue;
            }

            $summary = $this->certificateGenerator->normalizeCertificate($certificate, false);
            $row = $this->certificateGenerator->normalizeCertificateRow($certificate, $category, $summary);
            if (isset($row['session'])) {
                $resource->sessionCertificates[] = $row;

                continue;
            }

            $resource->courseCertificates[] = $row;
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

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }
}
