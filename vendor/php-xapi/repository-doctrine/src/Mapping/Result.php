<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\Doctrine\Mapping;

use Xabbuh\XApi\Model\Result as ResultModel;
use Xabbuh\XApi\Model\Score;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class Result
{
    public $identifier;

    /**
     * @var bool
     */
    public $hasScore;

    /**
     * @var float|null
     */
    public $scaled;

    /**
     * @var float|null
     */
    public $raw;

    /**
     * @var float|null
     */
    public $min;

    /**
     * @var float|null
     */
    public $max;

    /**
     * @var bool|null
     */
    public $success;

    /**
     * @var bool|null
     */
    public $completion;

    /**
     * @var string|null
     */
    public $response;

    /**
     * @var string|null
     */
    public $duration;

    /**
     * @var Extensions|null
     */
    public $extensions;

    public static function fromModel(ResultModel $model)
    {
        $result = new self();
        $result->success = $model->getSuccess();
        $result->completion = $model->getCompletion();
        $result->response = $model->getResponse();
        $result->duration = $model->getDuration();

        if (null !== $score = $model->getScore()) {
            $result->hasScore = true;
            $result->scaled = $score->getScaled();
            $result->raw = $score->getRaw();
            $result->min = $score->getMin();
            $result->max = $score->getMax();
        } else {
            $result->hasScore = false;
        }

        if (null !== $extensions = $model->getExtensions()) {
            $result->extensions = Extensions::fromModel($extensions);
        }

        return $result;
    }

    public function getModel()
    {
        $score = null;
        $extensions = null;

        if ($this->hasScore) {
            $score = new Score($this->scaled, $this->raw, $this->min, $this->max);
        }

        if (null !== $this->extensions) {
            $extensions = $this->extensions->getModel();
        }

        return new ResultModel($score, $this->success, $this->completion, $this->response, $this->duration, $extensions);
    }
}
