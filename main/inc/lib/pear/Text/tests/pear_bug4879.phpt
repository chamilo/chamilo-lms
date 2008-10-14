--TEST--
Text_Diff: PEAR Bug #4879 (inline renderer hangs on numbers in input string)
--FILE--
<?php
include_once 'Text/Diff.php';
include_once 'Text/Diff/Renderer/inline.php';

$oldtext = <<<EOT
Common text
Bob had 1 apple, Alice had 2.
Bon appetit!
EOT;

$newtext = <<< EOT
Common text
Bob had 10 apples, Alice had 1.
Bon appetit!
EOT;

$oldpieces = explode ("\n", $oldtext);
$newpieces = explode ("\n", $newtext);
$diff = &new Text_Diff ($oldpieces, $newpieces);

$renderer = &new Text_Diff_Renderer_inline();
echo $renderer->render($diff);
?>
--EXPECT--
Common text
Bob had <del>1 apple,</del><ins>10 apples,</ins> Alice had <del>2.</del><ins>1.</ins>
Bon appetit!
