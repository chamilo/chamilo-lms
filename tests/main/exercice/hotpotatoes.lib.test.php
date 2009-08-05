<?php
require_once(api_get_path(SYS_CODE_PATH).'exercice/hotpotatoes.lib.php');

class TestHotpotatoes extends UnitTestCase {
	
	function testCheckImageName() {
		$imgparams=array();
		$string='';
		$checked = myarraysearch($imgparams,$string);
		$res=CheckImageName(&$imgparams,$string);
		$this->assertTrue(is_bool($res));
		$this->assertTrue(is_bool($checked));
		//var_dump($res);
	}
	
	function testCheckSubFolder() {
		$path='Location: /main/exercice/';
		$res=CheckSubFolder($path);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	function testFillFolderName() {
		$name='12doceletras';
		$nsize=12;
		$res=FillFolderName($name,$nsize);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	function testGenerateHiddenList() {
		$imgparams=array('abc');
		$res=GenerateHiddenList($imgparams);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	function testGenerateHpFolder() {
		$folder='main/exercice/hotpotatoes.lib.php';
		$res=GenerateHpFolder($folder);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	function testGetComment() {
		global $dbTable;
		$path = 'test';
		$query = "select 1";
		$result = api_sql_query($query,__FILE__,__LINE__);
		$row = mysql_fetch_array($result);
		$res=GetComment($path);
		$this->assertTrue(is_string($res));
		$this->assertTrue(is_array($row));
		//var_dump($res);
		//var_dump($row);
	}
	
	/*  Deprecated 
	function testGetFileName() {
		$fname='main/exercice/hotpotatoes.lib.php';
		$res=GetFileName($fname);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}*/
	 
	function testGetFolderName() {
		$fname='main/exercice/hotpotatoes.lib.php';
		$res=GetFolderName($fname);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	function testGetFolderPath() {
		$fname='main/exercice/hotpotatoes.lib.php';
		$res=GetFolderPath($fname);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	function testGetImgName() {
		$imgtag='<img src="example.jpg">';
		$res=GetImgName($imgtag);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}	
	
	function testGetImgParams() {
		$fname='test.jpg';
		$fpath='main/exercice/test.jpg';
		$imgparams=array();
		$imgcount=$imgcount + 1;;	
		$res=GetImgParams($fname,$fpath,&$imgparams,&$imgcount);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testGetQuizName() {
		$fname='exercice_submit.php';
		$fpath='main/exercice/exercice_submit.php';
		$title = GetComment($fname);
		$fp = fopen($fpath.$fname, "r");
		$pattern = array ( 1 => "title>", 2 => "/title>");
		$contents = fread($fp, filesize($fpath.$fname));
		fclose($fp);
		$contents = api_strtolower($contents);
		$s_contents = api_substr($contents,0,api_strpos($contents,$pattern["2"])-1);
		$e_contents = api_substr($s_contents,api_strpos($contents,$pattern["1"])+api_strlen($pattern["1"]),api_strlen($s_contents));
		$title = $e_contents;
		$res=GetQuizName($fname,$fpath);
		$this->assertTrue(is_string($res));
		//var_dump($e_contents);	
	}
	
	function testGetSrcName() {	
		$imgtag='src="test.jpg""';
		$res=GetSrcName($imgtag);
		if(!is_string($res))$this->assertTrue(is_bool($res));
		//var_dump($res);	
	}
	
	function testhotpotatoes_init() {
		$baseWorkDir='/main/exercice';
		$res=hotpotatoes_init($baseWorkDir);
		$this->assertTrue(is_bool($res));
		//var_dump($res);	
	}
	
	function testHotPotGCt() { 
		$folder='/main/exercice';
		$flag=4;
		$userID=1;
		$res=HotPotGCt($folder,$flag,$userID);
		$this->assertTrue(is_null($res));
		//var_dump($res);	
	}
	
	function testmyarraysearch() {
		$array=array();
		$node='';
		$res=myarraysearch($array,$node);
		if(!is_bool($res))$this->assertTrue(is_null($res));
		//var_dump($res);	
	}
	
	function testReadFileCont() {
		$full_file_path='';
		$res=ReadFileCont($full_file_path);
		if(!is_bool($res))$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	
	
	
	
	
	
	
	
	
	
}
?>
