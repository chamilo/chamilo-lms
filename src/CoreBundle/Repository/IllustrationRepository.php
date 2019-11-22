<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Illustration;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceFile;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class IllustrationRepository.
 */
class IllustrationRepository extends ResourceRepository
{
    /**
     * @param AbstractResource $resource
     * @param User             $user
     * @param                  $uploadFile
     *
     * @return ResourceFile|null
     */
    public function addIllustration(AbstractResource $resource, User $user, $uploadFile): ?ResourceFile
    {
        $illustrationNode = $this->getIllustrationNodeFromResource($resource);
        $em = $this->getEntityManager();

        if ($illustrationNode === null) {
            $illustration = new Illustration();
            $em->persist($illustration);
            $this->addResourceNode($illustration, $user, $resource);
        } else {
            $illustration = $this->repository->findOneBy(['resourceNode' => $illustrationNode]);
        }

        //$this->addResourceToEveryone($illustrationNode);
        return $this->addFile($illustration, $uploadFile);
    }

    /**
     * @param AbstractResource $resource
     *
     * @return ResourceNode|null
     */
    public function getIllustrationNodeFromResource(AbstractResource $resource): ?ResourceNode
    {
        $nodeRepo = $this->getResourceNodeRepository();
        $resourceType = $this->getResourceType();

        /** @var ResourceNode $node */
        $node = $nodeRepo->findOneBy(
            ['parent' => $resource->getResourceNode(), 'resourceType' => $resourceType]
        );

        return $node;
    }

    /**
     * @param AbstractResource $resource
     */
    public function deleteIllustration(AbstractResource $resource)
    {
        $node = $this->getIllustrationNodeFromResource($resource);

        if ($node !== null) {
            $this->getEntityManager()->remove($node);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param string $filter See: services.yaml parameter "glide_media_filters" to see the list of filters.
     *
     * @return string
     */
    public function getIllustrationUrl(AbstractResource $resource, $filter = '')
    {
        $node = $this->getIllustrationNodeFromResource($resource);

        if ($node !== null) {
            $params = [
                'id' => $node->getId(),
                'tool' => 'admin',
                'type' => 'illustrations',
            ];
            if (!empty($filter)) {
                $params['filter'] = $filter;
            }

            return $this->getRouter()->generate(
                'chamilo_core_resource_file',
                $params
            );
        }

        return '';
    }
}
