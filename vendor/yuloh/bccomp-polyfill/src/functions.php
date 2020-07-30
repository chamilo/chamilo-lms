<?php

namespace Yuloh\BcCompPolyfill{
    if (!function_exists('Yuloh\BcCompPolyfill\bccomp')) {
        function bccomp($leftOperand, $rightOperand, $scale = 0)
        {
            $leftOperand  = new BigNumber((string) $leftOperand);
            $rightOperand = new BigNumber((string) $rightOperand);

            // The real bccomp casts floats to int but throws a warning for anything else.

            if (is_float($scale)) {
                $scale = (int) $scale;
            }

            if (!is_int($scale)) {
                trigger_error(
                    sprintf('bccomp() expects parameter 3 to be integer, %s given', gettype($scale)),
                    E_USER_WARNING
                );
            }

            // If both numbers are zero, they are equal.

            if ($leftOperand->isZero() && $rightOperand->isZero()) {
                return 0;
            }

             // If one number is positive while the other is negative we can return early.

            if ($leftOperand->isPositive() && $rightOperand->isNegative()) {
                return 1;
            }

            if ($leftOperand->isNegative() && $rightOperand->isPositive()) {
                return -1;
            }

            $isPositiveComparison = $leftOperand->isPositive() && $rightOperand->isPositive();

            // If the part to the left of the decimal is longer it's the larger number.
            if ($leftOperand->getCharacteristicLength() > $rightOperand->getCharacteristicLength()) {
                return $isPositiveComparison ? 1 : -1;
            }

            if ($leftOperand->getCharacteristicLength() < $rightOperand->getCharacteristicLength()) {
                return $isPositiveComparison ? -1 : 1;
            }

            // if the part to the left of the decimal is equal, we check each place for a larger number.
            for ($i = 0; $i < $leftOperand->getCharacteristicLength(); $i++) {
                if ($leftOperand->getCharacteristic()[$i] > $rightOperand->getCharacteristic()[$i]) {
                    return $isPositiveComparison ? 1 : -1;
                }

                if ($leftOperand->getCharacteristic()[$i] < $rightOperand->getCharacteristic()[$i]) {
                    return $isPositiveComparison ? -1 : 1;
                }
            }

            // if there is a scale and we still haven't found the larger number,
            // check each place to the right of the decimal place.
            $leftMantissa  = $leftOperand->getMantissa();
            $rightMantissa = $rightOperand->getMantissa();
            for ($i = 0; $i < $scale; $i++) {

                // If we are still iterating and out of digits to compare,
                // we can return early.
                if (!isset($leftMantissa[$i]) && !isset($rightMantissa[$i])) {
                    return 0;
                }

                // If there isn't a digit in this decimal place, set it to 0.
                if (!isset($leftMantissa[$i])) {
                    $leftMantissa = $leftMantissa . '0';
                }
                if (!isset($rightMantissa[$i])) {
                    $rightMantissa = $rightMantissa . '0';
                }

                if ($leftMantissa[$i] > $rightMantissa[$i]) {
                    return $isPositiveComparison ? 1 : -1;
                }

                if ($leftMantissa[$i] < $rightMantissa[$i]) {
                    return $isPositiveComparison ? -1 : 1;
                }
            }

            return 0;
        }
    }
}

namespace {
    if (!function_exists('bccomp')) {
        function bccomp($leftOperand, $rightOperand, $scale = 0)
        {
            return \Yuloh\BcCompPolyfill\bccomp($leftOperand, $rightOperand, $scale);
        }
    }
}
