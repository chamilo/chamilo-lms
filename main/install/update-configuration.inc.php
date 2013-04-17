<?php

/* For licensing terms, see /license.txt */
/**
 *
 * Chamilo LMS
 *
 * Only updates the  main/inc/conf/configuration.php  file with the new version use only from 1.8.8.1 to 1.8.8.2 (with no DB changes)
 * @package chamilo.install
 */

use Symfony\Component\Yaml\Dumper;

if (defined('SYSTEM_INSTALLATION')) {

    $app['monolog']->addInfo("Starting " . basename(__FILE__));

    //Creating app/config/configuration.yml
    global $_configuration;

    $_configuration['system_version'] = $new_version;
    $_configuration['system_stable'] = $new_version_stable ? 'true' : 'false';
    $_configuration['software_name'] = isset($software_name) ? $software_name : 'chamilo';
    $_configuration['software_url'] = isset($software_url) ? $software_url : 'chamilo';
    $_configuration['password_encryption'] = isset($userPasswordCrypted) ? $userPasswordCrypted : $_configuration['password_encryption'];

    $dumper = new Dumper();

    //removing useless indexes before saving in the configuration file

    unset($_configuration['dokeos_version']);
    unset($_configuration['dokeos_stable']);
    unset($_configuration['code_append']);
    unset($_configuration['course_folder']);

    $yaml = $dumper->dump($_configuration, 2); //inline
    $newConfigurationFile = api_get_path(SYS_CONFIG_PATH).'configuration.yml';
    file_put_contents($newConfigurationFile, $yaml);

    //Moving files in app/config

    if (file_exists(api_get_path(CONFIGURATION_PATH).'mail.conf.php')) {
        copy(api_get_path(CONFIGURATION_PATH).'mail.conf.php', api_get_path(SYS_CONFIG_PATH).'mail.conf.php');
        unlink(api_get_path(CONFIGURATION_PATH).'mail.conf.php');
    }

    if (file_exists(api_get_path(CONFIGURATION_PATH).'auth.conf.php')) {
        copy(api_get_path(CONFIGURATION_PATH).'auth.conf.php', api_get_path(SYS_CONFIG_PATH).'auth.conf.php');
        unlink(api_get_path(CONFIGURATION_PATH).'auth.conf.php');
    }

    if (file_exists(api_get_path(CONFIGURATION_PATH).'events.conf.php')) {
        copy(api_get_path(CONFIGURATION_PATH).'events.conf.php', api_get_path(SYS_CONFIG_PATH).'events.conf.php');
        unlink(api_get_path(CONFIGURATION_PATH).'events.conf.php');
    }

    if (file_exists(api_get_path(CONFIGURATION_PATH).'mail.conf.php')) {
        copy(api_get_path(CONFIGURATION_PATH).'mail.conf.php', api_get_path(SYS_CONFIG_PATH).'mail.conf.php');
        unlink(api_get_path(CONFIGURATION_PATH).'mail.conf.php');
    }

    if (file_exists(api_get_path(CONFIGURATION_PATH).'portfolio.conf.php')) {
        copy(api_get_path(CONFIGURATION_PATH).'portfolio.conf.php', api_get_path(SYS_CONFIG_PATH).'portfolio.conf.php');
        unlink(api_get_path(CONFIGURATION_PATH).'portfolio.conf.php');
    }

    if (file_exists(api_get_path(CONFIGURATION_PATH).'profile.conf.php')) {
        copy(api_get_path(CONFIGURATION_PATH).'profile.conf.php', api_get_path(SYS_CONFIG_PATH).'profile.conf.php');
        unlink(api_get_path(CONFIGURATION_PATH).'profile.conf.php');
    }
}