<?php

namespace Chamilo\ThemeBundle\Model;

/**
 * Interface NotificationInterface.
 *
 * @package Chamilo\ThemeBundle\Model
 */
interface NotificationInterface
{
    public function getMessage();

    public function getType();

    public function getIcon();

    public function getIdentifier();
}
