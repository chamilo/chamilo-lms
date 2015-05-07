<?php

namespace Chamilo\CoreBundle\Migrations;

use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Entity\SettingsOptions;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;

/**
 * Class AbstractMigrationChamilo
 * @package Chamilo\CoreBundle\Migrations
 */
abstract class AbstractMigrationChamilo extends AbstractMigration
{
    private $manager;

    /**
     * @param EntityManager $manager
     */
    public function setEntityManager(EntityManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->manager;
    }

    /**
     * Speeds up SettingsCurrent creation
     * @param   string  $variable   The variable itself
     * @param   string  $subKey The subkey
     * @param   string  $type   The type of setting (text, radio, select, etc)
     * @param   string  $category   The category (Platform, User, etc)
     * @param   string  $selectedValue  The default value
     * @param   string  $title  The setting title string name
     * @param   string  $comment    The setting comment string name
     * @param   string  $scope  The scope
     * @param   string  $subKeyText Text if there is a subKey
     * @param   int  $accessUrl  What URL it is for
     * @param   bool    $accessUrlChangeable Whether it can be changed on each url
     * @param   bool    $accessUrlLocked Whether the setting for the current URL is locked to the current value
     * @param   array   $options    Optional array in case of a radio-type field, to insert options
     */
    public function addSettingCurrent(
        $variable,
        $subKey = '',
        $type,
        $category,
        $selectedValue,
        $title,
        $comment,
        $scope = null,
        $subKeyText = '',
        $accessUrl = 1,
        $accessUrlChangeable = false,
        $accessUrlLocked = true,
        $options = array()
    ) {
        $setting = new SettingsCurrent();
        $setting->setVariable($variable);
        $setting->setSubkey($subKey);
        $setting->setType($type);
        $setting->setCategory($category);
        $setting->setSelectedValue($selectedValue);
        $setting->setTitle($title);
        $setting->setComment($comment);
        $setting->setScope($scope);
        $setting->setSubkeytext($subKeyText);
        $setting->setAccessUrl($accessUrl);
        $setting->setAccessUrlChangeable($accessUrlChangeable);
        $setting->setAccessUrlLocked($accessUrlLocked);
        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();
        if (count($options) > 0) {
            foreach ($options as $option) {
                $option = new SettingsOptions();
                $option->setVariable($variable);
                $option->setValue($option['value']);
                if (empty($option['text'])) {
                    if ($option['value'] == 'true') {
                        $option['text'] = 'Yes';
                    } else {
                        $option['text'] = 'No';
                    }
                }
                $option->setDisplayText($option['text']);
                $this->getEntityManager()->persis($option);
                $this->getEntityManager()->flush();
            }
        }
    }
}
