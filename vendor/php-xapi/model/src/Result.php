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
 * An {@link Actor Actor's} outcome related to the {@link Statement} in which
 * it is included.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class Result
{
    private $score;
    private $success;
    private $completion;
    private $response;
    private $duration;
    private $extensions;

    public function __construct(Score $score = null, bool $success = null, bool $completion = null, string $response = null, string $duration = null, Extensions $extensions = null)
    {
        $this->score = $score;
        $this->success = $success;
        $this->completion = $completion;
        $this->response = $response;
        $this->duration = $duration;
        $this->extensions = $extensions;
    }

    public function withScore(Score $score = null): self
    {
        $result = clone $this;
        $result->score = $score;

        return $result;
    }

    public function withSuccess(bool $success = null): self
    {
        $result = clone $this;
        $result->success = $success;

        return $result;
    }

    public function withCompletion(bool $completion = null): self
    {
        $result = clone $this;
        $result->completion = $completion;

        return $result;
    }

    public function withResponse(string $response = null): self
    {
        $result = clone $this;
        $result->response = $response;

        return $result;
    }

    public function withDuration(string $duration = null): self
    {
        $result = clone $this;
        $result->duration = $duration;

        return $result;
    }

    public function withExtensions(Extensions $extensions = null): self
    {
        $result = clone $this;
        $result->extensions = $extensions;

        return $result;
    }

    /**
     * Returns the user's score.
     */
    public function getScore(): ?Score
    {
        return $this->score;
    }

    /**
     * Returns whether or not the user finished a task successfully.
     */
    public function getSuccess(): ?bool
    {
        return $this->success;
    }

    /**
     * Returns the completion status.
     */
    public function getCompletion(): ?bool
    {
        return $this->completion;
    }

    /**
     * Returns the response.
     */
    public function getResponse(): ?string
    {
        return $this->response;
    }

    /**
     * Returns the period of time over which the Activity was performed.
     */
    public function getDuration(): ?string
    {
        return $this->duration;
    }

    /**
     * Returns the extensions associated with the result.
     */
    public function getExtensions(): ?Extensions
    {
        return $this->extensions;
    }

    /**
     * Checks if another result is equal.
     *
     * Two results are equal if and only if all of their properties are equal.
     */
    public function equals(Result $result): bool
    {
        if (null !== $this->score xor null !== $result->score) {
            return false;
        }

        if (null !== $this->score && !$this->score->equals($result->score)) {
            return false;
        }

        if ($this->success !== $result->success) {
            return false;
        }

        if ($this->completion !== $result->completion) {
            return false;
        }

        if ($this->response !== $result->response) {
            return false;
        }

        if ($this->duration !== $result->duration) {
            return false;
        }

        if (null !== $this->extensions xor null !== $result->extensions) {
            return false;
        }

        if (null !== $this->extensions && null !== $result->extensions && !$this->extensions->equals($result->extensions)) {
            return false;
        }

        return true;
    }
}
