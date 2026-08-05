<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Forum\ForumThreadWriteInput;

/**
 * Provides a dummy input object for manual multipart processing.
 *
 * @implements ProviderInterface<ForumThreadWriteInput>
 */
final class ForumThreadCreateStateProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ForumThreadWriteInput
    {
        return new ForumThreadWriteInput();
    }
}
