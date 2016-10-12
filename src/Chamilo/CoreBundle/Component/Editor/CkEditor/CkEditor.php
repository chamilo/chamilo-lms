<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor;

use Chamilo\CoreBundle\Component\Editor\CkEditor\Toolbar;
use Chamilo\CoreBundle\Component\Editor\Editor;

//use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class CkEditor
 * @package Chamilo\CoreBundle\Component\Editor\CkEditor
 */
class CkEditor extends Editor
{
    /**
     * @return string
     */
    public function getEditorTemplate()
    {
        return 'javascript/editor/ckeditor/elfinder.tpl';
    }

    /**
     * Set js to be include in the template
     */
    public function setJavascriptToInclude()
    {
        //$jsFolder = api_get_path(WEB_LIBRARY_JS_PATH);
        //$this->template->addResource($jsFolder.'ckeditor/ckeditor.js', 'js');
    }

    /**
     * Return the HTML code required to run editor.
     *
     * @return string
     */

    public function createHtml()
    {
        $html = '<textarea id="'.$this->getName().'" name="'.$this->getName().'" class="ckeditor">
                 '.$this->value.'
                 </textarea>';
        $html .= $this->editorReplace();

        return $html;
    }

    /**
     * Return the HTML code required to run editor.
     *
     * @return string
     */
    public function createHtmlStyle()
    {
        $style = '';
        if (trim($this->value) == '<html><head><title></title></head><body></body></html>' || $this->value == '' ) {
            $cssFile = api_get_path(SYS_CSS_PATH).'themes/'.api_get_visual_theme().'/editor.css';
            if (!is_file($cssFile)) {
                $cssFile = api_get_path(WEB_CSS_PATH).'editor.css';
            } else {
                $cssFile = api_get_path(WEB_CSS_PATH).'themes/'.api_get_visual_theme().'/editor.css';
            }
            $style = '<link href="'.$cssFile.'" rel="stylesheet" media="screen" type="text/css" />';
        }

        $html = '<textarea id="'.$this->getName().'" name="'.$this->getName().'" class="ckeditor">
                 '.$style.htmlspecialchars($this->value, ENT_COMPAT).'
                 </textarea>';
        $html .= $this->editorReplace();

        return $html;
    }

    /**
     * @return string
     */
    public function editorReplace()
    {
        $toolbar = new Toolbar\Basic($this->toolbarSet, $this->config, 'CkEditor');
        $toolbar->setLanguage($this->getLocale());
        $config = $toolbar->getConfig();

        $javascript = $this->toJavascript($config);

        $html = "<script>
           CKEDITOR.replace('".$this->getName()."',
               $javascript
           );
           </script>";

        return $html;
    }

    /**
     * @param array $templates
     *
     * @return null
     */
    public function formatTemplates($templates)
    {
        if (empty($templates)) {
            return null;
        }
        /** @var \Chamilo\CoreBundle\Entity\SystemTemplate $template */
        $templateList = array();

        $search = array('{CSS}', '{IMG_DIR}', '{REL_PATH}', '{COURSE_DIR}');
        $replace = array(
            '',
            api_get_path(REL_CODE_PATH).'img/',
            api_get_path(REL_PATH),
            //api_get_path(REL_DEFAULT_COURSE_DOCUMENT_PATH),
            //api_get_path(REL_DEFAULT_COURSE_DOCUMENT_PATH)
        );

        foreach ($templates as $template) {
            $image = $template->getImage();
            $image = !empty($image) ? $image : 'empty.gif';

            /*$image = $this->urlGenerator->generate(
                'get_document_template_action',
                array('file' => $image),
                UrlGenerator::ABSOLUTE_URL
            );*/

            $content = str_replace($search, $replace, $template->getContent());

            $templateList[] = array(
                'title' => $this->translator->trans($template->getTitle()),
                'description' => $this->translator->trans($template->getComment()),
                'image' => $image,
                'html' => $content
            );
        }

        return json_encode($templateList);
    }

    /**
     * Get the empty template
     * @return array
     */
    private function getEmptyTemplate()
    {
        return [[
            'title' => get_lang('EmptyTemplate'),
            'description' => null,
            'image' => api_get_path(WEB_APP_PATH) . 'home/default_platform_document/template_thumb/empty.gif',
            'html' => '
                <!DOCYTPE html>
                <html>
                    <head>
                        <meta charset="' . api_get_system_encoding() . '" />
                    </head>
                    <body  dir="' . api_get_text_direction() . '">
                        <p>
                            <br/>
                        </p>
                    </body>
                    </html>
                </html>
            '
        ]];
    }

    /**
     * Get the platform templates
     * @return array
     */
    private function getPlatformTemplates()
    {
        $entityManager = \Database::getManager();
        $systemTemplates = $entityManager->getRepository('ChamiloCoreBundle:SystemTemplate')->findAll();

        $search = array('{CSS}', '{IMG_DIR}', '{REL_PATH}', '{COURSE_DIR}');
        $replace = array(
            '',
            api_get_path(REL_CODE_PATH) . 'img/',
            api_get_path(REL_PATH),
            api_get_path(REL_DEFAULT_COURSE_DOCUMENT_PATH),
            api_get_path(REL_DEFAULT_COURSE_DOCUMENT_PATH)
        );

        $templateList = array();

        foreach ($systemTemplates as $template) {
            $image = $template->getImage();
            $image = !empty($image) ? $image : 'empty.gif';
            $image = api_get_path(WEB_APP_PATH) . 'home/default_platform_document/template_thumb/' . $image;

            /*$image = $this->urlGenerator->generate(
                'get_document_template_action',
                array('file' => $image),
                UrlGenerator::ABSOLUTE_URL
            );*/

            $templateContent = $template->getContent();
            $content = str_replace($search, $replace, $templateContent);

            $templateList[] = array(
                'title' => get_lang($template->getTitle()),
                'description' => get_lang($template->getComment()),
                'image' => $image,
                'html' => $content
            );
        }

        return $templateList;
    }

    private function getPersonalTemplates($userId = 0)
    {
        if (empty($userId)) {
            $userId = api_get_user_id();
        }

        $entityManager = \Database::getManager();
        $templatesRepo = $entityManager->getRepository('ChamiloCoreBundle:Templates');

        $user = $entityManager->find('ChamiloUserBundle:User', $userId);
        $course = $entityManager->find('ChamiloCoreBundle:Course', api_get_course_int_id());

        if (!$user || !$course) {
            return [];
        }

        $courseTemplates = $templatesRepo->getCourseTemplates($course, $user);

        $templateList = [];

        foreach ($courseTemplates as $templateData) {
            $template = $templateData[0];
            $courseDirectory = $course->getDirectory();

            $templateItem = [];
            $templateItem['title'] = $template->getTitle();
            $templateItem['description'] = $template->getDescription();
            $templateItem['image'] = api_get_path(WEB_APP_PATH)
                . 'home/default_platform_document/template_thumb/noimage.gif';
            $templateItem['html'] = file_get_contents(api_get_path(SYS_COURSE_PATH)
                . $courseDirectory . '/document' . $templateData['path']);

            $image = $template->getImage();
            if (!empty($image)) {
                $templateItem['image'] = api_get_path(WEB_COURSE_PATH)
                    . $courseDirectory . '/upload/template_thumbnails/' . $template->getImage();
            }

            $templateList[] = $templateItem;
        }

        return $templateList;
    }

    /**
     * Get the templates in JSON format
     * @return string|
     */
    public function simpleFormatTemplates()
    {
        $templates = $this->getEmptyTemplate();

        if (api_is_allowed_to_edit(false, true)) {
            $platformTemplates = $this->getPlatformTemplates();
            $templates = array_merge($templates, $platformTemplates);
        }

        $personalTemplates = $this->getPersonalTemplates();
        $templates = array_merge($templates, $personalTemplates);

        return json_encode($templates);
    }
}
