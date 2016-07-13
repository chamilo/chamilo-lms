<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations;

use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Entity\SettingsOptions;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;

/**
 * Class AbstractMigrationChamilo
 *
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
        if (empty($this->manager)) {
            $dbParams = array(
                'driver' => 'pdo_mysql',
                'host' => $this->connection->getHost(),
                'user' => $this->connection->getUsername(),
                'password' => $this->connection->getPassword(),
                'dbname' => $this->connection->getDatabase(),
                'port' => $this->connection->getPort()
            );
            $database = new \Database();
            $database->connect(
                $dbParams,
                __DIR__.'/../../',
                __DIR__.'/../../'
            );
            $this->manager = $database->getManager();
        }

        return $this->manager;
    }

    /**
     * Speeds up SettingsCurrent creation
     * @param string $variable The variable itself
     * @param string $subKey The subkey
     * @param string $type The type of setting (text, radio, select, etc)
     * @param string $category The category (Platform, User, etc)
     * @param string $selectedValue The default value
     * @param string $title The setting title string name
     * @param string $comment The setting comment string name
     * @param string $scope The scope
     * @param string $subKeyText Text if there is a subKey
     * @param int $accessUrl What URL it is for
     * @param bool $accessUrlChangeable Whether it can be changed on each url
     * @param bool $accessUrlLocked Whether the setting for the current URL is
     * locked to the current value
     * @param array $options Optional array in case of a radio-type field,
     * to insert options
     */
    public function addSettingCurrent(
        $variable,
        $subKey,
        $type,
        $category,
        $selectedValue,
        $title,
        $comment,
        $scope = '',
        $subKeyText = '',
        $accessUrl = 1,
        $accessUrlChangeable = false,
        $accessUrlLocked = true,
        $options = array()
    ) {
        $setting = new SettingsCurrent();
        $setting
            ->setVariable($variable)
            ->setSubkey($subKey)
            ->setType($type)
            ->setCategory($category)
            ->setSelectedValue($selectedValue)
            ->setTitle($title)
            ->setComment($comment)
            ->setScope($scope)
            ->setSubkeytext($subKeyText)
            ->setAccessUrl($accessUrl)
            ->setAccessUrlChangeable($accessUrlChangeable)
            ->setAccessUrlLocked($accessUrlLocked);

        $this->getEntityManager()->persist($setting);

        if (count($options) > 0) {
            foreach ($options as $option) {
                if (empty($option['text'])) {
                    if ($option['value'] == 'true') {
                        $option['text'] = 'Yes';
                    } else {
                        $option['text'] = 'No';
                    }
                }

                $settingOption = new SettingsOptions();
                $settingOption
                    ->setVariable($variable)
                    ->setValue($option['value'])
                    ->setDisplayText($option['text']);

                $this->getEntityManager()->persist($settingOption);
            }
        }
        $this->getEntityManager()->flush();
    }

    /**
     * @param string $variable
     * @return mixed
     */
    public function getConfigurationValue($variable)
    {
        global $_configuration;
        if (isset($_configuration[$variable])) {
            return $_configuration[$variable];
        }
        return false;
    }
    /**
     * Remove a setting completely
     * @param string $variable The setting variable name
     * @return void
     */
    public function removeSettingCurrent($variable)
    {
        //to be implemented
    }

}
