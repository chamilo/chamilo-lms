<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\State\CategorizedExerciseResultStateProvider;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ApiResource(
    description: 'Exercise results by categories',
    operations: [
        new Get(provider: CategorizedExerciseResultStateProvider::class),
    ],
)]
class CategorizedExerciseResult
{
    #[Ignore]
    public TrackEExercise $exe;

    /**
     * @var array<int, array<string, string>>
     */
    public array $categories;

    public function __construct(TrackEExercise $exe, array $categories)
    {
        $this->exe = $exe;
        $this->categories = $categories;
    }

    #[ApiProperty(readable: false, identifier: true)]
    public function getExeId(): string
    {
        return (string) $this->exe->getExeId();
    }
}
