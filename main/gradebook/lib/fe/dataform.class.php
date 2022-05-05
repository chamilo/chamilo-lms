<?php
/* For licensing terms, see /license.txt */

/**
 * Extends FormValidator with import and export forms.
 *
 * @author Stijn Konings
 */
class DataForm extends FormValidator
{
    public const TYPE_IMPORT = 1;
    public const TYPE_EXPORT = 2;
    public const TYPE_EXPORT_PDF = 3;

    /**
     * Builds a form containing form items based on a given parameter.
     *
     * @param int form_type 1=import, 2=export
     * @param obj cat_obj the category object
     * @param obj res_obj the result object
     * @param string form name
     * @param method
     * @param action
     */
    public function __construct(
        $form_type,
        $form_name,
        $method = 'post',
        $action = null,
        $target = '',
        $locked_status
    ) {
        parent::__construct($form_name, $method, $action, $target);
        $this->form_type = $form_type;
        if ($this->form_type == self::TYPE_IMPORT) {
            $this->build_import_form();
        } elseif ($this->form_type == self::TYPE_EXPORT) {
            if ($locked_status == 0) {
                $this->build_export_form_option(false);
            } else {
                $this->build_export_form();
            }
        } elseif ($this->form_type == self::TYPE_EXPORT_PDF) {
            $this->build_pdf_export_form();
        }
        $this->setDefaults();
    }

    public function display()
    {
        parent::display();
    }

    public function setDefaults($defaults = [], $filter = null)
    {
        parent::setDefaults($defaults, $filter);
    }

    protected function build_pdf_export_form()
    {
        $renderer = &$this->defaultRenderer();
        $renderer->setCustomElementTemplate('<span>{element}</span>');
        $this->addElement('header', get_lang('ChooseOrientation'));
        $this->addElement('radio', 'orientation', null, get_lang('Portrait'), 'portrait');
        $this->addElement('radio', 'orientation', null, get_lang('Landscape'), 'landscape');
        $this->addButtonExport(get_lang('Export'));
        $this->setDefaults([
            'orientation' => 'portrait',
        ]);
    }

    protected function build_export_form()
    {
        $this->addElement('header', get_lang('ChooseFormat'));
        $this->addElement('radio', 'file_type', get_lang('OutputFileType'), 'CSV (Comma-Separated Values)', 'csv');
        $this->addElement('radio', 'file_type', null, 'XML (Extensible Markup Language)', 'xml');
        $this->addElement('radio', 'file_type', null, 'PDF (Portable Document Format)', 'pdf');
        $this->addButtonExport(get_lang('Export'));
        $this->setDefaults([
            'file_type' => 'csv',
        ]);
    }

    protected function build_export_form_option($show_pdf = true)
    {
        $this->addElement('header', get_lang('ChooseFormat'));
        $this->addElement('radio', 'file_type', get_lang('OutputFileType'), 'CSV (Comma-Separated Values)', 'csv');
        $this->addElement('radio', 'file_type', null, 'XML (Extensible Markup Language)', 'xml');
        $this->addElement(
            'radio',
            'file_type',
            Display::return_icon('info3.gif', get_lang('ToExportMustLockEvaluation')),
            'PDF (Portable Document Format)',
            'pdf',
            ['disabled']
        );
        $this->addButtonExport(get_lang('Export'));
        $this->setDefaults([
            'file_type' => 'csv',
        ]);
    }

    protected function build_import_form()
    {
        $this->addElement('hidden', 'formSent');
        $this->addElement('header', get_lang('ImportFileLocation'));
        $this->addElement('file', 'import_file', get_lang('Location'));
        $this->addElement(
            'radio',
            'file_type',
            get_lang('FileType'),
            'CSV (<a href="docs/example_csv.html" target="_blank" download>'
                .get_lang('ExampleCSVFile')
                .'</a>)',
            'csv'
        );
        //$this->addElement('radio', 'file_type', null, 'XML (<a href="docs/example_xml.html" target="_blank" download>'.get_lang('ExampleXMLFile').'</a>)', 'xml');
        $this->addElement('checkbox', 'overwrite', null, get_lang('OverwriteScores'));
        $this->addElement('checkbox', 'ignoreerrors', null, get_lang('IgnoreErrors'));
        $this->addButtonImport(get_lang('Ok'));
        $this->setDefaults([
            'formSent' => '1',
            'file_type' => 'csv',
        ]);
    }
}
