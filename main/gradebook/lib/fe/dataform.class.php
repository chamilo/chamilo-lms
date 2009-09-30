<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
require_once (dirname(__FILE__).'/../../../inc/global.inc.php');
require_once (dirname(__FILE__).'/../be.inc.php');
require_once (dirname(__FILE__).'/../gradebook_functions.inc.php');
require_once (api_get_path(LIBRARY_PATH) . 'groupmanager.lib.php');
require_once (api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php');

/**
 * Extends formvalidator with import and export forms
 * @author Stijn Konings
 * @package dokeos.gradebook
 */
class DataForm extends FormValidator {

	const TYPE_IMPORT = 1;
	const TYPE_EXPORT = 2;
	const TYPE_EXPORT_PDF = 3;


	/**
	 * Builds a form containing form items based on a given parameter
	 * @param int form_type 1=import, 2=export
	 * @param obj cat_obj the category object
	 * @param obj res_obj the result object
	 * @param string form name
	 * @param method
	 * @param action
	 */
	function DataForm($form_type, $form_name, $method = 'post', $action = null,$target='') {
		parent :: __construct($form_name, $method, $action,$target);
		$this->form_type = $form_type;
		if ($this->form_type == self :: TYPE_IMPORT) {
			$this->build_import_form();
		}
		elseif ($this->form_type == self :: TYPE_EXPORT) {
			$this->build_export_form();
		}
		elseif ($this->form_type == self :: TYPE_EXPORT_PDF) {
			$this->build_pdf_export_form();
		}
		$this->setDefaults();
	}


	protected function build_pdf_export_form() {
		$renderer =& $this->defaultRenderer();
		$renderer->setElementTemplate('<span>{element}</span> ');
		$this->addElement('static','label','',get_lang('ChooseOrientation'));
		$this->addElement('radio', 'orientation', null, get_lang('Portrait'), 'portrait');
		$this->addElement('radio', 'orientation', null, get_lang('Landscape'), 'landscape');
		$this->addElement('submit', 'submit', get_lang('Ok'));
		$this->setDefaults(array (
			'orientation' => 'portrait'
		));
	}


	protected function build_export_form() {
		$this->addElement('header','label',get_lang('ChooseFormat'));
		$this->addElement('radio', 'file_type', get_lang('OutputFileType'), 'CSV (Comma-Separated Values)', 'csv');
		$this->addElement('radio', 'file_type', null, 'XML (Extensible Markup Language)', 'xml');
		$this->addElement('radio', 'file_type', null, 'PDF (Portable Document Format)', 'pdf');
		$this->addElement('submit', 'submit', get_lang('Ok'));
		$this->setDefaults(array (
			'file_type' => 'csv'
		));
	}

	protected function build_import_form() {
		$this->addElement('hidden', 'formSent');
		$this->addElement('header','label',get_lang('ImportFileLocation'));
		$this->addElement('file', 'import_file',get_lang('Location'));
		$allowed_file_types = array (
			'xml',
			'csv'
		);
		$this->addRule('file', get_lang('InvalidExtension') . ' (' . implode(',', $allowed_file_types) . ')', 'filetype', $allowed_file_types);
		$this->addElement('radio', 'file_type', get_lang('FileType'), 'CSV (<a href="docs/example_csv.html" target="_blank">' . get_lang('ExampleCSVFile') . '</a>)', 'csv');
		$this->addElement('radio', 'file_type', null, 'XML (<a href="docs/example_xml.html" target="_blank">' . get_lang('ExampleXMLFile') . '</a>)', 'xml');
		$this->addElement('checkbox','overwrite', null,get_lang('OverwriteScores'));
		$this->addElement('checkbox','ignoreerrors',null,get_lang('IgnoreErrors'));
		$this->addElement('submit', 'submit', get_lang('Ok'));
		$this->setDefaults(array(
		'formSent' => '1',
		'file_type' => 'csv'
		));
	}

	function display() {
		parent :: display();
	}

	function setDefaults($defaults = array ()) {
		parent :: setDefaults($defaults);
	}
}