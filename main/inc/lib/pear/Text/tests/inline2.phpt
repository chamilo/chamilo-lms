--TEST--
Text_Diff: Inline renderer 2
--FILE--
<?php
include_once 'Text/Diff.php';
include_once 'Text/Diff/Renderer/inline.php';

$lines1 = array(
    "This is a test.\n",
    "Adding random text to simulate files.\n",
    "Various Content.\n",
    "More Content.\n",
    "Testing diff and renderer.\n"
);
$lines2 = array(
    "This is a test.\n",
    "Adding random text to simulate files.\n",
    "Inserting a line.\n",
    "Various Content.\n",
    "Replacing content.\n",
    "Testing similarities and renderer.\n",
    "Append content.\n"
);

$diff = &new Text_Diff($lines1, $lines2);

$renderer = &new Text_Diff_Renderer_inline();
echo $renderer->render($diff);
?>
--EXPECT--
This is a test.
Adding random text to simulate files.
<ins>Inserting a line.</ins>
Various Content.
<del>More Content.</del><ins>Replacing content.</ins>
Testing <del>diff</del><ins>similarities</ins> and renderer.<ins>
Append content.</ins>
