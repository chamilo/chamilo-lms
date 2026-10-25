<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookCertificateCredentialAction;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookCertificateCredential;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateCredentialSigner;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateSepoliaVerifier;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<GradebookCertificateCredentialAction, GradebookCertificateCredentialAction> */
final readonly class GradebookCertificateCredentialActionProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
        private RequestStack $requestStack,
        private GradebookCertificateCredentialSigner $signer,
        private GradebookCertificateSepoliaVerifier $sepolia,
    ) {}

    /**
     * @param array<string,mixed> $uriVariables @param array<string,mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookCertificateCredentialAction
    {
        if (!$data instanceof GradebookCertificateCredentialAction || $data->certificateId < 1) {
            throw new BadRequestHttpException('Invalid certificate identifier.');
        }
        $actor = $this->security->getUser();
        if (!$actor instanceof User || !$actor->isAdmin() || $this->security->isGranted('IS_IMPERSONATOR')) {
            throw new AccessDeniedHttpException('Only a directly authenticated platform administrator can issue credentials.');
        }
        if (!\in_array(strtolower((string) $this->settingsManager->getSetting('certificate.blockchain_verification_enabled')), ['1', 'true'], true)) {
            throw new AccessDeniedHttpException('Certificate credential issuance is disabled.');
        }
        $certificate = $this->entityManager->getRepository(GradebookCertificate::class)->find($data->certificateId);
        if (!$certificate instanceof GradebookCertificate) {
            throw new NotFoundHttpException('Certificate not found.');
        }
        $category = $certificate->getCategory();
        if (!$category instanceof GradebookCategory || null !== $category->getParent() || !$category->getGenerateCertificates()) {
            throw new NotFoundHttpException('Certificate not found.');
        }
        $portalId = (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0);
        $inPortal = false;
        foreach ($category->getCourse()->getUrls() as $relation) {
            if ($portalId > 0 && (int) $relation->getUrl()->getId() === $portalId) {
                $inPortal = true;

                break;
            }
        }
        if (!$inPortal) {
            throw new NotFoundHttpException('Certificate not found.');
        }
        $action = strtolower(trim($data->action));
        if (!\in_array($action, ['issue', 'revoke', 'confirm_anchor'], true)) {
            throw new BadRequestHttpException('Unsupported action.');
        }
        $repo = $this->entityManager->getRepository(GradebookCertificateCredential::class);
        $existing = $repo->findOneBy(['certificate' => $certificate], ['issuedAt' => 'DESC']);
        $approval = $certificate->getUserPublicationApprovalDate();
        $hasScopedApproval = null !== $approval
            && 'sepolia-hash-only-v1' === $certificate->getUserPublicationConsentScope();

        if ('issue' === $action) {
            if (!$hasScopedApproval) {
                throw new ConflictHttpException('The owner must explicitly approve the current credential and Sepolia hash-only terms.');
            }
            if ($existing instanceof GradebookCertificateCredential && null === $existing->getRevokedAt()) {
                $credential = $existing;
            } else {
                $request = $this->requestStack->getCurrentRequest();
                if (null === $request || !$request->isSecure()) {
                    throw new BadRequestHttpException('Credential issuer requires HTTPS.');
                }
                $credential = $this->signer->issue($certificate, $request->getSchemeAndHttpHost());
                $this->entityManager->persist($credential);
                $this->entityManager->flush();
            }
        } else {
            if (!$existing instanceof GradebookCertificateCredential) {
                throw new NotFoundHttpException('No signed credential exists for this certificate.');
            }
            $credential = $existing;
            if ('revoke' === $action) {
                $credential->revoke();
                $this->entityManager->flush();
            } else {
                if (!$hasScopedApproval || null !== $credential->getRevokedAt()) {
                    throw new ConflictHttpException('Credential is not eligible for anchoring.');
                }
                if (null !== $credential->getTxHash()) {
                    throw new ConflictHttpException('Credential already anchored.');
                }
                if (!$this->signer->validSignature($credential) || !$this->signer->matchesCurrentSource($credential)) {
                    throw new ConflictHttpException('Credential signature is invalid.');
                }
                if (1 !== preg_match('/^0x[0-9a-fA-F]{64}$/D', $data->txHash)) {
                    throw new BadRequestHttpException('A Sepolia transaction hash is required.');
                }
                // Small institutional safety limit. Browser wallet confirmation is mandatory.
                $today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
                $anchorCount = (int) $this->entityManager->createQueryBuilder()
                    ->select('COUNT(p.id)')
                    ->from(GradebookCertificateCredential::class, 'p')
                    ->where('p.anchoredAt >= :today')
                    ->setParameter('today', $today, Types::DATETIME_IMMUTABLE)
                    ->getQuery()->getSingleScalarResult()
                ;
                if ($anchorCount >= 5) {
                    throw new ConflictHttpException('Daily testnet anchor limit reached (5).');
                }

                try {
                    $valid = $this->sepolia->verifyTransaction($credential, $data->txHash);
                } catch (RuntimeException $e) {
                    throw new BadRequestHttpException($e->getMessage(), $e);
                }
                if (!$valid) {
                    throw new ConflictHttpException('The Sepolia transaction is unconfirmed or its payload does not match.');
                }
                $credential->recordAnchor($data->txHash);
                $this->entityManager->flush();
            }
        }
        $response = new GradebookCertificateCredentialAction();
        $response->success = true;
        $response->token = $credential->getToken();
        $response->verificationUrl = '/certificates/verify/'.$credential->getToken();
        $response->anchorData = $this->signer->anchorData($credential);
        $response->status = null !== $credential->getRevokedAt() ? 'revoked' : 'issued';

        return $response;
    }
}
