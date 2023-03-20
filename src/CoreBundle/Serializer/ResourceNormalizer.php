<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Serializer;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceIllustrationInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

final class ResourceNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'MEDIA_OBJECT_NORMALIZER_ALREADY_CALLED';

    private ResourceNodeRepository $resourceNodeRepository;
    private IllustrationRepository $illustrationRepository;
    private RequestStack $requestStack;
    private UrlGeneratorInterface $generator;

    public function __construct(ResourceNodeRepository $resourceNodeRepository, IllustrationRepository $illustrationRepository, RequestStack $requestStack, UrlGeneratorInterface $generator)
    {
        $this->resourceNodeRepository = $resourceNodeRepository;
        $this->requestStack = $requestStack;
        $this->generator = $generator;
        $this->illustrationRepository = $illustrationRepository;
    }

    /**
     * @param AbstractResource|User $object
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        $request = $this->requestStack->getCurrentRequest();
        $getFile = false;
        $courseId = 0;
        $sessionId = 0;
        $groupId = 0;

        if ($request) {
            $getFile = $request->get('getFile');
            $courseId = (int) $request->get('cid');
            if (empty($courseId)) {
                // Try with cid from session
                $courseId = (int) $request->getSession()->get('cid');
            }

            $sessionId = (int) $request->get('sid');
            if (empty($sessionId)) {
                $sessionId = (int) $request->getSession()->get('sid');
            }

            $groupId = (int) $request->get('gid');
            if (empty($groupId)) {
                $groupId = (int) $request->getSession()->get('gid');
            }
        }

        if ($object->hasResourceNode()) {
            $resourceNode = $object->getResourceNode();

            $params = [
                'id' => $resourceNode->getUuid(),
                'cid' => $courseId,
                'sid' => $sessionId,
                'gid' => $groupId,
                'tool' => $resourceNode->getResourceType()->getTool()->getName(),
                'type' => $resourceNode->getResourceType()->getName(),
            ];

            //if ($getFile) {
            // Get all links from resource.
            if ($object instanceof AbstractResource) {
                $object->setResourceLinkListFromEntity();
            }
            //}

            $object->contentUrl = $this->generator->generate('chamilo_core_resource_view', $params);
            $object->downloadUrl = $this->generator->generate('chamilo_core_resource_download', $params);

            // Get illustration of a resource, instead of looking for the node children to get the illustration.
            if ($object instanceof ResourceIllustrationInterface) {
                $object->illustrationUrl = $this->illustrationRepository->getIllustrationUrl($object);
            }

            // This gets the file contents, usually use to get HTML/Text data to be edited.
            if ($getFile &&
                $resourceNode->hasResourceFile() &&
                $resourceNode->hasEditableTextContent()
            ) {
                $object->contentFile = $this->resourceNodeRepository->getResourceNodeFileContent(
                    $resourceNode
                );
            }
        }

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof AbstractResource || $data instanceof User;
    }
}
