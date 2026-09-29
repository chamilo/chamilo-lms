<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Forum\ForumPostWriteInput;

/**
 * Provides a dummy input object for manual reply request processing.
 *
 * @implements ProviderInterface<ForumPostWriteInput>
 */
final class ForumPostCreateStateProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ForumPostWriteInput
    {
        return new ForumPostWriteInput();
    }
}
