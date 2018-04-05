<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Model;

/**
 * Interface UserInterface.
 *
 * @package Chamilo\ThemeBundle\Model
 */
interface UserInterface
{
    public function getAvatar();

    public function getUsername();

    public function getMemberSince();

    public function isOnline();

    public function getIdentifier();
}
