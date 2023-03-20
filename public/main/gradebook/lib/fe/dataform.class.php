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
        parent:: __construct($form_name, $method, $action, $target);
        $this->form_type = $form_type;
        if (self::TYPE_IMPORT == $this->form_type) {
            $this->build_import_form();
        } elseif (self::TYPE_EXPORT == $this->form_type) {
            if (0 == $locked_status) {
                $this->build_export_form_option(false);
            } else {
                $this->build_export_form();
            }
        } elseif (self::TYPE_EXPORT_PDF == $this->form_type) {
            $this->build_pdf_export_form();
        }
        $this->setDefaults();
    }

    public function display()
    {
        parent::display();
    }

    public function setDefaults($defaultValues = [], $filter = null)
    {
        parent::setDefaults($defaultValues, $filter);
    }

    protected function build_pdf_export_form()
    {
        $renderer = &$this->defaultRenderer();
        $renderer->setCustomElementTemplate('<span>{element}</span>');
        $this->addElement('header', get_lang('Choose orientation'));
        $this->addElement('radio', 'orientation', null, get_lang('Portrait'), 'portrait');
        $this->addElement('radio', 'orientation', null, get_lang('Landscape'), 'landscape');
        $this->addButtonExport(get_lang('Export'));
        $this->setDefaults([
            'orientation' => 'portrait',
        ]);
    }

    protected function build_export_form()
    {
        $this->addElement('header', get_lang('PDF report'));
        $this->addElement('radio', 'file_type', get_lang('Output file type'), 'CSV (Comma-Separated Values)', 'csv');
        $this->addElement('radio', 'file_type', null, 'XML (Extensible Markup Language)', 'xml');
        $this->addElement('radio', 'file_type', null, 'PDF (Portable Document Format)', 'pdf');
        $this->addButtonExport(get_lang('Export'));
        $this->setDefaults([
            'file_type' => 'csv',
        ]);
    }

    protected function build_export_form_option($show_pdf = true)
    {
        $this->addElement('header', get_lang('PDF report'));
        $this->addElement('radio', 'file_type', get_lang('Output file type'), 'CSV (Comma-Separated Values)', 'csv');
        $this->addElement('radio', 'file_type', null, 'XML (Extensible Markup Language)', 'xml');
        $this->addElement(
            'radio',
            'file_type',
            Display::return_icon('info3.gif', get_lang('To export, you must lock the evaluation.')),
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
        $this->addElement('header', get_lang('Import marks in an assessment'));
        $this->addElement('file', 'import_file', get_lang('URL/URI'));
        $this->addElement(
            'radio',
            'file_type',
            get_lang('File type'),
            'CSV (<a href="docs/example_csv.html" target="_blank" download>'
                .get_lang('Example CSV file')
                .'</a>)',
            'csv'
        );
        //$this->addElement('radio', 'file_type', null, 'XML (<a href="docs/example_xml.html" target="_blank" download>'.get_lang('Example XML file').'</a>)', 'xml');
        $this->addElement('checkbox', 'overwrite', null, get_lang('Overwrite scores'));
        $this->addElement('checkbox', 'ignoreerrors', null, get_lang('Ignore errors'));
        $this->addButtonImport(get_lang('Validate'));
        $this->setDefaults([
            'formSent' => '1',
            'file_type' => 'csv',
        ]);
    }
}
