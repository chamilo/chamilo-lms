<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;

class Gradebook extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'gradebook';
    }

    public function getLink(): string
    {
        return '/main/gradebook/index.php';
    }

    public function getIcon(): string
    {
        return 'mdi-certificate';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'gradebooks' => GradebookCategory::class,
            'gradebook_links' => GradebookLink::class,
            'gradebook_evaluations' => GradebookEvaluation::class,
        ];
    }
}
