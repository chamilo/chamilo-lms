<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Branch;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<Branch, Branch>
 */
final readonly class BranchStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private AccessUrlHelper $accessUrlHelper,
        private RoomAccessUrlHelper $roomAccessUrlHelper,
        private EntityManagerInterface $em,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Branch
    {
        \assert($data instanceof Branch);

        // Put/Patch denormalize onto a fresh, unmanaged Branch instead of
        // the fetched one (object_to_populate isn't taking effect for this
        // resource), so anything the request didn't submit -- including the
        // Gedmo tree bookkeeping columns -- would otherwise be wiped, and
        // TreeListener then rejects the flush ("Root cannot be changed
        // manually"). Re-attach the submitted branch:write values onto the
        // real, managed entity before persisting.
        if (isset($uriVariables['id'])) {
            $existing = $this->em->getRepository(Branch::class)->find($uriVariables['id']);

            if ($existing instanceof Branch && $existing !== $data) {
                $existing
                    ->setTitle($data->getTitle())
                    ->setDescription($data->getDescription())
                    ->setParent($data->getParent())
                    ->setBranchIp($data->getBranchIp())
                    ->setCoordinates($data->getCoordinates())
                    ->setDwnSpeed($data->getDwnSpeed())
                    ->setUpSpeed($data->getUpSpeed())
                    ->setDelay($data->getDelay())
                    ->setAdminMail($data->getAdminMail())
                    ->setAdminName($data->getAdminName())
                    ->setAdminPhone($data->getAdminPhone())
                ;
                $data = $existing;
            }
        }

        if (null === $data->getId()) {
            $currentAccessUrl = $this->accessUrlHelper->getCurrent();
            if (null === $currentAccessUrl) {
                throw new BadRequestHttpException('The current access URL could not be resolved.');
            }

            $data->setUrl($currentAccessUrl);
        } else {
            $this->roomAccessUrlHelper->assertBranchAllowed($data);
        }

        if (null !== $data->getParent()) {
            $this->roomAccessUrlHelper->assertBranchAllowed($data->getParent());
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
