<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor;

use Chamilo\CoreBundle\Component\Editor\Editor;
use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

/**
 * Class CkEditor.
 */
class CkEditor extends Editor
{
    /**
     * Return the HTML code required to run editor.
     *
     * @param string $value
     *
     * @return string
     */
    public function createHtml($value)
    {
        $html = '<textarea id="'.$this->getTextareaId().'" name="'.$this->getName().'" class="ckeditor">
                 '.$value.'
                 </textarea>';
        $html .= $this->editorReplace();

        return $html;
    }

    /**
     * Return the HTML code required to run editor.
     *
     * @param string $value
     *
     * @return string
     */
    public function createHtmlStyle($value): string
    {
        $style = '';

        $value = trim($value);

        if ($value === '' || $value === '<html><head><title></title></head><body></body></html>') {
            $style = api_get_bootstrap_and_font_awesome();
            $style .= api_get_css(ChamiloApi::getEditorDocStylePath());
        }

        $html = '<textarea id="'.$this->getTextareaId().'" name="'.$this->getName().'" class="ckeditor">
                 '.$style.$value.'
                 </textarea>';
        $html .= $this->editorReplace();

        return $html;
    }

    /**
     * @return string
     */
    public function editorReplace()
    {
        $toolbar = new Toolbar\Basic(
            $this->urlGenerator,
            $this->toolbarSet,
            $this->config,
            'CkEditor'
        );

        $toolbar->setLanguage($this->getLocale());
        $config = $toolbar->getConfig();
        $javascript = $this->toJavascript($config);

        //var_dump($this->getTextareaId());

        $html = "<script>
           CKEDITOR.replace('".$this->getTextareaId()."',
               $javascript
           );
           </script>";

        return $html;
    }

    /**
     * @param array $templates
     *
     * @return string
     */
    public function formatTemplates($templates)
    {
        if (empty($templates)) {
            return null;
        }
        /** @var \Chamilo\CoreBundle\Entity\SystemTemplate $template */
        $templateList = [];
        $cssTheme = api_get_path(WEB_CSS_PATH).'themes/'.api_get_visual_theme().'/';
        $search = ['{CSS_THEME}', '{IMG_DIR}', '{REL_PATH}', '{COURSE_DIR}', '{CSS}'];
        $replace = [
            $cssTheme,
            api_get_path(REL_CODE_PATH).'img/',
            api_get_path(REL_PATH),
            api_get_path(REL_DEFAULT_COURSE_DOCUMENT_PATH),
            '',
        ];

        foreach ($templates as $template) {
            $image = $template->getImage();
            $image = !empty($image) ? $image : 'empty.gif';

            /*$image = $this->urlGenerator->generate(
                'get_document_template_action',
                array('file' => $image),
                UrlGenerator::ABSOLUTE_URL
            );*/

            $content = str_replace($search, $replace, $template->getContent());

            $templateList[] = [
                'title' => $this->translator->trans($template->getTitle()),
                'description' => $this->translator->trans($template->getComment()),
                'image' => $image,
                'html' => $content,
            ];
        }

        return json_encode($templateList);
    }

    /**
     * Get the templates in JSON format.
     *
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

    /**
     * Get the empty template.
     *
     * @return array
     */
    private function getEmptyTemplate(): array
    {
        return [
            [
                'title' => get_lang('Blank template'),
                'description' => null,
                'image' => api_get_path(WEB_PUBLIC_PATH).'img/template_thumb/empty.gif',
                'html' => '
                <!DOCYTPE html>
                <html>
                    <head>
                        <meta charset="'.api_get_system_encoding().'" />
                    </head>
                    <body  dir="'.api_get_text_direction().'">
                        <p>
                            <br/>
                        </p>
                    </body>
                    </html>
                </html>
            ',
            ],
        ];
    }

    /**
     * Get the platform templates.
     *
     * @return array
     */
    private function getPlatformTemplates(): array
    {
        $entityManager = \Database::getManager();
        $systemTemplates = $entityManager->getRepository('ChamiloCoreBundle:SystemTemplate')->findAll();
        $cssTheme = api_get_path(WEB_CSS_PATH).'themes/'.api_get_visual_theme().'/';
        $search = ['{CSS_THEME}', '{IMG_DIR}', '{REL_PATH}', '{COURSE_DIR}', '{CSS}'];
        $replace = [
            $cssTheme,
            api_get_path(REL_PATH).'public/img/',
            api_get_path(REL_PATH),
            api_get_path(REL_PATH).'public/img/document/',
            '',
        ];

        $templateList = [];

        foreach ($systemTemplates as $template) {
            $image = $template->getImage();
            $image = !empty($image) ? $image : 'empty.gif';
            $image = api_get_path(WEB_PUBLIC_PATH).'img/template_thumb/'.$image;
            $templateContent = $template->getContent();
            $content = str_replace($search, $replace, $templateContent);

            $templateList[] = [
                'title' => get_lang($template->getTitle()),
                'description' => get_lang($template->getComment()),
                'image' => $image,
                'html' => $content,
            ];
        }

        return $templateList;
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    private function getPersonalTemplates($userId = 0)
    {
        if (empty($userId)) {
            $userId = api_get_user_id();
        }

        $entityManager = \Database::getManager();
        $templatesRepo = $entityManager->getRepository('ChamiloCoreBundle:Templates');
        $user = api_get_user_entity($userId);
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
            $templateItem['image'] = api_get_path(WEB_PUBLIC_PATH).'img/template_thumb/noimage.gif';
            $templateItem['html'] = file_get_contents(api_get_path(SYS_COURSE_PATH)
                .$courseDirectory.'/document'.$templateData['path']);

            $image = $template->getImage();
            if (!empty($image)) {
                $templateItem['image'] = api_get_path(WEB_COURSE_PATH)
                    .$courseDirectory.'/upload/template_thumbnails/'.$template->getImage();
            }

            $templateList[] = $templateItem;
        }

        return $templateList;
    }
}
