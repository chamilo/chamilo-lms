<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model;

/**
 * The outcome of an {@link Activity} achieved by an {@link Agent}.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class Score
{
    private $scaled;
    private $raw;
    private $min;
    private $max;

    /**
     * @param float|int|null $scaled
     * @param float|int|null $raw
     * @param float|int|null $min
     * @param float|int|null $max
     */
    public function __construct($scaled = null, $raw = null, $min = null, $max = null)
    {
        $this->scaled = $scaled;
        $this->raw = $raw;
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * @param float|int|null $scaled
     */
    public function withScaled($scaled): self
    {
        $score = clone $this;
        $score->scaled = $scaled;

        return $score;
    }

    /**
     * @param float|int|null $raw
     */
    public function withRaw($raw): self
    {
        $score = clone $this;
        $score->raw = $raw;

        return $score;
    }

    /**
     * @param float|int|null $min
     */
    public function withMin($min): self
    {
        $score = clone $this;
        $score->min = $min;

        return $score;
    }

    /**
     * @param float|int|null $max
     */
    public function withMax($max): self
    {
        $score = clone $this;
        $score->max = $max;

        return $score;
    }

    /**
     * Returns the Agent's scaled score (a number between -1 and 1).
     *
     * @return float|int|null The scaled score
     */
    public function getScaled()
    {
        return $this->scaled;
    }

    /**
     * Returns the Agent's score (a number between min and max).
     *
     * @return float|int|null The score
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * Returns the lowest possible score.
     *
     * @return float|int|null The lowest possible score
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Returns the highest possible score.
     *
     * @return float|int|null The highest possible score
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Checks if another score is equal.
     *
     * Two scores are equal if and only if all of their properties are equal.
     */
    public function equals(Score $score): bool
    {
        if (null !== $this->scaled xor null !== $score->scaled) {
            return false;
        }

        if ((float) $this->scaled !== (float) $score->scaled) {
            return false;
        }

        if (null !== $this->raw xor null !== $score->raw) {
            return false;
        }

        if ((float) $this->raw !== (float) $score->raw) {
            return false;
        }

        if (null !== $this->min xor null !== $score->min) {
            return false;
        }

        if ((float) $this->min !== (float) $score->min) {
            return false;
        }

        if (null !== $this->max xor null !== $score->max) {
            return false;
        }

        if ((float) $this->max !== (float) $score->max) {
            return false;
        }

        return true;
    }
}
