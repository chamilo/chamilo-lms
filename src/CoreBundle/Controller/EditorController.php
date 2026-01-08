<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Component\Editor\Tiny\TinyEditor;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;
use Chamilo\CoreBundle\Traits\ResourceControllerTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/editor')]
class EditorController extends BaseController
{
    use ControllerTrait;
    use CourseControllerTrait;
    use ResourceControllerTrait;

    /**
     * Get templates (left column when creating a document).
     */
    #[Route(path: '/templates', methods: ['GET'], name: 'editor_templates')]
    public function editorTemplates(TranslatorInterface $translator, RouterInterface $router): Response
    {
        $editor = new TinyEditor(
            $translator,
            $router
        );
        $templates = $editor->simpleFormatTemplates();

        return $this->render(
            '@ChamiloCore/Editor/templates.html.twig',
            [
                'templates' => $templates,
            ]
        );
    }
}
