<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\CkEditor;

class TinyMceEditor extends Editor
{

    /**
     * @param string $name
     * @param \Symfony\Component\Translation\Translator $translator
     */
    public function __construct($name, \Symfony\Component\Translation\Translator $translator)
    {
        $this->name = $name;
        $this->toolbarSet   = 'Basic';
        $this->value        = '';
        $this->config       = array();
        $this->setConfigAttribute('width', '100%');
        $this->setConfigAttribute('height', '200');
        $this->setConfigAttribute('fullPage', false);
        $this->translator = $translator;
    }

    /**
     * Return the HTML code required to run editor.
     *
     * @return string
     */
    public function createHtml()
    {
        $Html = '<textarea id="'.$this->name.'" name="'.$this->name.'" class="ckeditor" >'.$this->value.'</textarea>';
        $Html .= $this->editorReplace();

        return $Html;
    }

    /**
     * @return string
     */
    public function editorReplace()
    {
        $toolbar  = new Toolbar\Basic($this->toolbarSet);
        $toolbar->setLanguage($this->translator->getLocale());
        $config = $toolbar->getConfig();
        $javascript = $this->toJavascript($config);
        $html = "<script>
           CKEDITOR.replace('".$this->name."',
               $javascript
           );
           </script>";

        return $html;
    }
}
