<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CGlossary;

class Glossary extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'glossary';
    }

    public function getLink(): string
    {
        return '/main/glossary/index.php';
    }

    public function getIcon(): string
    {
        return 'mdi-alphabetical';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'glossaries' => CGlossary::class,
        ];
    }
}
