--TEST--
Text_Diff: Unified renderer 2
--FILE--
<?php
include_once 'Text/Diff.php';
include_once 'Text/Diff/Renderer/unified.php';

$lines1 = file(dirname(__FILE__) . '/5.txt');
$lines2 = file(dirname(__FILE__) . '/6.txt');

$diff = &new Text_Diff($lines1, $lines2);

$renderer = &new Text_Diff_Renderer_unified();
echo $renderer->render($diff);
?>
--EXPECT--
@@ -1,5 +1,7 @@
 This is a test.
 Adding random text to simulate files.
+Inserting a line.
 Various Content.
-More Content.
-Testing diff and renderer.
+Replacing content.
+Testing similarities and renderer.
+Append content.
