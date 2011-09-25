<?php
require_once(api_get_path(SYS_CODE_PATH).'exercice/hotpotatoes.lib.php');

class TestHotpotatoes extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Hotpotatoes exercises library - main/exercice/hotpotatoes.lib.test.php');
    }

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
		$name='testnameofthefolder';
		$nsize=12;
		$res=FillFolderName($name,$nsize);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testGenerateHiddenList() {
		$imgparams=array('test');
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

			$course_code = 'COURSETEST';
			$path = 'exercice_submit.php';
			$query ="select comment from $dbTable where path='$path'";
			$res=GetComment($path,$course_code);
			$this->assertTrue(is_string($res));
			//var_dump($res);

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
		$fname='/main/css/academica/images/bg.jpg';
		$fpath='main/css/academica/images/';
		$imgparams= array();
		$imgcount='';
		$res=GetImgParams($fname,$fpath,&$imgparams,&$imgcount);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testGetQuizName() {
		$course_code = 'COURSETEST';
		$fname='exercice_submit.php';
		$fpath='main/exercice/exercice_submit.php';
		$res=GetQuizName($fname,$fpath);

		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testGetSrcName() {
		$imgtag='src="test.jpg""';
		$res=GetSrcName($imgtag);
		if(!is_string($res))$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testhotpotatoes_init() {
		$base = api_get_path(SYS_CODE_PATH);
		$baseWorkDir=$base.'exercice/';
		$res=hotpotatoes_init($baseWorkDir);
		$this->assertFalse($res);
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

	function testReplaceImgTag() {
		$content='src="test2.jpg"';
		$res=ReplaceImgTag($content);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testSetComment() {
		global $dbTable;
		$path='/main/exercice';
		$comment='testing this function';
		$comment = Database::escape_string($comment);
		$query = "UPDATE $dbTable set comment='$comment' where path='$path'";
		$result = Database::query($query);
		$res=SetComment($path,$comment);
		$this->assertTrue(is_string($res));
		//var_dump($resu);
	}

	function testWriteFileCont() {
		$course_code = 'COURSETEST';
		$full_file_path='/main/exercice/';
		$content='test test test';
		$res=WriteFileCont($full_file_path,$content);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
}
?>
