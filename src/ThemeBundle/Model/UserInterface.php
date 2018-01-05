<?php
/**
 * UserInterface.php
 * avanzu-admin
 * Date: 23.02.14
 */

namespace Chamilo\ThemeBundle\Model;


interface UserInterface
{
    public function getAvatar();
    public function getUsername();
    public function getMemberSince();
    public function isOnline();
    public function getIdentifier();
}
