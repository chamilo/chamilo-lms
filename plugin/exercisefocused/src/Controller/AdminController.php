<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Controller;

use Chamilo\PluginBundle\ExerciseFocused\Traits\ReportingFilterTrait;
use Display;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminController extends BaseController
{
    use ReportingFilterTrait;

    public function __invoke(): HttpResponse
    {
        parent::__invoke();

        $form = $this->createForm();

        $results = [];

        if ($form->validate()) {
            $results = $this->findResults(
                $form->exportValues()
            );
        }

        $table = $this->createTable($results);

        $content = $form->returnForm()
            .Display::page_subheader2($this->plugin->get_lang('ReportByAttempts'))
            .$table->toHtml();

        $this->setBreadcrumb();

        return $this->renderView(
            $this->plugin->get_title(),
            $content
        );
    }

    private function setBreadcrumb()
    {
        $codePath = api_get_path(WEB_CODE_PATH);

        $GLOBALS['interbreadcrumb'][] = [
            'url' => $codePath.'admin/index.php',
            'name' => get_lang('Administration'),
        ];
    }
}
