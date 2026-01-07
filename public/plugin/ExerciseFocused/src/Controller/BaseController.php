<?php

namespace Chamilo\PluginBundle\ExerciseFocused\Controller;

use Chamilo\PluginBundle\ExerciseFocused\Repository\LogRepository;
use Doctrine\ORM\EntityManager;
use Exception;
use ExerciseFocusedPlugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Template;

abstract class BaseController
{
    /**
     * @var Template
     */
    protected $template;

    public function __construct(
        protected readonly ExerciseFocusedPlugin $plugin,
        protected readonly Request $request,
        protected readonly EntityManager $em,
        protected readonly LogRepository $logRepository
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(): Response
    {
        if (!$this->plugin->isEnabled(true)) {
            throw new AccessDeniedHttpException(
                Response::$statusTexts[Response::HTTP_FORBIDDEN]
            );
        }

        return new Response();
    }

    protected function renderView(
        string $title,
        string $content,
        ?string $header = null,
        array $actions = []
    ): Response {
        if (!$header) {
            $header = $title;
        }

        $this->template = new Template($title);
        $this->template->assign('header', $header);
        $this->template->assign('actions', implode(PHP_EOL, $actions));
        $this->template->assign('content', $content);

        ob_start();
        $this->template->display_one_col_template();
        $html = ob_get_contents();
        ob_end_clean();

        return new Response($html);
    }
}
