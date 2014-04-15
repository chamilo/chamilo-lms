<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor\CkEditor;

use ChamiloLMS\Component\Editor\Editor;
use ChamiloLMS\Component\Editor\CkEditor\Toolbar;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class CkEditor
 * @package ChamiloLMS\Component\Editor\CkEditor
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
        $jsFolder = api_get_path(WEB_LIBRARY_JS_PATH);
        $this->template->addResource($jsFolder.'ckeditor/ckeditor.js', 'js');
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
     * @return string
     */
    public function editorReplace()
    {
        $toolbar = new Toolbar\Basic($this->urlGenerator, $this->toolbarSet, $this->config, 'CkEditor');
        $toolbar->setLanguage($this->translator->getLocale());
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
     * @param array
     * @return null
     */
    public function formatTemplates($templates)
    {
        if (empty($templates)) {
            return null;
        }
        /** @var \ChamiloLMS\Entity\SystemTemplate $template */
        $templateList = array();

        $search = array('{CSS}', '{IMG_DIR}', '{REL_PATH}', '{COURSE_DIR}');
        $replace = array(
            '',
            api_get_path(REL_CODE_PATH).'img/',
            api_get_path(REL_PATH),
            api_get_path(REL_DEFAULT_COURSE_DOCUMENT_PATH),
            api_get_path(REL_DEFAULT_COURSE_DOCUMENT_PATH)
        );

        foreach ($templates as $template) {
            $image = $template->getImage();
            $image = !empty($image) ? $image : 'empty.gif';

            $image = $this->urlGenerator->generate(
                'get_document_template_action',
                array('file' => $image),
                UrlGenerator::ABSOLUTE_URL
            );

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
}
