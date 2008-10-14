--TEST--
Text_Diff: PEAR Bug #6428 (problem with single digits after space)
--FILE--
<?php
include_once 'Text/Diff.php';
include_once 'Text/Diff/Renderer/inline.php';

$from = array('Line 1',  'Another line');
$to   = array('Line  1', 'Another line');

$diff = &new Text_Diff ($from, $to);
$renderer = &new Text_Diff_Renderer_inline();

echo $renderer->render($diff);
?>
--EXPECT--
Line <del>1</del><ins> 1</ins>
Another line
