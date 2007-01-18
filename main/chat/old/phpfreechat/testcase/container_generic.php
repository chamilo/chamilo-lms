<?php

$delim = DIRECTORY_SEPARATOR == "\\" ? ";" : ":";
$classpath = "." . $delim . dirname(__FILE__).'/../lib/pear/';
ini_set('include_path', $classpath);
require_once "PHPUnit.php";

class pfcContainerTestcase extends PHPUnit_TestCase
{
  var $type   = "";
  
  var $chan   = "testcase";
  var $nick   = "testnick";
  var $nickid = "testnickid";

  var $c  = NULL;
  var $ct = NULL;
  
  // constructor of the test suite
  function pfcContainerTestcase($name)
  {
    $this->PHPUnit_TestCase($name);
  }
  
  // called before the test functions will be executed
  // this function is defined in PHPUnit_TestCase and overwritten
  // here
  function setUp()
  {
    //    echo "setUp<br>";
    require_once dirname(__FILE__)."/../src/pfcglobalconfig.class.php";   
    $params = array();
    $params["title"] = "testcase -> pfccontainer_".$this->type;
    $params["serverid"] = md5(__FILE__/* . time()*/);
    $params["container_type"] = $this->type;
    $this->c  = new pfcGlobalConfig($params);
    $this->ct = $this->c->getContainerInstance();
  }
  
  // called after the test functions are executed
  // this function is defined in PHPUnit_TestCase and overwritten
  // here
  function tearDown()
  {
    //    echo "tearDown<br>";
    $this->ct->clear();
    $this->c->destroyCache();
  }

  function test_createNick_Generic()
  {
    $c  =& $this->c;
    $ct =& $this->ct;
    $prefix = __FUNCTION__;
    $nick   = $prefix . '_' . $this->nick;
    $nickid = $prefix . '_' . $this->nickid;
    $chan   = $prefix . '_' . $this->chan;

    // create on the channel
    $this->ct->createNick($chan, $nick, $nickid);
    $isonline = ($this->ct->isNickOnline($chan, $nickid) >= 0);
    $this->assertTrue($isonline, "nickname should be online on the channel");

    // create on the server
    $chan = NULL;
    $this->ct->createNick($chan, $nick, $nickid);
    $isonline = ($this->ct->isNickOnline($chan, $nickid) >= 0);
    $this->assertTrue($isonline, "nickname should be online on the server");
  }

  function test_removeNick_Generic()
  {
    $c  =& $this->c;
    $ct =& $this->ct;
    $prefix = __FUNCTION__;
    $nick   = $prefix . '_' . $this->nick;
    $nickid = $prefix . '_' . $this->nickid;
    $chan   = $prefix . '_' . $this->chan;

    // on the channel
    $this->ct->createNick($chan, $nick, $nickid);
    $this->ct->removeNick($chan, $nickid);
    $isonline = ($this->ct->isNickOnline($chan, $nickid) >= 0);
    $this->assertFalse($isonline, "nickname shouldn't be online on the channel");   
    $isonline2 = ($this->ct->isNickOnline(NULL, $nickid) >= 0);
    $this->assertTrue($isonline2, "nickname should be online on the server");   

    $this->ct->removeNick(NULL, $nickid);
    $isonline = ($this->ct->isNickOnline(NULL, $nickid) >= 0);
    $this->assertFalse($isonline, "nickname shouldn't be online on the server");
  }

  function test_getNickId_Generic()
  {
    $c  =& $this->c;
    $ct =& $this->ct;
    $prefix = __FUNCTION__;
    $nick   = $prefix . '_' . $this->nick;
    $nickid = $prefix . '_' . $this->nickid;
    $chan   = $prefix . '_' . $this->chan;
 
    $this->ct->createNick(NULL, $nick, $nickid);
    $ret = $this->ct->getNickId($nick);
    $this->assertEquals($nickid, $ret, "created nickname doesn't have a correct nickid");
  }

  function test_getNickname_Generic()
  {
    $c  =& $this->c;
    $ct =& $this->ct;
    $prefix = __FUNCTION__;
    $nick   = $prefix . '_' . $this->nick;
    $nickid = $prefix . '_' . $this->nickid;
    $chan   = $prefix . '_' . $this->chan;

    // on the channel
    $this->ct->createNick($chan, $nick, $nickid);

    $ret = $this->ct->getNickname($nickid);
    $this->assertEquals($nick, $ret, "nickname value is wrong");
  }

  function test_getOnlineNick_Generic()
  {
    $c  =& $this->c;
    $ct =& $this->ct;
    $prefix = __FUNCTION__;
    $nick   = $prefix . '_' . $this->nick;
    $nickid = $prefix . '_' . $this->nickid;
    $chan   = $prefix . '_' . $this->chan;

    // on the channel
    $this->ct->createNick($chan, $nick, $nickid);
    $time = time();
    $ret = $this->ct->getOnlineNick($chan);
    $this->assertEquals(1, count($ret["nickid"]), "1 nickname should be online");
    $this->assertEquals(1, count($ret["nick"]), "1 nickname should be online");
    $this->assertEquals(1, count($ret["timestamp"]), "1 nickname should be online");
    
    $this->assertEquals($time,   $ret["timestamp"][0], "nickname timestamp is wrong");
    $this->assertEquals($nick,   $ret["nick"][0], "nickname value is wrong");
    $this->assertEquals($nickid, $ret["nickid"][0], "nickname id is wrong");
  }

  
  function test_removeObsoleteNick_Generic()
  {
    $c  =& $this->c;
    $ct =& $this->ct;
    $prefix = __FUNCTION__;
    $nick   = $prefix . '_' . $this->nick;
    $nickid = $prefix . '_' . $this->nickid;
    $chan   = $prefix . '_' . $this->chan;

    $this->ct->createNick($chan, $nick, $nickid);
    sleep(2);
    $ret = $this->ct->removeObsoleteNick(1000);
    $this->assertEquals(1, count($ret["nickid"]), "1 nickname should be obsolete");
    $this->assertEquals(2, count($ret["channels"][0]), "nickname should be disconnected from two channels");
    $isonline = ($this->ct->isNickOnline($chan, $nickid) >= 0);
    $this->assertFalse($isonline, "nickname shouldn't be online anymore");
  }
  
  function test_updateNick_Generic()
  {
    $c  =& $this->c;
    $ct =& $this->ct;
    $prefix = __FUNCTION__;
    $nick   = $prefix . '_' . $this->nick;
    $nickid = $prefix . '_' . $this->nickid;
    $chan   = $prefix . '_' . $this->chan;

    $this->ct->createNick($chan, $nick, $nickid);
    sleep(2);
    $ret = $this->ct->updateNick($nickid);
    $this->assertTrue($ret, "nickname should be correctly updated");

    $ret = $this->ct->removeObsoleteNick(1000);
    $this->assertFalse(in_array($nick, $ret['nick']), "nickname shouldn't be removed because it has been updated");
    $isonline = ($this->ct->isNickOnline($chan, $nickid) >= 0);
    $this->assertTrue($isonline, "nickname should be online");
  }
  

  function test_changeNick_Generic()
  {
    $c  =& $this->c;
    $ct =& $this->ct;
    $prefix = __FUNCTION__;
    $nick1   = $prefix . '_' . $this->nick;
    $nick2   = $prefix . '_' . $this->nick.'2';
    $nickid  = $prefix . '_' . $this->nickid;
    $chan    = $prefix . '_' . $this->chan;

    // create a nick on a channel and change it
    $this->ct->createNick($chan, $nick1, $nickid);
    $ret = $this->ct->changeNick($nick2, $nick1);
    $this->assertTrue($ret, "nickname change function should returns true (success)");
    $isonline1 = ($this->ct->isNickOnline($chan, $this->ct->getNickId($nick1)) >= 0);
    $isonline2 = ($this->ct->isNickOnline($chan, $this->ct->getNickId($nick2)) >= 0);
    $this->assertFalse($isonline1, "nickname shouldn't be online");
    $this->assertTrue($isonline2, "nickname shouldn't be online");
  }

  function test_getLastId_Generic()
  {
    $c  =& $this->c;
    $ct =& $this->ct;
    $prefix = __FUNCTION__;
    $nick    = $prefix . '_' . $this->nick;
    $nickid  = $prefix . '_' . $this->nickid;
    $chan    = $prefix . '_' . $this->chan;
    $cmd    = "send";
    $msg    = "my test message";
    
    // on the channel
    $this->ct->createNick($chan, $nick, $nickid);
    for($i = 0; $i < 10; $i++)
    {
      $msgid = $this->ct->write($chan, $nick, $cmd ,$msg . $i);
      $this->assertEquals($msgid, $i+1,"generated msg_id is not correct");
    }
    $msgid = $this->ct->getLastId($chan);
    $this->assertEquals(10, $msgid, "last msgid is not correct");
  }

  function test_write_Generic()
  {
    $c  =& $this->c;
    $ct =& $this->ct;
    $prefix  = __FUNCTION__;
    $nick    = $prefix . '_' . $this->nick;
    $nickid  = $prefix . '_' . $this->nickid;
    $chan    = $prefix . '_' . $this->chan;
    $cmd    = "send";
    $msg    = "my test message";
    
    // create message on the channel
    $this->ct->createNick($chan, $nick, $nickid);
    $msgid = $this->ct->write($chan, $nick, $cmd, $msg);
    $this->assertEquals(1, $msgid,"generated msg_id is not correct");
    $res = $this->ct->read($chan, 0);
    $this->assertEquals(1, count($res["data"]), "1 messages should be read");
    $this->assertEquals($msg, $res["data"][1]["param"] ,"messages data is not the same as the sent one");
    $this->assertEquals(1, $res["new_from_id"],"new_from_id is not correct");
  }
  
  function test_read_Generic()
  {
    $c  =& $this->c;
    $ct =& $this->ct;
    $prefix  = __FUNCTION__;
    $nick    = $prefix . '_' . $this->nick;
    $nickid  = $prefix . '_' . $this->nickid;
    $chan    = $prefix . '_' . $this->chan;
    $cmd    = "send";
    $msg    = "my test message";
    
    // create on the channel
    $this->ct->createNick($chan, $nick, $nickid);
    for($i = 0; $i < 10; $i++)
    {
      $msgid = $this->ct->write($chan, $nick, $cmd ,$msg . $i);
      $this->assertEquals($msgid, $i+1, "generated msg_id is not correct");
    }

    $res = $this->ct->read($chan, 0);
    $this->assertEquals(10, count($res["data"]), "10 messages should be read");
    $this->assertEquals($msg."0", $res["data"][1]["param"] ,"messages data is not the same as the sent one");
    $this->assertEquals($msg."8", $res["data"][9]["param"] ,"messages data is not the same as the sent one");
    $this->assertEquals($res["new_from_id"], 10 ,"new_from_id is not correct");
    
    $res = $this->ct->read($chan, 5);
    $this->assertEquals(5, count($res["data"]), "5 messages should be read");
    $this->assertEquals($msg."5", $res["data"][6]["param"] ,"messages data is not the same as the sent one");
    $this->assertEquals($msg."9", $res["data"][10]["param"] ,"messages data is not the same as the sent one");
    $this->assertEquals($res["new_from_id"], 10 ,"new_from_id is not correct");
  }

  function test_encodedecode_Generic()
  {
    $c  =& $this->c;
    $ct =& $this->ct;

    $string = "il était une fois C;h:!?§+ toto=}at是";

    $prefix    = __FUNCTION__;
    $group     = $prefix."_nickid-to-channelid";
    $subgroup  = $prefix."_nickid1";
    $leaf      = $prefix."_".$ct->encode($string);
    $leafvalue = $string;
    $ct->setMeta($group, $subgroup, $leaf, $leafvalue);

    $ret = $ct->getMeta($group, $subgroup);
    $this->assertEquals($ret['value'][0], $leaf, "the leaf name is wrong");
    $ret = $ct->getMeta($group, $subgroup, $leaf, true);
    $this->assertEquals($ret['value'][0], $leafvalue, "the leaf value is wrong");
  }
  
  function test_getMeta_Generic_1()
  {
    $c  =& $this->c;
    $ct =& $this->ct;

    $prefix   = __FUNCTION__;
    $group    = $prefix."_nickid-to-channelid";
    $subgroup = $prefix."_nickid1";
    $leaf     = $prefix."_channelid1";
    $ct->setMeta($group, $subgroup, $leaf);
    $time = time();

    $ret = $ct->getMeta($group, $subgroup, $leaf);
    $this->assertEquals(count($ret["timestamp"]), 1, "number of leaf is wrong");
    $this->assertEquals($ret["timestamp"][0], $time, "the leaf timestamp is wrong");
    $this->assertEquals($ret["value"][0], null, "the leaf value is wrong");

    $ret = $ct->getMeta($group, $subgroup);
    $this->assertEquals(count($ret["timestamp"]), 1, "number of leaf is wrong");
    $this->assertEquals($ret["timestamp"][0], $time, "the leaf timestamp is wrong");
    $this->assertEquals($ret["value"][0], $leaf, "the leaf name is wrong");

    $leafvalue = $prefix."_leafvalue";
    $ct->setMeta($group, $subgroup, $leaf, $leafvalue);
    $time = time();

    $ret = $ct->getMeta($group, $subgroup, $leaf, true);
    $this->assertEquals(count($ret["timestamp"]), 1, "number of leaf is wrong");
    $this->assertEquals($ret["timestamp"][0], $time, "the leaf timestamp is wrong");
    $this->assertEquals($ret["value"][0], $leafvalue, "the leaf value is wrong");
  }

  function test_getMeta_Generic_2()
  {
    $c  =& $this->c;
    $ct =& $this->ct;

    $prefix   = __FUNCTION__;
    $group    = $prefix."_nickid-to-channelid";
    $subgroup = $prefix."_nickid1";
    $leaf1    = $prefix."_channelid1";
    $leaf2    = $prefix."_channelid2";
    $ct->setMeta($group, $subgroup, $leaf1);
    $ct->setMeta($group, $subgroup, $leaf2);
    $time = time();

    $ret = $ct->getMeta($group, $subgroup);
    asort($ret["value"]);
    $this->assertEquals(count($ret["timestamp"]), 2, "number of leaf is wrong");
    $this->assertEquals($ret["timestamp"][0], $time, "the leaf timestamp is wrong");
    $this->assertEquals($ret["timestamp"][1], $time, "the leaf timestamp is wrong");
    $this->assertEquals($ret["value"][0], $leaf1, "the leaf name is wrong");
    $this->assertEquals($ret["value"][1], $leaf2, "the leaf name is wrong");
  }

  function test_getMeta_Generic_3()
  {
    $c  =& $this->c;
    $ct =& $this->ct;

    $prefix   = __FUNCTION__;
    $group    = $prefix."_nickid-to-channelid";
    $subgroup1 = $prefix."_nickid1";
    $subgroup2 = $prefix."_nickid2";
    $leaf1    = $prefix."_channelid1";
    $leaf2    = $prefix."_channelid2";
    $ct->setMeta($group, $subgroup1, $leaf1);
    $ct->setMeta($group, $subgroup1, $leaf2);
    $ct->setMeta($group, $subgroup2, $leaf1);
    $ct->setMeta($group, $subgroup2, $leaf2);
    $time = time();

    $ret = $ct->getMeta($group);
    asort($ret["value"]);
    $this->assertEquals(count($ret["timestamp"]), 2, "number of subgroup is wrong");
    $this->assertEquals($ret["timestamp"][0], $time, "the subgroup timestamp is wrong");
    $this->assertEquals($ret["timestamp"][1], $time, "the subgroup timestamp is wrong");
    $this->assertEquals($ret["value"][0], $subgroup1, "the subgroup name is wrong");
    $this->assertEquals($ret["value"][1], $subgroup2, "the subgroup name is wrong");
  }
  
}

?>
