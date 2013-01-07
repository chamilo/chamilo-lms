<?php
/* For licensing terms, see /license.txt */
/**
 * Document controller class definition
 * @package chamilo.document
 */
/**
 * Init
 */

class DocumentController {
	function __construct($title = null) {
		$this->tpl = new Template($title);	
	}
}
