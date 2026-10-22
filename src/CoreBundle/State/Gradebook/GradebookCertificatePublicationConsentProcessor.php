<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookCertificatePublicationConsent;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookCertificateCredential;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificatePublicationConsentService;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const DATE_ATOM;

/**
 * @implements ProcessorInterface<GradebookCertificatePublicationConsent, GradebookCertificatePublicationConsent>
 */
final readonly class GradebookCertificatePublicationConsentProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserHelper $userHelper,
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private GradebookCertificatePublicationConsentService $consentService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookCertificatePublicationConsent
    {
        if (!$data instanceof GradebookCertificatePublicationConsent || $data->certificateId <= 0) {
            throw new BadRequestHttpException('A valid certificate id is required.');
        }

        // Consent must come from the certificate owner, not from an impersonating manager.
        if ($this->security->isGranted('IS_IMPERSONATOR')) {
            throw new AccessDeniedHttpException('Publication approval requires the certificate owner to sign in.');
        }

        $user = $this->userHelper->getCurrent();
        if (!$user instanceof User) {
            // Do not reveal whether the supplied certificate exists.
            throw new NotFoundHttpException('Certificate not found.');
        }

        $certificate = $this->entityManager->getRepository(GradebookCertificate::class)->find($data->certificateId);
        if (!$certificate instanceof GradebookCertificate || (int) $certificate->getUser()->getId() !== (int) $user->getId()) {
            throw new NotFoundHttpException('Certificate not found.');
        }

        // Mirror the visibility rules of GradebookMyCertificatesProvider. Possession of
        // an arbitrary certificate ID does not confer access across institutional URLs.
        $category = $certificate->getCategory();
        if (!$category instanceof GradebookCategory || null !== $category->getParent() || !$category->getGenerateCertificates()) {
            throw new NotFoundHttpException('Certificate not found.');
        }

        $course = $category->getCourse();
        $accessUrlId = (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0);
        $onCurrentPortal = false;
        foreach ($course->getUrls() as $relation) {
            if ((int) $relation->getUrl()->getId() === $accessUrlId) {
                $onCurrentPortal = true;

                break;
            }
        }

        if ($accessUrlId <= 0 || !$onCurrentPortal) {
            throw new NotFoundHttpException('Certificate not found.');
        }

        $session = $category->getSession();
        if (null === $session) {
            $membership = $this->entityManager->getRepository(CourseRelUser::class)->findOneBy([
                'course' => $course,
                'user' => $user,
            ]);
        } else {
            $membership = $this->entityManager->getRepository(SessionRelCourseRelUser::class)->findOneBy([
                'course' => $course,
                'session' => $session,
                'user' => $user,
            ]);
        }

        if (null === $membership) {
            throw new NotFoundHttpException('Certificate not found.');
        }

        $this->consentService->update($certificate, $data->approve, new DateTimeImmutable('now', new DateTimeZone('UTC')));
        if (!$data->approve) {
            // Withdrawal cannot delete an on-chain transaction, but it must
            // permanently revoke any already-issued public credential.
            $proofs = $this->entityManager->getRepository(GradebookCertificateCredential::class)
                ->findBy(['certificate' => $certificate])
            ;
            foreach ($proofs as $proof) {
                if ($proof instanceof GradebookCertificateCredential) {
                    $proof->revoke();
                }
            }
        }
        $this->entityManager->flush();

        $response = new GradebookCertificatePublicationConsent();
        $response->certificateId = (int) $certificate->getId();
        $response->success = true;
        $response->approvedAt = $certificate->getUserPublicationApprovalDate()?->format(DATE_ATOM);
        $response->revokedAt = $certificate->getUserPublicationRevocationDate()?->format(DATE_ATOM);

        return $response;
    }
}
