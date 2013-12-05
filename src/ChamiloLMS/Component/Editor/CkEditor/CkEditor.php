<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor\CkEditor;

use ChamiloLMS\Component\Editor\Editor;
use ChamiloLMS\Component\Editor\CkEditor\Toolbar;

/**
 * Class CkEditor
 * @package ChamiloLMS\Component\Editor\CkEditor
 */
class CkEditor extends Editor
{

    /**
     * @return string
     */
    public function getTemplate()
    {
        return 'javascript/editor/ckeditor/elfinder.tpl';
    }

    /**
     * @param array $files
     */
    public function getJavascriptToInclude(& $files)
    {
        $jsFolder = api_get_path(WEB_LIBRARY_PATH).'javascript/';
        $files[] = $jsFolder.'ckeditor/ckeditor.js';
    }

    /**
     * Return the HTML code required to run editor.
     *
     * @return string
     */
    public function createHtml()
    {
        $html = '<textarea id="'.$this->getName().'" name="'.$this->getName().'" class="ckeditor" >'.$this->value.'</textarea>';
        $html .= $this->editorReplace();

        return $html;
    }

    /**
     * @return string
     */
    public function editorReplace()
    {
        $toolbar  = new Toolbar\Basic($this->toolbarSet, $this->config, 'CkEditor');
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
}
