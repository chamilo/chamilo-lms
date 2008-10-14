--TEST--
Text_Diff: Text_Diff_Engine_string test.
--FILE--
<?php

require_once 'PEAR.php';
require_once 'Text/Diff.php';

$unified = file_get_contents(dirname(__FILE__) . '/unified.patch');
$diff_u = new Text_Diff('string', array($unified));
echo strtolower(print_r($diff_u, true));

$unified2 = file_get_contents(dirname(__FILE__) . '/unified2.patch');
$diff_u2 = new Text_Diff('string', array($unified2));
var_export(is_a($diff_u2->getDiff(), 'PEAR_Error'));
echo "\n";
$diff_u2 = new Text_Diff('string', array($unified2, 'unified'));
echo strtolower(print_r($diff_u2, true));

$context = file_get_contents(dirname(__FILE__) . '/context.patch');
$diff_c = new Text_Diff('string', array($context));
echo strtolower(print_r($diff_c, true));

?>
--EXPECT--
text_diff object
(
    [_edits] => array
        (
            [0] => text_diff_op_copy object
                (
                    [orig] => array
                        (
                            [0] => this line is the same.
                        )

                    [final] => array
                        (
                            [0] => this line is the same.
                        )

                )

            [1] => text_diff_op_change object
                (
                    [orig] => array
                        (
                            [0] => this line is different in 1.txt
                        )

                    [final] => array
                        (
                            [0] => this line is different in 2.txt
                        )

                )

            [2] => text_diff_op_copy object
                (
                    [orig] => array
                        (
                            [0] => this line is the same.
                        )

                    [final] => array
                        (
                            [0] => this line is the same.
                        )

                )

        )

)
true
text_diff object
(
    [_edits] => array
        (
            [0] => text_diff_op_change object
                (
                    [orig] => array
                        (
                            [0] => for the first time in u.s. history number of private contractors and troops are equal
                        )

                    [final] => array
                        (
                            [0] => number of private contractors and troops are equal for first time in u.s. history
                        )

                )

        )

)
text_diff object
(
    [_edits] => array
        (
            [0] => text_diff_op_copy object
                (
                    [orig] => array
                        (
                            [0] => this line is the same.
                        )

                    [final] => array
                        (
                            [0] => this line is the same.
                        )

                )

            [1] => text_diff_op_change object
                (
                    [orig] => array
                        (
                            [0] => this line is different in 1.txt
                        )

                    [final] => array
                        (
                            [0] => this line is different in 2.txt
                        )

                )

            [2] => text_diff_op_copy object
                (
                    [orig] => array
                        (
                            [0] => this line is the same.
                        )

                    [final] => array
                        (
                            [0] => this line is the same.
                        )

                )

        )

)
