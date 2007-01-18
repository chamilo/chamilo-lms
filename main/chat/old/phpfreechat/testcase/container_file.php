<?php

require_once "container_generic.php";

class pfcContainerTestcase_File extends pfcContainerTestcase
{
  // constructor of the test suite
  function pfcContainerTestcase_File($name)
  {
    $this->type = "File";
    $this->pfcContainerTestcase($name);
  }
  
  // called before the test functions will be executed
  // this function is defined in PHPUnit_TestCase and overwritten
  // here
  function setUp()
  {
    pfcContainerTestcase::setUp();
  }

  // called after the test functions are executed
  // this function is defined in PHPUnit_TestCase and overwritten
  // here
  function tearDown()
  {
    pfcContainerTestcase::tearDown();   
  }

  function test_setMeta_File_1()
  {
    $c  =& $this->c;
    $ct =& $this->ct;

    $prefix    = __FUNCTION__;
    $group     = $prefix."_nickid-to-channelid";
    $subgroup  = $prefix."_nickid1";
    $leaf      = $prefix."_channelid1";
    $ret = $ct->setMeta($group, $subgroup, $leaf);
    $this->assertEquals($ret, 0, "the leaf should be first time created");

    $f = $c->container_cfg_server_dir.'/'.$group.'/'.$subgroup.'/'.$leaf;
    $ret = file_exists($f);
    $this->assertEquals($ret, true, "the leaf file should exists");

    $ret = file_get_contents($f);
    $this->assertEquals($ret, '', "the leaf file should contain nothing");
  }

  function test_setMeta_File_2()
  {
    $c  =& $this->c;
    $ct =& $this->ct;

    $prefix    = __FUNCTION__;
    $group     = $prefix."_nickid-to-channelid";
    $subgroup  = $prefix."_nickid1";
    $leaf      = $prefix."_channelid1";
    $leafvalue = $prefix."_leafvalue1";
    $ret = $ct->setMeta($group, $subgroup, $leaf, $leafvalue);
    $this->assertEquals($ret, 0, "the leaf should be first time created");

    $f = $c->container_cfg_server_dir.'/'.$group.'/'.$subgroup.'/'.$leaf;
    $ret = file_exists($f);
    $this->assertEquals($ret, true, "the leaf file should exists");

    $ret = file_get_contents($f);
    $this->assertEquals($ret, $leafvalue, "the leaf file should contain the value");
  }

  function test_setMeta_File_3()
  {
    $c  =& $this->c;
    $ct =& $this->ct;

    $prefix    = __FUNCTION__;
    $group     = $prefix."_nickid-to-channelid";
    $subgroup  = $prefix."_nickid1";
    $leaf      = $prefix."_channelid1";
    $leafvalue = $prefix."_leafvalue1";

    $ret = $ct->setMeta($group, $subgroup, $leaf, $leafvalue);
    $this->assertEquals($ret, 0, "the leaf should be first time created");

    $leafvalue = null;
    $ret = $ct->setMeta($group, $subgroup, $leaf, $leafvalue);
    $this->assertEquals($ret, 1, "the leaf should be overwritten");

    $f = $c->container_cfg_server_dir.'/'.$group.'/'.$subgroup.'/'.$leaf;
    $ret = file_exists($f);
    $this->assertEquals($ret, true, "the leaf file should exists");

    $ret = file_get_contents($f);
    $this->assertEquals($ret, '', "the leaf file should contain nothing");
  }


  function test_rmMeta_File_1()
  {
    $c  =& $this->c;
    $ct =& $this->ct;

    $prefix    = __FUNCTION__;
    $group     = $prefix."_nickid-to-channelid";
    $subgroup  = $prefix."_nickid1";
    $leaf      = $prefix."_channelid1";
    $ret = $ct->setMeta($group, $subgroup, $leaf);

    $ret = $ct->rmMeta($group, $subgroup, $leaf);
    $this->assertEquals($ret, true, "the returned value should be true (rm success)");

    $f = $c->container_cfg_server_dir.'/'.$group.'/'.$subgroup.'/'.$leaf;
    $ret = file_exists($f);
    $this->assertEquals($ret, false, "the leaf file should not exists anymore");
  }

  function test_rmMeta_File_2()
  {
    $c  =& $this->c;
    $ct =& $this->ct;

    $prefix    = __FUNCTION__;
    $group     = $prefix."_nickid-to-channelid";
    $subgroup  = $prefix."_nickid1";
    $leaf1      = $prefix."_channelid1";
    $leaf2      = $prefix."_channelid2";
    $ret = $ct->setMeta($group, $subgroup, $leaf1);
    $ret = $ct->setMeta($group, $subgroup, $leaf2);

    $ret = $ct->rmMeta($group, $subgroup);
    $this->assertEquals($ret, true, "the returned value should be true (rm success)");

    $f = $c->container_cfg_server_dir.'/'.$group.'/'.$subgroup.'/'.$leaf1;
    $ret = file_exists($f);
    $this->assertEquals($ret, false, "the leaf file should not exists anymore");
    $f = $c->container_cfg_server_dir.'/'.$group.'/'.$subgroup.'/'.$leaf2;
    $ret = file_exists($f);
    $this->assertEquals($ret, false, "the leaf file should not exists anymore");

    $d = $c->container_cfg_server_dir.'/'.$group.'/'.$subgroup;
    $ret = file_exists($f);
    $this->assertEquals($ret, false, "the subgroup directory should not exists anymore");
  }

  function test_rmMeta_File_3()
  {
    $c  =& $this->c;
    $ct =& $this->ct;

    $prefix    = __FUNCTION__;
    $group     = $prefix."_nickid-to-channelid";
    $subgroup  = $prefix."_nickid1";
    $leaf1      = $prefix."_channelid1";
    $leaf2      = $prefix."_channelid2";
    $ret = $ct->setMeta($group, $subgroup, $leaf1);
    $ret = $ct->setMeta($group, $subgroup, $leaf2);

    $ret = $ct->rmMeta($group);
    $this->assertEquals($ret, true, "the returned value should be true (rm success)");

    $f = $c->container_cfg_server_dir.'/'.$group.'/'.$subgroup.'/'.$leaf1;
    $ret = file_exists($f);
    $this->assertEquals($ret, false, "the leaf file should not exists anymore");
    $f = $c->container_cfg_server_dir.'/'.$group.'/'.$subgroup.'/'.$leaf2;
    $ret = file_exists($f);
    $this->assertEquals($ret, false, "the leaf file should not exists anymore");

    $d = $c->container_cfg_server_dir.'/'.$group.'/'.$subgroup;
    $ret = file_exists($d);
    $this->assertEquals($ret, false, "the subgroup directory should not exists anymore");

    $d = $c->container_cfg_server_dir.'/'.$group;
    $ret = file_exists($d);
    $this->assertEquals($ret, false, "the group directory should not exists anymore");
  }
}

// on desactive le timeout car se script peut mettre bcp de temps a s'executer
ini_set('max_execution_time', 0);

$suite = new PHPUnit_TestSuite();
$suite->addTestSuite("pfcContainerTestcase_File");
$result =& PHPUnit::run($suite);
echo "<pre>";
print_r($result->toString());
echo "</pre>";

?>