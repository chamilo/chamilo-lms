<?php

class DocumentController {
	function __construct($title = null) {
		$this->tpl = new Template($title);	
	}
}