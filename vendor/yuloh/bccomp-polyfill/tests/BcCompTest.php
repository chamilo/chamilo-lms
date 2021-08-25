<?php

namespace Yuloh\BcCompPolyfill;

class BcCompTest extends \PHPUnit_Framework_TestCase
{
    public function bcCompData()
    {
        return [
            ['1', '2', 0, -1, 'lt'],
            ['2', '1', 0, 1, 'gt'],
            ['2', '2', 0, 0, 'eq'],
            ['123', '1', 0, 1, 'gt when left operand is longer'],
            ['1', '123', 0, -1, 'lt when left operand is shorter'],
            ['100.12', '100.13', 0, 0, 'eq when scale is 0 but mantissa is less than'],
            ['100.12', '100.13', 1, 0, 'eq when eq up to last decimal place checked'],
            ['100.12', '100.13', 2, -1, 'lt with two decimal places checked and last place is lt'],
            ['100.12', '100.13', 5, -1, 'lt when scale is larger than both operands'],
            ['1', '-1', 0, 1, 'gt when right operand is negative'],
            ['-1', '1', 0, -1, 'lt when left operand is negative'],
            ['-1', '-1', 0, 0, 'eq when both operands are negative'],
            ['-1', '-2', 0, 1, 'gt when both operands are negative'],
            ['-2', '-1', 0, -1, 'lt when both operands are negative'],
            ['-20', '-1', 0, -1, 'lt when both operands are negative and left operand is longer'],
            ['-2', '-10', 0, 1, 'gt when both operands are negative and left operand is shorter'],
            ['-200.12', '-200.13', 2, 1, 'gt when both operands are negative with mantissa'],
            ['-' . PHP_INT_MAX . '99', '-' . PHP_INT_MAX . '99', 0, 0, 'eq when negative and larger than PHP_INT_MAX'],
            ['-' . PHP_INT_MAX . '99', '-' . PHP_INT_MAX . '98', 0, -1, 'lt when negative and larger than PHP_INT_MAX'],
            [PHP_INT_MAX . '99', PHP_INT_MAX . '99', 0, 0, 'eq when larger than PHP_INT_MAX'],
            [PHP_INT_MAX . '99', PHP_INT_MAX . '97', 0, 1, 'gt when larger than PHP_INT_MAX'],
            [PHP_INT_MAX . '99.999', PHP_INT_MAX . '99.997', 5, 1, 'lt when larger than PHP_INT_MAX with mantissa'],
            ['riffraff', '1', 0, -1, 'comparison when left operand is nonsense'],
            ['1', 'kodos', 0, 1, 'comparison when right operand is nonsense'],
            ['riffraff', 'kodos', 0, 0, 'comparison when both operands are nonsense'],
            ['1.2.3', '1', 5, -1, 'comparison when left operand has too many decimal separators'],
            ['1', '1.2.3', 5, 1, 'comparison when right operand has too many decimal separators'],
            ['1.12', '1.15', 1.6, 0, 'float scale should be cast to int, not rounded'],
            ['1a', '1b', 0, 0, 'comparison when both operands have a letter in a number'],
            ['0', '-0', 0, 0, 'comparison of 0 and -0'],
        ];
    }

    /**
     * @dataProvider bcCompData
     */
    public function testBcComp($leftOperand, $rightOperand, $scale, $result, $msg)
    {
        $realLibraryResult = \bccomp($leftOperand, $rightOperand, $scale);
        $this->assertSame($result, $realLibraryResult, 'The actual function result should match the expected result');

        $actual = \Yuloh\BcCompPolyfill\bccomp($leftOperand, $rightOperand, $scale);

        $this->assertSame($result, $actual, $msg);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testInvalidScale()
    {
        \Yuloh\BcCompPolyfill\bccomp(1, 1, []);
    }

    public function fuzzTestData()
    {
        $data = [];

        for ($i = 0; $i < 1000; $i++) {

            $leftOperand = $this->randOperand();
            $rightOperand = $this->randOperand();
            $scale = rand(0, 200);
            $result = \bccomp($leftOperand, $rightOperand, $scale);

            $data[] = [$leftOperand, $rightOperand, $scale, $result];
        }

        return $data;
    }

    /**
     * @dataProvider fuzzTestData
     */
    public function testWithRandomData($leftOperand, $rightOperand, $scale, $result)
    {
        $actual = \Yuloh\BcCompPolyfill\bccomp($leftOperand, $rightOperand, $scale);

        $this->assertSame($result, $actual);
    }

    public function randOperand()
    {
        $negative = rand() % 2 === 0 ? '-' : '';
        $characteristic = rand();
        $mantissa = rand() % 2 === 0 ? '.' . rand() : '';

        return $negative . $characteristic . $mantissa;
    }
}
