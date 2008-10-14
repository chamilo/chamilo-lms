--TEST--
Text_Diff: Context renderer 2
--FILE--
<?php
include_once 'Text/Diff.php';
include_once 'Text/Diff/Renderer/context.php';

$lines1 = file(dirname(__FILE__) . '/5.txt');
$lines2 = file(dirname(__FILE__) . '/6.txt');

$diff = &new Text_Diff($lines1, $lines2);

$renderer = &new Text_Diff_Renderer_context();
echo $renderer->render($diff);
?>
--EXPECT--
***************
*** 1,5 ****
  This is a test.
  Adding random text to simulate files.
  Various Content.
! More Content.
! Testing diff and renderer.
--- 1,7 ----
  This is a test.
  Adding random text to simulate files.
+ Inserting a line.
  Various Content.
! Replacing content.
! Testing similarities and renderer.
! Append content.
