<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor\TinyMce;

use ChamiloLMS\Component\Editor\Editor;
use ChamiloLMS\Component\Editor\TinyMce\Toolbar;

/**
 * Class TinyMce
 * @package ChamiloLMS\Component\Editor\TinyMce
 */
class TinyMce extends Editor
{
    /**
     * @param array $files
     */
    public function getJavascriptToInclude(& $files)
    {
        $jsFolder = api_get_path(WEB_LIBRARY_PATH).'javascript/';
        $files[] = $jsFolder.'tinymce/tinymce.min.js';
    }

    /**
     * @return string
     */
    public function getEditorTemplate()
    {
        return 'javascript/editor/tinymce/elfinder.tpl';
    }

    /**
     * Return the HTML code required to run editor.
     *
     * @return string
     */
    public function createHtml()
    {
        $html = '<textarea id="'.$this->name.'" name="'.$this->name.'" class="ckeditor" >'.$this->value.'</textarea>';
        $html .= $this->editorReplace();

        return $html;
    }

    /**
     * @return string
     */
    public function editorReplace()
    {
        $toolbar  = new Toolbar\Basic($this->urlGenerator, $this->toolbarSet, $this->config, 'TinyMce');
        $toolbar->setLanguage($this->translator->getLocale());
        $config = $toolbar->getConfig();
        $config['selector'] = "#".$this->name;
        $javascript = $this->toJavascript($config);
        $javascript = str_replace('"elFinderBrowser"', "elFinderBrowser", $javascript);

        $html = "<script>
            function elFinderBrowser (field_name, url, type, win) {
                tinymce.activeEditor.windowManager.open({
                    file: '".$this->urlGenerator->generate('filemanager')."',
                    title: 'elFinder 2.0',
                    width: 900,
                    height: 450,
                    resizable: 'yes'
                    }, {
                    setUrl: function (url) {
                        win.document.getElementById(field_name).value = url;
                    }
                });
                return false;
            }

            $(document).ready(function() {
                tinymce.init(
                    $javascript
                 );
             });
        </script>";

        return $html;
    }
}
