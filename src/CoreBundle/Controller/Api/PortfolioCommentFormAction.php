<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\ApiResource\Portfolio\PortfolioCommentForm;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class PortfolioCommentFormAction
{
    public function __invoke(): PortfolioCommentForm
    {
        return new PortfolioCommentForm();
    }
}
