--TEST--
Text_Diff: Inline renderer
--FILE--
<?php
include_once 'Text/Diff.php';
include_once 'Text/Diff/Renderer/inline.php';

$lines1 = file(dirname(__FILE__) . '/1.txt');
$lines2 = file(dirname(__FILE__) . '/2.txt');

$diff = &new Text_Diff($lines1, $lines2);

$renderer = &new Text_Diff_Renderer_inline();
echo $renderer->render($diff);
?>
--EXPECT--
This line is the same.
This line is different in <del>1.txt</del><ins>2.txt</ins>
This line is the same.
