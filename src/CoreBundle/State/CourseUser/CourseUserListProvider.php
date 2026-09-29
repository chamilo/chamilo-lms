<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseUser;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseUser\CourseUserList;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseUserList>
 */
final readonly class CourseUserListProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private CourseUserManager $courseUserManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseUserList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $data = $this->courseUserManager->getListData($request);
        $response = new CourseUserList();

        foreach ($data as $property => $value) {
            if (property_exists($response, $property)) {
                $response->{$property} = $value;
            }
        }

        return $response;
    }
}
