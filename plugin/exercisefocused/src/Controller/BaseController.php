<?php

namespace Chamilo\PluginBundle\ExerciseFocused\Controller;

use Chamilo\PluginBundle\ExerciseFocused\Repository\LogRepository;
use Doctrine\ORM\EntityManager;
use Exception;
use ExerciseFocusedPlugin;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Template;

abstract class BaseController
{
    /**
     * @var ExerciseFocusedPlugin
     */
    protected $plugin;

    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var LogRepository
     */
    protected $logRepository;

    /**
     * @var Template
     */
    protected $template;

    public function __construct(
        ExerciseFocusedPlugin $plugin,
        HttpRequest $request,
        EntityManager $em,
        LogRepository $logRepository
    ) {
        $this->plugin = $plugin;
        $this->request = $request;
        $this->em = $em;
        $this->logRepository = $logRepository;
    }

    /**
     * @throws Exception
     */
    public function __invoke(): HttpResponse
    {
        if (!$this->plugin->isEnabled(true)) {
            throw new Exception();
        }

        return HttpResponse::create();
    }

    protected function renderView(
        string $title,
        string $content,
        ?string $header = null,
        array $actions = []
    ): HttpResponse {
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

        return HttpResponse::create($html);
    }
}
