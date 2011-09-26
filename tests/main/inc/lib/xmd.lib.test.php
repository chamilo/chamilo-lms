<?php
class TestXmd extends UnitTestCase{

	public $Xmddoc;
	public function TestXmddoc(){

		$this->UnitTestCase('XML Dom Library function tests');
	}

	public function setUp(){
		$this->Xmddoc = new xmddoc();
	}

	public function tearDown(){
		$this->Xmddoc= null;
	}

	public function testXmdGetElement(){
		$parent=0;
		array('?name' => $this->name[$parent],
              '?parent' => $this->parent[$parent]);
		$res = $this->Xmddoc->xmd_get_element($parent);
		if(!is_array($res))
		$this->assertTrue(is_null($res));
		else
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testXmdGetNsUri(){
		$parent = 0;
		$attName = '';
		$this->names[$this->ns[$parent]];
		$res = Xmddoc::xmd_get_ns_uri($parent = 0, $attName = '');
		if(is_array($res))
		$this->assertTrue(is_array($res));
		else
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testXmdRemoveElement(){
		$child='';
		$res = Xmddoc:: xmd_remove_element($child);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res ===false);
		//var_dump($res);
	}

	public function testXmdRemoveNodes(){
		$children=2;
		$parent = 1;
		$res = Xmddoc::xmd_remove_nodes($children, $parent = 0);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	/**
	 *
	 */
	public function testXmdUpdate(){
		$xmPath='';
		$text = '';
		$parent = 0;
		$res = $this->Xmddoc->xmd_update($xmPath, $text, $parent);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	/**
	 * this function have work with the method xmddoc
	 */
	public function testXmdUpdateMany(){
		$xmPaths='';
		$subPath='';
		$text='';
		$parent='';
		$res = $this->Xmddoc->xmd_update_many($xmPaths, $subPath, $text, $parent);
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testXmdCopyForeignChild(){
		$fdoc='';
		$fchild='';
		$parent='';
		$res = $this->Xmddoc->xmd_copy_foreign_child($fdoc, $fchild, $parent);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testXmdAddElement(){
		$name='asasasas';
		$parent = 0;
		$attribs = array();
		$res  = $this->Xmddoc->xmd_add_element($name, $parent, $attribs);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res === -1 || $res === 0);
		//var_dump($res);
	}

	public function testXmdSetAttribute(){
		$parent=0;
		$name='';
		$value=0;
		$checkurihaspfx = TRUE;
		$res = $this->Xmddoc->xmd_set_attribute($parent, $name, $value, $checkurihaspfx);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testXmdAddText(){
		$text='asasasasasa';
		$parent = 1;
		$res = $this->Xmddoc->xmd_add_text($text, $parent = 0);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}

	public function testXmdAddTextElement(){
		$name='';
		$text='';
		$parent = 0;
		$attribs = array();
		$res = $this->Xmddoc->xmd_add_text_element($name, $text, $parent = 0, $attribs = array());
		$this->assertTrue(is_bool($res) || is_numeric($res));
		//var_dump($res);
	}

	public function testXmdText(){
		$parent = 0;
		$res =$this->Xmddoc->xmd_text($parent = 0);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testXmdXml(){
		$increase = '  ';
		$indent = '';
		$lbr = "\n";
		$parent = 0;
		$res = $this->Xmddoc->xmd_xml($increase, $indent, $lbr, $parent);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testXmdValue($xmPath, $parent, $fix, $fun){
		$xmPath='';
		$parent = 0;
		$fix = array();
		$fun = '';
		$res = $this->Xmddoc->xmd_value($xmPath, $parent, $fix, $fun);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testXmdHtmlValue(){
		$xmPath='';
		$parent = 0;
		$fun = 'htmlspecialchars';
		$res = $this->Xmddoc->xmd_html_value($xmPath, $parent , $fun );
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testXmdSelectSingleElement(){
		$xmPath='';
		$parent = 0;
		$res = $this->Xmddoc->xmd_select_single_element($xmPath, $parent);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testXmdSelectElementsWhere(){
		$xmPath='';
		$subPath = '.';
		$value = '';
		$parent = 0;
		$res = $this->Xmddoc->xmd_select_elements_where($xmPath, $subPath, $value, $parent);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testXmdSelectElementsWhereNotempty(){
		$xmPath='';
		$subPath = '.';
		$parent = 0;
		$res = $this->Xmddoc->xmd_select_elements_where_notempty($xmPath,$subPath, $parent);
        $this->assertTrue(is_array($res));
        //var_dump($res);
    }

    public function testxmd_select_elements(){
    	$xmPath='';
    	$parent = 0;
    	$res = $this->Xmddoc->xmd_select_elements($xmPath, $parent);
    	$this->assertTrue(is_array($res));
    	//var_dump($res);
    }

    public function testXmdSelectElements(){
    	$xmPath='';
    	$parent = 0;
    	$res = $this->Xmddoc-> xmd_select_elements($xmPath, $parent);
    	$this->assertTrue(is_array($res));
    	//var_dump($res);
    }
}
?>
