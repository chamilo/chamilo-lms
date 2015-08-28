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
        if (trim($this->value) == '<html><head><title></title></head><body></body></html>') {
            $cssFile = api_get_path(SYS_CSS_PATH).'themes/'.api_get_visual_theme().'/frames.css';
            if (!is_file($cssFile)) {
                $cssFile = api_get_path(WEB_CSS_PATH).'frames.css';
            } else {
                $cssFile = api_get_path(WEB_CSS_PATH).'themes/'.api_get_visual_theme().'/frames.css';
            }
            $style = '<link href="'.$cssFile.'" rel="stylesheet" media="screen" type="text/css" />';
        }
        $html = '<textarea id="'.$this->getName().'" name="'.$this->getName().'" class="ckeditor">
                 '.$style.$this->value.'
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
     * @param array $templates
     * @return null|string
     */
    public function simpleFormatTemplates($templates)
    {
        if (empty($templates)) {
            return null;
        }

        $search = array('{CSS}', '{IMG_DIR}', '{REL_PATH}', '{COURSE_DIR}');
        $replace = array(
            '',
            api_get_path(REL_CODE_PATH).'img/',
            api_get_path(REL_PATH),
            api_get_path(REL_DEFAULT_COURSE_DOCUMENT_PATH),
            api_get_path(REL_DEFAULT_COURSE_DOCUMENT_PATH)
        );

        $templateList = array();

        foreach ($templates as $template) {
            $image = $template['image'];
            $image = !empty($image) ? $image : 'empty.gif';
            $image = api_get_path(WEB_APP_PATH).'home/default_platform_document/template_thumb/'.$image;

            /*$image = $this->urlGenerator->generate(
                'get_document_template_action',
                array('file' => $image),
                UrlGenerator::ABSOLUTE_URL
            );*/

            $content = str_replace($search, $replace, $template['content']);

            $templateList[] = array(
                'title' => get_lang($template['title']),
                'description' => get_lang($template['comment']),
                'image' => $image,
                'html' => $content
            );
        }


        return json_encode($templateList);
    }
}
