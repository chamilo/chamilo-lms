<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\ApiResource\Portfolio\PortfolioForm;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class PortfolioFormAction
{
    public function __invoke(): PortfolioForm
    {
        return new PortfolioForm();
    }
}
