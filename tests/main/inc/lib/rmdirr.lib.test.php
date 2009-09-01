<?php

/**
 * Testing the function rmdirr() for recursive directory deletion.
 * This test is an adaptation of a published sample test.
 * @link http://aidanlister.com/2004/04/recursively-deleting-a-folder-in-php/
 * @author Aidan Lister, April, 2004
 * @author Ivan Tcholakov, September, 2009 - adaptation for the Dokeos LMS.
 */

class Test_RmDirRFunction extends UnitTestCase {

	function Test_RmDirRFunction() {
        $this->UnitTestCase('Testing the function rmdirr() for recursive directory deletion');
	}

	public function test_rmdirr() {

		$current_dir = dirname(__FILE__).'/';
		$test_dir = $current_dir.'../../../../archive/'; // Write-access is needed for this directory.
		$test_dir = realpath($test_dir).'/';

		// Let us not clean backwars slashes on Windows, intentionally.
		//$test_dir = str_replace('\\', '/', $test_dir);

		// Create a directory and file tree
		mkdir($test_dir.'testdelete');
		mkdir($test_dir.'testdelete/one-a');
		touch($test_dir.'testdelete/one-a/testfile');
		mkdir($test_dir.'testdelete/one-b');

		// Add some hidden files for good measure
		touch($test_dir.'testdelete/one-b/.hiddenfile');
		mkdir($test_dir.'testdelete/one-c');
		touch($test_dir.'testdelete/one-c/.hiddenfile');

		// Add some more depth
		mkdir($test_dir.'testdelete/one-c/two-a');
		touch($test_dir.'testdelete/one-c/two-a/testfile');
		mkdir($test_dir.'testdelete/one-d/');

		// Test that symlinks are not followed
		// The function symlink() does not work on some Windows OS versions. For these cases this part of the test is skipped.
		$function_symlink_exists = function_exists('symlink');
		if ($function_symlink_exists) {
			mkdir($test_dir.'testlink');
			touch($test_dir.'testlink/testfile');
			symlink($test_dir.'testlink/testfile', 'testdelete/one-d/my-symlink');
			symlink($test_dir.'testlink', 'testdelete/one-d/my-symlink-dir');
		}

		// Run the actual delete
		$status = rmdirr($test_dir.'testdelete');

		// Check if we passed the test
		if ($status === true && !file_exists($test_dir.'testdelete') && ($function_symlink_exists ? file_exists($test_dir.'testlink/testfile') : true)) {
			//echo 'TEST PASSED';
			$res = true;
		} else {
			//echo 'TEST FAILED';
			$res = false;
		}
		if ($function_symlink_exists) {
			@rmdirr($test_dir.'testlink');
		}

		// Pass the result of this test
		$this->assertTrue($res === true);
		//var_dump($test_dir);
	}

}
