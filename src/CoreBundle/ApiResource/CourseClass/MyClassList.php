<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseClass;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseClass\MyClassListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'MyClassList',
    operations: [
        new Get(
            uriTemplate: '/my-classes/list',
            openapi: new Operation(
                summary: 'Classes and social groups of the authenticated user',
                parameters: [
                    new Parameter(name: 'id', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_my_class_list',
            provider: MyClassListProvider::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['my_class_list:read']],
)]
final class MyClassList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['my_class_list:read'])]
    public string $id = 'my_class_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['my_class_list:read'])]
    public array $items = [];

    #[Groups(['my_class_list:read'])]
    public int $totalItems = 0;

    #[Groups(['my_class_list:read'])]
    public bool $canAddClasses = false;

    #[Groups(['my_class_list:read'])]
    public string $addClassesUrl = '';

    public function getId(): string
    {
        return $this->id;
    }
}
