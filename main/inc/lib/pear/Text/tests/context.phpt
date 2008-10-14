--TEST--
Text_Diff: Context renderer
--FILE--
<?php
include_once 'Text/Diff.php';
include_once 'Text/Diff/Renderer/context.php';

$lines1 = file(dirname(__FILE__) . '/1.txt');
$lines2 = file(dirname(__FILE__) . '/2.txt');

$diff = &new Text_Diff($lines1, $lines2);

$renderer = &new Text_Diff_Renderer_context();
echo $renderer->render($diff);
?>
--EXPECT--
***************
*** 1,3 ****
  This line is the same.
! This line is different in 1.txt
  This line is the same.
--- 1,3 ----
  This line is the same.
! This line is different in 2.txt
  This line is the same.
