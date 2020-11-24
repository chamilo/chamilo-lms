<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chamilo\PluginBundle\Entity\XApi\Lrs;

use Doctrine\ORM\Mapping as ORM;
use Xabbuh\XApi\Model\Result as ResultModel;
use Xabbuh\XApi\Model\Score;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * @ORM\Table(name="xapi_result")
 * @ORM\Entity()
 */
class Result
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    public $identifier;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    public $hasScore;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    public $scaled;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    public $raw;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    public $min;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    public $max;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $success;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $completion;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $response;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $duration;

    /**
     * @var Extensions|null
     *
     * @ORM\OneToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\Lrs\Extensions", cascade={"ALL"})
     * @ORM\JoinColumn(referencedColumnName="identifier")
     */
    public $extensions;

    /**
     * @param \Xabbuh\XApi\Model\Result $model
     *
     * @return \Chamilo\PluginBundle\Entity\XApi\Lrs\Result
     */
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

    /**
     * @return \Xabbuh\XApi\Model\Result
     */
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
