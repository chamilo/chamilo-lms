--TEST--
Text_Diff: Basic diff operation
--FILE--
<?php
include_once 'Text/Diff.php';

$lines1 = file(dirname(__FILE__) . '/1.txt');
$lines2 = file(dirname(__FILE__) . '/2.txt');

$diff = &new Text_Diff($lines1, $lines2);
echo strtolower(print_r($diff, true));
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
