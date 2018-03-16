<?php

namespace Chamilo\ThemeBundle\Model;

/**
 * Interface TaskInterface.
 *
 * @package Chamilo\ThemeBundle\Model
 */
interface TaskInterface
{
    public function getColor();

    public function getProgress();

    public function getTitle();

    public function getIdentifier();
}
