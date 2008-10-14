--TEST--
Text_Diff: PEAR Bug #12740 (failed assertion)
--FILE--
<?php

require dirname(__FILE__) . '/../Diff.php';
require dirname(__FILE__) . '/../Diff/Renderer/inline.php';

$a = <<<QQ
<li>The tax credit amounts to 30% of the cost of the system, with a
maximum of 2,000. This credit is separate from the 500 home improvement
credit.</li>
<h3>Fuel Cells<a
href="12341234213421341234123412341234123421341234213412342134213423"
class="anchor" title="Link to this section"><br />
<li>Your fuel 123456789</li>
QQ;

$b = <<<QQ
<li> of gas emissions by 2050</li>
<li>Raise car fuel economy to 50 mpg by 2017</li>
<li>Increase access to mass transit systems</li>
QQ;

$diff = new Text_Diff(explode("\n", $b), explode("\n", $a));
$renderer = new Text_Diff_Renderer_inline();
$renderer->render($diff);

?>
--EXPECT--
