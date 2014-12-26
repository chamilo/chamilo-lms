<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Data\ORM;

use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Entity\SettingsOptions;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * @deprecated we use the Chamilo\SettingsBundle\Manager\SettingsManager class
 * Class LoadSettingsData
 * @package Chamilo\CoreBundle\DataFixtures\ORM
 */
class LoadSettingsData extends AbstractFixture implements
    ContainerAwareInterface,
    OrderedFixtureInterface,
    VersionedFixtureInterface
{
    private $container;

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 7;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param $manager
     * @param $entity
     * @param $variableName
     * @param $variables
     */
    private function generateCode($manager, $entity, $variableName, $variables)
    {
        $data = $manager->getRepository('ChamiloCoreBundle:'.$entity)->findAll();

        if (!empty($data)) {
            $code = null;
            /** @var SettingsCurrent $setting */
            foreach ($data as $setting) {
                $code .= "\n\$".$variableName." = new $entity();\n";
                foreach ($variables as $variable) {
                    $functionGet = "get$variable";
                    $functionSet = "set$variable";
                    $code .= "\$".$variableName."->" . $functionSet . "('" . addslashes(
                            $setting->$functionGet()
                        ) . "'); \n";

                }
                $code .= "\$manager->persist(\$".$variableName."); \n\n";
            }

            echo $code;
        }
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        //$this->generateSettingsCode($manager);
        $this->createSettings($manager);
        //$this->createOptions($manager);
        return;

    }

    /**
     * @param ObjectManager $manager
     */
    public function createSettings(ObjectManager $manager)
    {
        $accessUrl = $this->getReference('access_url');

        $setting = new SettingsCurrent();
        $setting->setVariable('institution');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('Chamilo');
        $setting->setTitle('InstitutionTitle');
        $setting->setComment('InstitutionComment');
        $setting->setScope('platform');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $setting->setUrl($accessUrl);

        $manager->persist($setting);

        $setting = new SettingsCurrent();
        $setting->setVariable('institution_url');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('platform');
        $setting->setSelectedValue('http://www.chamilo.org');
        $setting->setTitle('InstitutionUrlTitle');
        $setting->setComment('InstitutionUrlComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);

        $manager->persist($setting);

        $setting = new SettingsCurrent();
        $setting->setVariable('site_name');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('platform');
        $setting->setSelectedValue('Campus Chamilo');
        $setting->setTitle('SiteNameTitle');
        $setting->setComment('SiteNameComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);

        $manager->persist($setting);

        $setting = new SettingsCurrent();
        $setting->setVariable('administrator_email');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('admin');
        $setting->setSelectedValue('admin@example.org');
        $setting->setTitle('emailAdministratorTitle');
        $setting->setComment('emailAdministratorComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);

        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('administrator_surname');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('admin');
        $setting->setSelectedValue('Doe');
        $setting->setTitle('administratorSurnameTitle');
        $setting->setComment('administratorSurnameComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);

        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('administrator_name');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('admin');
        $setting->setSelectedValue('Jane');
        $setting->setTitle('administratorNameTitle');
        $setting->setComment('administratorNameComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);

        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('administrator_phone');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('admin');
        $setting->setSelectedValue('(000) 001 02 03');
        $setting->setTitle('administratorTelephoneTitle');
        $setting->setComment('administratorTelephoneComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);

        $manager->persist($setting);

        return;

        $setting = new SettingsCurrent();
        $setting->setVariable('noreply_email_address');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('');
        $setting->setTitle('NoReplyEmailAddress');
        $setting->setComment('NoReplyEmailAddressComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_administrator_data');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Admin');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowAdministratorDataTitle');
        $setting->setComment('ShowAdministratorDataComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);

        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_tutor_data');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Session');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowTutorDataTitle');
        $setting->setComment('ShowTutorDataComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);

        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_teacher_data');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowTeacherDataTitle');
        $setting->setComment('ShowTeacherDataComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');

        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('homepage_view');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('activity_big');
        $setting->setTitle('HomepageViewTitle');
        $setting->setComment('HomepageViewComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);

        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_toolshortcuts');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShowToolShortcutsTitle');
        $setting->setComment('ShowToolShortcutsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_group_categories');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowGroupCategories');
        $setting->setComment('AllowGroupCategoriesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('platformLanguage');
        $setting->setSubkey('');
        $setting->setType('link');
        $setting->setCategory('Languages');
        $setting->setSelectedValue('{PLATFORMLANGUAGE}');
        $setting->setTitle('PlatformLanguageTitle');
        $setting->setComment('PlatformLanguageComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('showonline');
        $setting->setSubkey('world');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowOnlineTitle');
        $setting->setComment('ShowOnlineComment');
        $setting->setScope('');
        $setting->setSubkeytext('ShowOnlineWorld');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('showonline');
        $setting->setSubkey('users');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowOnlineTitle');
        $setting->setComment('ShowOnlineComment');
        $setting->setScope('');
        $setting->setSubkeytext('ShowOnlineUsers');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('showonline');
        $setting->setSubkey('course');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowOnlineTitle');
        $setting->setComment('ShowOnlineComment');
        $setting->setScope('');
        $setting->setSubkeytext('ShowOnlineCourse');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('showonline');
        $setting->setSubkey('session');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowOnlineTitle');
        $setting->setComment('ShowOnlineComment');
        $setting->setScope('');
        $setting->setSubkeytext('ShowOnlineSession');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('profile');
        $setting->setSubkey('name');
        $setting->setType('checkbox');
        $setting->setCategory('User');
        $setting->setSelectedValue('false');
        $setting->setTitle('ProfileChangesTitle');
        $setting->setComment('ProfileChangesComment');
        $setting->setScope('');
        $setting->setSubkeytext('name');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('profile');
        $setting->setSubkey('officialcode');
        $setting->setType('checkbox');
        $setting->setCategory('User');
        $setting->setSelectedValue('false');
        $setting->setTitle('ProfileChangesTitle');
        $setting->setComment('ProfileChangesComment');
        $setting->setScope('');
        $setting->setSubkeytext('officialcode');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('profile');
        $setting->setSubkey('email');
        $setting->setType('checkbox');
        $setting->setCategory('User');
        $setting->setSelectedValue('false');
        $setting->setTitle('ProfileChangesTitle');
        $setting->setComment('ProfileChangesComment');
        $setting->setScope('');
        $setting->setSubkeytext('Email');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('profile');
        $setting->setSubkey('picture');
        $setting->setType('checkbox');
        $setting->setCategory('User');
        $setting->setSelectedValue('true');
        $setting->setTitle('ProfileChangesTitle');
        $setting->setComment('ProfileChangesComment');
        $setting->setScope('');
        $setting->setSubkeytext('UserPicture');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('profile');
        $setting->setSubkey('login');
        $setting->setType('checkbox');
        $setting->setCategory('User');
        $setting->setSelectedValue('false');
        $setting->setTitle('ProfileChangesTitle');
        $setting->setComment('ProfileChangesComment');
        $setting->setScope('');
        $setting->setSubkeytext('Login');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('profile');
        $setting->setSubkey('password');
        $setting->setType('checkbox');
        $setting->setCategory('User');
        $setting->setSelectedValue('true');
        $setting->setTitle('ProfileChangesTitle');
        $setting->setComment('ProfileChangesComment');
        $setting->setScope('');
        $setting->setSubkeytext('UserPassword');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('profile');
        $setting->setSubkey('language');
        $setting->setType('checkbox');
        $setting->setCategory('User');
        $setting->setSelectedValue('true');
        $setting->setTitle('ProfileChangesTitle');
        $setting->setComment('ProfileChangesComment');
        $setting->setScope('');
        $setting->setSubkeytext('Language');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('default_document_quotum');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Course');
        $setting->setSelectedValue('100000000');
        $setting->setTitle('DefaultDocumentQuotumTitle');
        $setting->setComment('DefaultDocumentQuotumComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('registration');
        $setting->setSubkey('officialcode');
        $setting->setType('checkbox');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('RegistrationRequiredFormsTitle');
        $setting->setComment('RegistrationRequiredFormsComment');
        $setting->setScope('');
        $setting->setSubkeytext('OfficialCode');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('registration');
        $setting->setSubkey('email');
        $setting->setType('checkbox');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('true');
        $setting->setTitle('RegistrationRequiredFormsTitle');
        $setting->setComment('RegistrationRequiredFormsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Email');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('registration');
        $setting->setSubkey('language');
        $setting->setType('checkbox');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('true');
        $setting->setTitle('RegistrationRequiredFormsTitle');
        $setting->setComment('RegistrationRequiredFormsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Language');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('default_group_quotum');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Course');
        $setting->setSelectedValue('5000000');
        $setting->setTitle('DefaultGroupQuotumTitle');
        $setting->setComment('DefaultGroupQuotumComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_registration');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('{ALLOWSELFREGISTRATION}');
        $setting->setTitle('AllowRegistrationTitle');
        $setting->setComment('AllowRegistrationComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_registration_as_teacher');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('{ALLOWTEACHERSELFREGISTRATION}');
        $setting->setTitle('AllowRegistrationAsTeacherTitle');
        $setting->setComment('AllowRegistrationAsTeacherComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_lostpassword');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowLostPasswordTitle');
        $setting->setComment('AllowLostPasswordComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_user_headings');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowUserHeadings');
        $setting->setComment('AllowUserHeadingsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('course_description');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('CourseDescription');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('agenda');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Agenda');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('documents');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Documents');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('learning_path');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('LearningPath');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('links');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Links');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('announcements');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Announcements');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('forums');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Forums');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('dropbox');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Dropbox');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('quiz');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Quiz');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('users');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Users');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('groups');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Groups');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('chat');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Chat');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('online_conference');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('OnlineConference');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('student_publications');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('StudentPublications');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_personal_agenda');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('User');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowPersonalAgendaTitle');
        $setting->setComment('AllowPersonalAgendaComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('display_coursecode_in_courselist');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('false');
        $setting->setTitle('DisplayCourseCodeInCourselistTitle');
        $setting->setComment('DisplayCourseCodeInCourselistComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('display_teacher_in_courselist');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('true');
        $setting->setTitle('DisplayTeacherInCourselistTitle');
        $setting->setComment('DisplayTeacherInCourselistComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('permanently_remove_deleted_files');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('PermanentlyRemoveFilesTitle');
        $setting->setComment('PermanentlyRemoveFilesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('dropbox_allow_overwrite');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('DropboxAllowOverwriteTitle');
        $setting->setComment('DropboxAllowOverwriteComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('dropbox_max_filesize');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('100000000');
        $setting->setTitle('DropboxMaxFilesizeTitle');
        $setting->setComment('DropboxMaxFilesizeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('dropbox_allow_just_upload');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('DropboxAllowJustUploadTitle');
        $setting->setComment('DropboxAllowJustUploadComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('dropbox_allow_student_to_student');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('DropboxAllowStudentToStudentTitle');
        $setting->setComment('DropboxAllowStudentToStudentComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('dropbox_allow_group');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('DropboxAllowGroupTitle');
        $setting->setComment('DropboxAllowGroupComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('dropbox_allow_mailing');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('DropboxAllowMailingTitle');
        $setting->setComment('DropboxAllowMailingComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('extended_profile');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('User');
        $setting->setSelectedValue('false');
        $setting->setTitle('ExtendedProfileTitle');
        $setting->setComment('ExtendedProfileComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('student_view_enabled');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('true');
        $setting->setTitle('StudentViewEnabledTitle');
        $setting->setComment('StudentViewEnabledComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_navigation_menu');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShowNavigationMenuTitle');
        $setting->setComment('ShowNavigationMenuComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enable_tool_introduction');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('course');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnableToolIntroductionTitle');
        $setting->setComment('EnableToolIntroductionComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('page_after_login');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('user_portal.php');
        $setting->setTitle('PageAfterLoginTitle');
        $setting->setComment('PageAfterLoginComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('time_limit_whosonline');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('30');
        $setting->setTitle('TimeLimitWhosonlineTitle');
        $setting->setComment('TimeLimitWhosonlineComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('breadcrumbs_course_homepage');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('course_title');
        $setting->setTitle('BreadCrumbsCourseHomepageTitle');
        $setting->setComment('BreadCrumbsCourseHomepageComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('example_material_course_creation');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ExampleMaterialCourseCreationTitle');
        $setting->setComment('ExampleMaterialCourseCreationComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('account_valid_duration');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('3660');
        $setting->setTitle('AccountValidDurationTitle');
        $setting->setComment('AccountValidDurationComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('use_session_mode');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Session');
        $setting->setSelectedValue('true');
        $setting->setTitle('UseSessionModeTitle');
        $setting->setComment('UseSessionModeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_email_editor');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowEmailEditorTitle');
        $setting->setComment('AllowEmailEditorComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('registered');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('');
        $setting->setSelectedValue('false');
        $setting->setTitle('');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('donotlistcampus');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('');
        $setting->setSelectedValue('false');
        $setting->setTitle('');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_email_addresses');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShowEmailAddresses');
        $setting->setComment('ShowEmailAddressesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('profile');
        $setting->setSubkey('phone');
        $setting->setType('checkbox');
        $setting->setCategory('User');
        $setting->setSelectedValue('false');
        $setting->setTitle('ProfileChangesTitle');
        $setting->setComment('ProfileChangesComment');
        $setting->setScope('');
        $setting->setSubkeytext('phone');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('service_visio');
        $setting->setSubkey('active');
        $setting->setType('radio');
        $setting->setCategory('');
        $setting->setSelectedValue('false');
        $setting->setTitle('VisioEnable');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('service_visio');
        $setting->setSubkey('visio_host');
        $setting->setType('textfield');
        $setting->setCategory('');
        $setting->setSelectedValue('');
        $setting->setTitle('VisioHost');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('service_visio');
        $setting->setSubkey('visio_port');
        $setting->setType('textfield');
        $setting->setCategory('');
        $setting->setSelectedValue('1935');
        $setting->setTitle('VisioPort');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('service_visio');
        $setting->setSubkey('visio_pass');
        $setting->setType('textfield');
        $setting->setCategory('');
        $setting->setSelectedValue('');
        $setting->setTitle('VisioPassword');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('service_ppt2lp');
        $setting->setSubkey('active');
        $setting->setType('radio');
        $setting->setCategory('');
        $setting->setSelectedValue('false');
        $setting->setTitle('ppt2lp_actived');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('service_ppt2lp');
        $setting->setSubkey('host');
        $setting->setType('textfield');
        $setting->setCategory('');
        $setting->setSelectedValue('');
        $setting->setTitle('Host');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('service_ppt2lp');
        $setting->setSubkey('port');
        $setting->setType('textfield');
        $setting->setCategory('');
        $setting->setSelectedValue('2002');
        $setting->setTitle('Port');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('service_ppt2lp');
        $setting->setSubkey('user');
        $setting->setType('textfield');
        $setting->setCategory('');
        $setting->setSelectedValue('');
        $setting->setTitle('UserOnHost');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('service_ppt2lp');
        $setting->setSubkey('ftp_password');
        $setting->setType('textfield');
        $setting->setCategory('');
        $setting->setSelectedValue('');
        $setting->setTitle('FtpPassword');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('service_ppt2lp');
        $setting->setSubkey('path_to_lzx');
        $setting->setType('textfield');
        $setting->setCategory('');
        $setting->setSelectedValue('');
        $setting->setTitle('');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('service_ppt2lp');
        $setting->setSubkey('size');
        $setting->setType('radio');
        $setting->setCategory('');
        $setting->setSelectedValue('720x540');
        $setting->setTitle('');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('wcag_anysurfer_public_pages');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('false');
        $setting->setTitle('PublicPagesComplyToWAITitle');
        $setting->setComment('PublicPagesComplyToWAIComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('stylesheets');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('stylesheets');
        $setting->setSelectedValue('chamilo');
        $setting->setTitle('');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('upload_extensions_list_type');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Security');
        $setting->setSelectedValue('blacklist');
        $setting->setTitle('UploadExtensionsListType');
        $setting->setComment('UploadExtensionsListTypeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);

            //aqui

        $setting = new SettingsCurrent();
        $setting->setVariable('upload_extensions_blacklist');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Security');
        $setting->setSelectedValue('');
        $setting->setTitle('UploadExtensionsBlacklist');
        $setting->setComment('UploadExtensionsBlacklistComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('upload_extensions_whitelist');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Security');
        $setting->setSelectedValue('htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg;mov;flv;doc;docx;xls;xlsx;ppt;pptx;odt;odp;ods;pdf,webm,oga,ogg,ogv,h264');
        $setting->setTitle('UploadExtensionsWhitelist');
        $setting->setComment('UploadExtensionsWhitelistComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('upload_extensions_skip');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Security');
        $setting->setSelectedValue('true');
        $setting->setTitle('UploadExtensionsSkip');
        $setting->setComment('UploadExtensionsSkipComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('upload_extensions_replace_by');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Security');
        $setting->setSelectedValue('dangerous');
        $setting->setTitle('UploadExtensionsReplaceBy');
        $setting->setComment('UploadExtensionsReplaceByComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_number_of_courses');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShowNumberOfCourses');
        $setting->setComment('ShowNumberOfCoursesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_empty_course_categories');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowEmptyCourseCategories');
        $setting->setComment('ShowEmptyCourseCategoriesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_back_link_on_top_of_tree');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShowBackLinkOnTopOfCourseTree');
        $setting->setComment('ShowBackLinkOnTopOfCourseTreeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_different_course_language');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowDifferentCourseLanguage');
        $setting->setComment('ShowDifferentCourseLanguageComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('split_users_upload_directory');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tuning');
        $setting->setSelectedValue('true');
        $setting->setTitle('SplitUsersUploadDirectory');
        $setting->setComment('SplitUsersUploadDirectoryComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('hide_dltt_markup');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Languages');
        $setting->setSelectedValue('true');
        $setting->setTitle('HideDLTTMarkup');
        $setting->setComment('HideDLTTMarkupComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('display_categories_on_homepage');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('DisplayCategoriesOnHomepageTitle');
        $setting->setComment('DisplayCategoriesOnHomepageComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('permissions_for_new_directories');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Security');
        $setting->setSelectedValue('0777');
        $setting->setTitle('PermissionsForNewDirs');
        $setting->setComment('PermissionsForNewDirsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('permissions_for_new_files');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Security');
        $setting->setSelectedValue('0666');
        $setting->setTitle('PermissionsForNewFiles');
        $setting->setComment('PermissionsForNewFilesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_tabs');
        $setting->setSubkey('campus_homepage');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowTabsTitle');
        $setting->setComment('ShowTabsComment');
        $setting->setScope('');
        $setting->setSubkeytext('TabsCampusHomepage');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_tabs');
        $setting->setSubkey('my_courses');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowTabsTitle');
        $setting->setComment('ShowTabsComment');
        $setting->setScope('');
        $setting->setSubkeytext('TabsMyCourses');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_tabs');
        $setting->setSubkey('reporting');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowTabsTitle');
        $setting->setComment('ShowTabsComment');
        $setting->setScope('');
        $setting->setSubkeytext('TabsReporting');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_tabs');
        $setting->setSubkey('platform_administration');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowTabsTitle');
        $setting->setComment('ShowTabsComment');
        $setting->setScope('');
        $setting->setSubkeytext('TabsPlatformAdministration');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_tabs');
        $setting->setSubkey('my_agenda');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowTabsTitle');
        $setting->setComment('ShowTabsComment');
        $setting->setScope('');
        $setting->setSubkeytext('TabsMyAgenda');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_tabs');
        $setting->setSubkey('my_profile');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowTabsTitle');
        $setting->setComment('ShowTabsComment');
        $setting->setScope('');
        $setting->setSubkeytext('TabsMyProfile');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('default_forum_view');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('flat');
        $setting->setTitle('DefaultForumViewTitle');
        $setting->setComment('DefaultForumViewComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('platform_charset');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Languages');
        $setting->setSelectedValue('UTF-8');
        $setting->setTitle('PlatformCharsetTitle');
        $setting->setComment('PlatformCharsetComment');
        $setting->setScope('platform');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('survey_email_sender_noreply');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('coach');
        $setting->setTitle('SurveyEmailSenderNoReply');
        $setting->setComment('SurveyEmailSenderNoReplyComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('openid_authentication');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Security');
        $setting->setSelectedValue('false');
        $setting->setTitle('OpenIdAuthentication');
        $setting->setComment('OpenIdAuthenticationComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('profile');
        $setting->setSubkey('openid');
        $setting->setType('checkbox');
        $setting->setCategory('User');
        $setting->setSelectedValue('false');
        $setting->setTitle('ProfileChangesTitle');
        $setting->setComment('ProfileChangesComment');
        $setting->setScope('');
        $setting->setSubkeytext('OpenIDURL');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('gradebook_enable');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('false');
        $setting->setTitle('GradebookActivation');
        $setting->setComment('GradebookActivationComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_tabs');
        $setting->setSubkey('my_gradebook');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowTabsTitle');
        $setting->setComment('ShowTabsComment');
        $setting->setScope('');
        $setting->setSubkeytext('TabsMyGradebook');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('gradebook_score_display_coloring');
        $setting->setSubkey('my_display_coloring');
        $setting->setType('checkbox');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('false');
        $setting->setTitle('GradebookScoreDisplayColoring');
        $setting->setComment('GradebookScoreDisplayColoringComment');
        $setting->setScope('');
        $setting->setSubkeytext('TabsGradebookEnableColoring');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('gradebook_score_display_custom');
        $setting->setSubkey('my_display_custom');
        $setting->setType('checkbox');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('false');
        $setting->setTitle('GradebookScoreDisplayCustom');
        $setting->setComment('GradebookScoreDisplayCustomComment');
        $setting->setScope('');
        $setting->setSubkeytext('TabsGradebookEnableCustom');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('gradebook_score_display_colorsplit');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('50');
        $setting->setTitle('GradebookScoreDisplayColorSplit');
        $setting->setComment('GradebookScoreDisplayColorSplitComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('gradebook_score_display_upperlimit');
        $setting->setSubkey('my_display_upperlimit');
        $setting->setType('checkbox');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('false');
        $setting->setTitle('GradebookScoreDisplayUpperLimit');
        $setting->setComment('GradebookScoreDisplayUpperLimitComment');
        $setting->setScope('');
        $setting->setSubkeytext('TabsGradebookEnableUpperLimit');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('gradebook_number_decimals');
        $setting->setSubkey('');
        $setting->setType('select');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('0');
        $setting->setTitle('GradebookNumberDecimals');
        $setting->setComment('GradebookNumberDecimalsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('user_selected_theme');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('User');
        $setting->setSelectedValue('false');
        $setting->setTitle('UserThemeSelection');
        $setting->setComment('UserThemeSelectionComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('profile');
        $setting->setSubkey('theme');
        $setting->setType('checkbox');
        $setting->setCategory('User');
        $setting->setSelectedValue('false');
        $setting->setTitle('ProfileChangesTitle');
        $setting->setComment('ProfileChangesComment');
        $setting->setScope('');
        $setting->setSubkeytext('UserTheme');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_course_theme');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowCourseThemeTitle');
        $setting->setComment('AllowCourseThemeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('display_mini_month_calendar');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('DisplayMiniMonthCalendarTitle');
        $setting->setComment('DisplayMiniMonthCalendarComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('display_upcoming_events');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('DisplayUpcomingEventsTitle');
        $setting->setComment('DisplayUpcomingEventsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('number_of_upcoming_events');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('1');
        $setting->setTitle('NumberOfUpcomingEventsTitle');
        $setting->setComment('NumberOfUpcomingEventsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_closed_courses');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShowClosedCoursesTitle');
        $setting->setComment('ShowClosedCoursesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('service_visio');
        $setting->setSubkey('visio_use_rtmpt');
        $setting->setType('radio');
        $setting->setCategory('');
        $setting->setSelectedValue('false');
        $setting->setTitle('VisioUseRtmptTitle');
        $setting->setComment('VisioUseRtmptComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('extendedprofile_registration');
        $setting->setSubkey('mycomptetences');
        $setting->setType('checkbox');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('ExtendedProfileRegistrationTitle');
        $setting->setComment('ExtendedProfileRegistrationComment');
        $setting->setScope('');
        $setting->setSubkeytext('MyCompetences');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('extendedprofile_registration');
        $setting->setSubkey('mydiplomas');
        $setting->setType('checkbox');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('ExtendedProfileRegistrationTitle');
        $setting->setComment('ExtendedProfileRegistrationComment');
        $setting->setScope('');
        $setting->setSubkeytext('MyDiplomas');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('extendedprofile_registration');
        $setting->setSubkey('myteach');
        $setting->setType('checkbox');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('ExtendedProfileRegistrationTitle');
        $setting->setComment('ExtendedProfileRegistrationComment');
        $setting->setScope('');
        $setting->setSubkeytext('MyTeach');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('extendedprofile_registration');
        $setting->setSubkey('mypersonalopenarea');
        $setting->setType('checkbox');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('ExtendedProfileRegistrationTitle');
        $setting->setComment('ExtendedProfileRegistrationComment');
        $setting->setScope('');
        $setting->setSubkeytext('MyPersonalOpenArea');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('extendedprofile_registrationrequired');
        $setting->setSubkey('mycomptetences');
        $setting->setType('checkbox');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('ExtendedProfileRegistrationRequiredTitle');
        $setting->setComment('ExtendedProfileRegistrationRequiredComment');
        $setting->setScope('');
        $setting->setSubkeytext('MyCompetences');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('extendedprofile_registrationrequired');
        $setting->setSubkey('mydiplomas');
        $setting->setType('checkbox');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('ExtendedProfileRegistrationRequiredTitle');
        $setting->setComment('ExtendedProfileRegistrationRequiredComment');
        $setting->setScope('');
        $setting->setSubkeytext('MyDiplomas');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('extendedprofile_registrationrequired');
        $setting->setSubkey('myteach');
        $setting->setType('checkbox');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('ExtendedProfileRegistrationRequiredTitle');
        $setting->setComment('ExtendedProfileRegistrationRequiredComment');
        $setting->setScope('');
        $setting->setSubkeytext('MyTeach');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('extendedprofile_registrationrequired');
        $setting->setSubkey('mypersonalopenarea');
        $setting->setType('checkbox');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('ExtendedProfileRegistrationRequiredTitle');
        $setting->setComment('ExtendedProfileRegistrationRequiredComment');
        $setting->setScope('');
        $setting->setSubkeytext('MyPersonalOpenArea');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('registration');
        $setting->setSubkey('phone');
        $setting->setType('textfield');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('RegistrationRequiredFormsTitle');
        $setting->setComment('RegistrationRequiredFormsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Phone');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('add_users_by_coach');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Session');
        $setting->setSelectedValue('false');
        $setting->setTitle('AddUsersByCoachTitle');
        $setting->setComment('AddUsersByCoachComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('extend_rights_for_coach');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Security');
        $setting->setSelectedValue('false');
        $setting->setTitle('ExtendRightsForCoachTitle');
        $setting->setComment('ExtendRightsForCoachComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('extend_rights_for_coach_on_survey');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Security');
        $setting->setSelectedValue('true');
        $setting->setTitle('ExtendRightsForCoachOnSurveyTitle');
        $setting->setComment('ExtendRightsForCoachOnSurveyComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('wiki');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Wiki');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_session_coach');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Session');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShowSessionCoachTitle');
        $setting->setComment('ShowSessionCoachComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('gradebook');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Gradebook');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_users_to_create_courses');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowUsersToCreateCoursesTitle');
        $setting->setComment('AllowUsersToCreateCoursesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('survey');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Survey');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('glossary');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Glossary');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('notebook');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Notebook');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('attendances');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Attendances');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('course_progress');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('CourseProgress');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('advanced_filemanager');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('true');
        $setting->setTitle('AdvancedFileManagerTitle');
        $setting->setComment('AdvancedFileManagerComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_reservation');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowReservationTitle');
        $setting->setComment('AllowReservationComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('profile');
        $setting->setSubkey('apikeys');
        $setting->setType('checkbox');
        $setting->setCategory('User');
        $setting->setSelectedValue('false');
        $setting->setTitle('ProfileChangesTitle');
        $setting->setComment('ProfileChangesComment');
        $setting->setScope('');
        $setting->setSubkeytext('ApiKeys');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_message_tool');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowMessageToolTitle');
        $setting->setComment('AllowMessageToolComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_social_tool');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowSocialToolTitle');
        $setting->setComment('AllowSocialToolComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_students_to_browse_courses');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowStudentsToBrowseCoursesTitle');
        $setting->setComment('AllowStudentsToBrowseCoursesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_session_data');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Session');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShowSessionDataTitle');
        $setting->setComment('ShowSessionDataComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_use_sub_language');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Languages');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowUseSubLanguageTitle');
        $setting->setComment('AllowUseSubLanguageComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_glossary_in_documents');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('none');
        $setting->setTitle('ShowGlossaryInDocumentsTitle');
        $setting->setComment('ShowGlossaryInDocumentsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_terms_conditions');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowTermsAndConditionsTitle');
        $setting->setComment('AllowTermsAndConditionsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_create_active_tools');
        $setting->setSubkey('enable_search');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseCreateActiveToolsTitle');
        $setting->setComment('CourseCreateActiveToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Search');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('search_enabled');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Search');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnableSearchTitle');
        $setting->setComment('EnableSearchComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('search_prefilter_prefix');
        $setting->setSubkey('');
        $setting->setType('');
        $setting->setCategory('Search');
        $setting->setSelectedValue('');
        $setting->setTitle('SearchPrefilterPrefix');
        $setting->setComment('SearchPrefilterPrefixComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('search_show_unlinked_results');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Search');
        $setting->setSelectedValue('true');
        $setting->setTitle('SearchShowUnlinkedResultsTitle');
        $setting->setComment('SearchShowUnlinkedResultsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_courses_descriptions_in_catalog');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowCoursesDescriptionsInCatalogTitle');
        $setting->setComment('ShowCoursesDescriptionsInCatalogComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_coach_to_edit_course_session');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Session');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowCoachsToEditInsideTrainingSessions');
        $setting->setComment('AllowCoachsToEditInsideTrainingSessionsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        /*$setting = new SettingsCurrent();
        $setting->setVariable('show_glossary_in_extra_tools');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShowGlossaryInExtraToolsTitle');
        $setting->setComment('ShowGlossaryInExtraToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);
*/

        $setting = new SettingsCurrent();
        $setting->setVariable('send_email_to_admin_when_create_course');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('SendEmailToAdminTitle');
        $setting->setComment('SendEmailToAdminComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('go_to_course_after_login');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('false');
        $setting->setTitle('GoToCourseAfterLoginTitle');
        $setting->setComment('GoToCourseAfterLoginComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('math_mimetex');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('false');
        $setting->setTitle('MathMimetexTitle');
        $setting->setComment('MathMimetexComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('math_asciimathML');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('false');
        $setting->setTitle('MathASCIImathMLTitle');
        $setting->setComment('MathASCIImathMLComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enabled_asciisvg');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('false');
        $setting->setTitle('AsciiSvgTitle');
        $setting->setComment('AsciiSvgComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('include_asciimathml_script');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('false');
        $setting->setTitle('IncludeAsciiMathMlTitle');
        $setting->setComment('IncludeAsciiMathMlComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('youtube_for_students');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('true');
        $setting->setTitle('YoutubeForStudentsTitle');
        $setting->setComment('YoutubeForStudentsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('block_copy_paste_for_students');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('false');
        $setting->setTitle('BlockCopyPasteForStudentsTitle');
        $setting->setComment('BlockCopyPasteForStudentsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('more_buttons_maximized_mode');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('true');
        $setting->setTitle('MoreButtonsForMaximizedModeTitle');
        $setting->setComment('MoreButtonsForMaximizedModeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('students_download_folders');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowStudentsDownloadFoldersTitle');
        $setting->setComment('AllowStudentsDownloadFoldersComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('users_copy_files');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowUsersCopyFilesTitle');
        $setting->setComment('AllowUsersCopyFilesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_tabs');
        $setting->setSubkey('social');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowTabsTitle');
        $setting->setComment('ShowTabsComment');
        $setting->setScope('');
        $setting->setSubkeytext('TabsSocial');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_students_to_create_groups_in_social');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowStudentsToCreateGroupsInSocialTitle');
        $setting->setComment('AllowStudentsToCreateGroupsInSocialComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_send_message_to_all_platform_users');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowSendMessageToAllPlatformUsersTitle');
        $setting->setComment('AllowSendMessageToAllPlatformUsersComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('message_max_upload_filesize');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('20971520');
        $setting->setTitle('MessageMaxUploadFilesizeTitle');
        $setting->setComment('MessageMaxUploadFilesizeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_tabs');
        $setting->setSubkey('dashboard');
        $setting->setType('checkbox');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowTabsTitle');
        $setting->setComment('ShowTabsComment');
        $setting->setScope('');
        $setting->setSubkeytext('TabsDashboard');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('use_users_timezone');
        $setting->setSubkey('timezones');
        $setting->setType('radio');
        $setting->setCategory('Timezones');
        $setting->setSelectedValue('true');
        $setting->setTitle('UseUsersTimezoneTitle');
        $setting->setComment('UseUsersTimezoneComment');
        $setting->setScope('');
        $setting->setSubkeytext('Timezones');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('timezone_value');
        $setting->setSubkey('timezones');
        $setting->setType('select');
        $setting->setCategory('Timezones');
        $setting->setSelectedValue('');
        $setting->setTitle('TimezoneValueTitle');
        $setting->setComment('TimezoneValueComment');
        $setting->setScope('');
        $setting->setSubkeytext('Timezones');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_user_course_subscription_by_course_admin');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Security');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowUserCourseSubscriptionByCourseAdminTitle');
        $setting->setComment('AllowUserCourseSubscriptionByCourseAdminComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_link_bug_notification');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowLinkBugNotificationTitle');
        $setting->setComment('ShowLinkBugNotificationComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_validation');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnableCourseValidation');
        $setting->setComment('EnableCourseValidationComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_validation_terms_and_conditions_url');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('');
        $setting->setTitle('CourseValidationTermsAndConditionsLink');
        $setting->setComment('CourseValidationTermsAndConditionsLinkComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('sso_authentication');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Security');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnableSSOTitle');
        $setting->setComment('EnableSSOComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('sso_authentication_domain');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Security');
        $setting->setSelectedValue('');
        $setting->setTitle('SSOServerDomainTitle');
        $setting->setComment('SSOServerDomainComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('sso_authentication_auth_uri');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Security');
        $setting->setSelectedValue('/?q=user');
        $setting->setTitle('SSOServerAuthURITitle');
        $setting->setComment('SSOServerAuthURIComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('sso_authentication_unauth_uri');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Security');
        $setting->setSelectedValue('/?q=logout');
        $setting->setTitle('SSOServerUnAuthURITitle');
        $setting->setComment('SSOServerUnAuthURIComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('sso_authentication_protocol');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Security');
        $setting->setSelectedValue('http://');
        $setting->setTitle('SSOServerProtocolTitle');
        $setting->setComment('SSOServerProtocolComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enabled_wiris');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnabledWirisTitle');
        $setting->setComment('EnabledWirisComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_spellcheck');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowSpellCheckTitle');
        $setting->setComment('AllowSpellCheckComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('force_wiki_paste_as_plain_text');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('false');
        $setting->setTitle('ForceWikiPasteAsPlainTextTitle');
        $setting->setComment('ForceWikiPasteAsPlainTextComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enabled_googlemaps');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnabledGooglemapsTitle');
        $setting->setComment('EnabledGooglemapsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enabled_imgmap');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('true');
        $setting->setTitle('EnabledImageMapsTitle');
        $setting->setComment('EnabledImageMapsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enabled_support_svg');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('EnabledSVGTitle');
        $setting->setComment('EnabledSVGComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('pdf_export_watermark_enable');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('PDFExportWatermarkEnableTitle');
        $setting->setComment('PDFExportWatermarkEnableComment');
        $setting->setScope('platform');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('pdf_export_watermark_by_course');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('PDFExportWatermarkByCourseTitle');
        $setting->setComment('PDFExportWatermarkByCourseComment');
        $setting->setScope('platform');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('pdf_export_watermark_text');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('');
        $setting->setTitle('PDFExportWatermarkTextTitle');
        $setting->setComment('PDFExportWatermarkTextComment');
        $setting->setScope('platform');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enabled_insertHtml');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('true');
        $setting->setTitle('EnabledInsertHtmlTitle');
        $setting->setComment('EnabledInsertHtmlComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('students_export2pdf');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('EnabledStudentExport2PDFTitle');
        $setting->setComment('EnabledStudentExport2PDFComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('exercise_min_score');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Course');
        $setting->setSelectedValue('');
        $setting->setTitle('ExerciseMinScoreTitle');
        $setting->setComment('ExerciseMinScoreComment');
        $setting->setScope('platform');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('exercise_max_score');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Course');
        $setting->setSelectedValue('');
        $setting->setTitle('ExerciseMaxScoreTitle');
        $setting->setComment('ExerciseMaxScoreComment');
        $setting->setScope('platform');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_users_folders');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowUsersFoldersTitle');
        $setting->setComment('ShowUsersFoldersComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_default_folders');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowDefaultFoldersTitle');
        $setting->setComment('ShowDefaultFoldersComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_chat_folder');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowChatFolderTitle');
        $setting->setComment('ShowChatFolderComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enabled_text2audio');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('Text2AudioTitle');
        $setting->setComment('Text2AudioComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('course_description');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('CourseDescription');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('calendar_event');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Agenda');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('document');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Documents');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('learnpath');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('LearningPath');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('link');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Links');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('announcement');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Announcements');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('forum');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Forums');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('dropbox');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Dropbox');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('quiz');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Quiz');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('user');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Users');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('group');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Groups');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('chat');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Chat');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('student_publication');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('StudentPublications');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('wiki');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Wiki');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('gradebook');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Gradebook');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('survey');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Survey');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('glossary');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Glossary');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('notebook');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Notebook');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('attendance');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Attendances');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('course_progress');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('CourseProgress');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('blog_management');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Blog');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('tracking');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Stats');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('course_maintenance');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('Maintenance');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('course_hide_tools');
        $setting->setSubkey('course_setting');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('CourseHideToolsTitle');
        $setting->setComment('CourseHideToolsComment');
        $setting->setScope('');
        $setting->setSubkeytext('CourseSettings');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enabled_support_pixlr');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnabledPixlrTitle');
        $setting->setComment('EnabledPixlrComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_groups_to_users');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Session');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShowGroupsToUsersTitle');
        $setting->setComment('ShowGroupsToUsersComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('accessibility_font_resize');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnableAccessibilityFontResizeTitle');
        $setting->setComment('EnableAccessibilityFontResizeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('hide_courses_in_sessions');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Session');
        $setting->setSelectedValue('false');
        $setting->setTitle('HideCoursesInSessionsTitle');
        $setting->setComment('HideCoursesInSessionsComment');
        $setting->setScope('platform');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enable_quiz_scenario');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnableQuizScenarioTitle');
        $setting->setComment('EnableQuizScenarioComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enable_nanogong');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnableNanogongTitle');
        $setting->setComment('EnableNanogongComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('filter_terms');
        $setting->setSubkey('');
        $setting->setType('textarea');
        $setting->setCategory('Security');
        $setting->setSelectedValue('');
        $setting->setTitle('FilterTermsTitle');
        $setting->setComment('FilterTermsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('header_extra_content');
        $setting->setSubkey('');
        $setting->setType('textarea');
        $setting->setCategory('Tracking');
        $setting->setSelectedValue('');
        $setting->setTitle('HeaderExtraContentTitle');
        $setting->setComment('HeaderExtraContentComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('footer_extra_content');
        $setting->setSubkey('');
        $setting->setType('textarea');
        $setting->setCategory('Tracking');
        $setting->setSelectedValue('');
        $setting->setTitle('FooterExtraContentTitle');
        $setting->setComment('FooterExtraContentComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_documents_preview');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShowDocumentPreviewTitle');
        $setting->setComment('ShowDocumentPreviewComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('htmlpurifier_wiki');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('false');
        $setting->setTitle('HtmlPurifierWikiTitle');
        $setting->setComment('HtmlPurifierWikiComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('cas_activate');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('CAS');
        $setting->setSelectedValue('false');
        $setting->setTitle('CasMainActivateTitle');
        $setting->setComment('CasMainActivateComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('cas_server');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('CAS');
        $setting->setSelectedValue('');
        $setting->setTitle('CasMainServerTitle');
        $setting->setComment('CasMainServerComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('cas_server_uri');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('CAS');
        $setting->setSelectedValue('');
        $setting->setTitle('CasMainServerURITitle');
        $setting->setComment('CasMainServerURIComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('cas_port');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('CAS');
        $setting->setSelectedValue('');
        $setting->setTitle('CasMainPortTitle');
        $setting->setComment('CasMainPortComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('cas_protocol');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('CAS');
        $setting->setSelectedValue('');
        $setting->setTitle('CasMainProtocolTitle');
        $setting->setComment('CasMainProtocolComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('cas_add_user_activate');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('CAS');
        $setting->setSelectedValue('false');
        $setting->setTitle('CasUserAddActivateTitle');
        $setting->setComment('CasUserAddActivateComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('update_user_info_cas_with_ldap');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('CAS');
        $setting->setSelectedValue('true');
        $setting->setTitle('UpdateUserInfoCasWithLdapTitle');
        $setting->setComment('UpdateUserInfoCasWithLdapComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('student_page_after_login');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('');
        $setting->setTitle('StudentPageAfterLoginTitle');
        $setting->setComment('StudentPageAfterLoginComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('teacher_page_after_login');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('');
        $setting->setTitle('TeacherPageAfterLoginTitle');
        $setting->setComment('TeacherPageAfterLoginComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('drh_page_after_login');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('');
        $setting->setTitle('DRHPageAfterLoginTitle');
        $setting->setComment('DRHPageAfterLoginComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('sessionadmin_page_after_login');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('');
        $setting->setTitle('SessionAdminPageAfterLoginTitle');
        $setting->setComment('SessionAdminPageAfterLoginComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('student_autosubscribe');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('');
        $setting->setTitle('StudentAutosubscribeTitle');
        $setting->setComment('StudentAutosubscribeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('teacher_autosubscribe');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('');
        $setting->setTitle('TeacherAutosubscribeTitle');
        $setting->setComment('TeacherAutosubscribeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('drh_autosubscribe');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('');
        $setting->setTitle('DRHAutosubscribeTitle');
        $setting->setComment('DRHAutosubscribeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('sessionadmin_autosubscribe');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('');
        $setting->setTitle('SessionadminAutosubscribeTitle');
        $setting->setComment('SessionadminAutosubscribeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('scorm_cumulative_session_time');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('true');
        $setting->setTitle('ScormCumulativeSessionTimeTitle');
        $setting->setComment('ScormCumulativeSessionTimeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_hr_skills_management');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowHRSkillsManagementTitle');
        $setting->setComment('AllowHRSkillsManagementComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enable_help_link');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('EnableHelpLinkTitle');
        $setting->setComment('EnableHelpLinkComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('teachers_can_change_score_settings');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('true');
        $setting->setTitle('TeachersCanChangeScoreSettingsTitle');
        $setting->setComment('TeachersCanChangeScoreSettingsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_users_to_change_email_with_no_password');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('User');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowUsersToChangeEmailWithNoPasswordTitle');
        $setting->setComment('AllowUsersToChangeEmailWithNoPasswordComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_admin_toolbar');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('show_to_admin');
        $setting->setTitle('ShowAdminToolbarTitle');
        $setting->setComment('ShowAdminToolbarComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_global_chat');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('AllowGlobalChatTitle');
        $setting->setComment('AllowGlobalChatComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('languagePriority1');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Languages');
        $setting->setSelectedValue('course_lang');
        $setting->setTitle('LanguagePriority1Title');
        $setting->setComment('LanguagePriority1Comment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('languagePriority2');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Languages');
        $setting->setSelectedValue('user_profil_lang');
        $setting->setTitle('LanguagePriority2Title');
        $setting->setComment('LanguagePriority2Comment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('languagePriority3');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Languages');
        $setting->setSelectedValue('user_selected_lang');
        $setting->setTitle('LanguagePriority3Title');
        $setting->setComment('LanguagePriority3Comment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('languagePriority4');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Languages');
        $setting->setSelectedValue('platform_lang');
        $setting->setTitle('LanguagePriority4Title');
        $setting->setComment('LanguagePriority4Comment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('login_is_email');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('LoginIsEmailTitle');
        $setting->setComment('LoginIsEmailComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('courses_default_creation_visibility');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('2');
        $setting->setTitle('CoursesDefaultCreationVisibilityTitle');
        $setting->setComment('CoursesDefaultCreationVisibilityComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_browser_sniffer');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tuning');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowBrowserSnifferTitle');
        $setting->setComment('AllowBrowserSnifferComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enable_wami_record');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnableWamiRecordTitle');
        $setting->setComment('EnableWamiRecordComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('gradebook_enable_grade_model');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('false');
        $setting->setTitle('GradebookEnableGradeModelTitle');
        $setting->setComment('GradebookEnableGradeModelComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);




        $setting = new SettingsCurrent();
        $setting->setVariable('gradebook_default_weight');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('100');
        $setting->setTitle('GradebookDefaultWeightTitle');
        $setting->setComment('GradebookDefaultWeightComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('ldap_description');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('LDAP');
        $setting->setSelectedValue('');
        $setting->setTitle('LdapDescriptionTitle');
        $setting->setComment('LdapDescriptionComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('shibboleth_description');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Shibboleth');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShibbolethMainActivateTitle');
        $setting->setComment('ShibbolethMainActivateComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('facebook_description');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Facebook');
        $setting->setSelectedValue('false');
        $setting->setTitle('FacebookMainActivateTitle');
        $setting->setComment('FacebookMainActivateComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('gradebook_locking_enabled');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('false');
        $setting->setTitle('GradebookEnableLockingTitle');
        $setting->setComment('GradebookEnableLockingComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('gradebook_default_grade_model_id');
        $setting->setSubkey('');
        $setting->setType('select');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('');
        $setting->setTitle('GradebookDefaultGradeModelTitle');
        $setting->setComment('GradebookDefaultGradeModelComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_session_admins_to_manage_all_sessions');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Session');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowSessionAdminsToSeeAllSessionsTitle');
        $setting->setComment('AllowSessionAdminsToSeeAllSessionsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_skills_tool');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowSkillsToolTitle');
        $setting->setComment('AllowSkillsToolComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_public_certificates');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Course');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowPublicCertificatesTitle');
        $setting->setComment('AllowPublicCertificatesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('platform_unsubscribe_allowed');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Registration');
        $setting->setSelectedValue('false');
        $setting->setTitle('PlatformUnsubscribeTitle');
        $setting->setComment('PlatformUnsubscribeComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('activate_email_template');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('ActivateEmailTemplateTitle');
        $setting->setComment('ActivateEmailTemplateComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enable_iframe_inclusion');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Editor');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnableIframeInclusionTitle');
        $setting->setComment('EnableIframeInclusionComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('show_hot_courses');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('ShowHotCoursesTitle');
        $setting->setComment('ShowHotCoursesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('enable_webcam_clip');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('EnableWebCamClipTitle');
        $setting->setComment('EnableWebCamClipComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('use_custom_pages');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('UseCustomPagesTitle');
        $setting->setComment('UseCustomPagesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('tool_visible_by_default_at_creation');
        $setting->setSubkey('documents');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('ToolVisibleByDefaultAtCreationTitle');
        $setting->setComment('ToolVisibleByDefaultAtCreationComment');
        $setting->setScope('');
        $setting->setSubkeytext('Documents');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('tool_visible_by_default_at_creation');
        $setting->setSubkey('learning_path');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('ToolVisibleByDefaultAtCreationTitle');
        $setting->setComment('ToolVisibleByDefaultAtCreationComment');
        $setting->setScope('');
        $setting->setSubkeytext('LearningPath');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('tool_visible_by_default_at_creation');
        $setting->setSubkey('links');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('ToolVisibleByDefaultAtCreationTitle');
        $setting->setComment('ToolVisibleByDefaultAtCreationComment');
        $setting->setScope('');
        $setting->setSubkeytext('Links');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('tool_visible_by_default_at_creation');
        $setting->setSubkey('announcements');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('ToolVisibleByDefaultAtCreationTitle');
        $setting->setComment('ToolVisibleByDefaultAtCreationComment');
        $setting->setScope('');
        $setting->setSubkeytext('Announcements');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('tool_visible_by_default_at_creation');
        $setting->setSubkey('forums');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('ToolVisibleByDefaultAtCreationTitle');
        $setting->setComment('ToolVisibleByDefaultAtCreationComment');
        $setting->setScope('');
        $setting->setSubkeytext('Forums');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('tool_visible_by_default_at_creation');
        $setting->setSubkey('quiz');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('ToolVisibleByDefaultAtCreationTitle');
        $setting->setComment('ToolVisibleByDefaultAtCreationComment');
        $setting->setScope('');
        $setting->setSubkeytext('Quiz');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('tool_visible_by_default_at_creation');
        $setting->setSubkey('gradebook');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('true');
        $setting->setTitle('ToolVisibleByDefaultAtCreationTitle');
        $setting->setComment('ToolVisibleByDefaultAtCreationComment');
        $setting->setScope('');
        $setting->setSubkeytext('Gradebook');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('session_tutor_reports_visibility');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Session');
        $setting->setSelectedValue('true');
        $setting->setTitle('SessionTutorsCanSeeExpiredSessionsResultsTitle');
        $setting->setComment('SessionTutorsCanSeeExpiredSessionsResultsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('gradebook_show_percentage_in_reports');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Gradebook');
        $setting->setSelectedValue('true');
        $setting->setTitle('GradebookShowPercentageInReportsTitle');
        $setting->setComment('GradebookShowPercentageInReportsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('session_page_enabled');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Session');
        $setting->setSelectedValue('true');
        $setting->setTitle('SessionPageEnabledTitle');
        $setting->setComment('SessionPageEnabledComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('settings_latest_update');
        $setting->setSubkey('');
        $setting->setType('');
        $setting->setCategory('');
        $setting->setSelectedValue('');
        $setting->setTitle('');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('user_name_order');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('');
        $setting->setTitle('UserNameOrderTitle');
        $setting->setComment('UserNameOrderComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('user_name_sort_by');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('');
        $setting->setTitle('UserNameSortByTitle');
        $setting->setComment('UserNameSortByComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_teachers_to_create_sessions');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Session');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowTeachersToCreateSessionsTitle');
        $setting->setComment('AllowTeachersToCreateSessionsComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('use_virtual_keyboard');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('ShowVirtualKeyboardTitle');
        $setting->setComment('ShowVirtualKeyboardComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('disable_copy_paste');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('false');
        $setting->setTitle('DisableCopyPasteTitle');
        $setting->setComment('DisableCopyPasteComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('login_as_allowed'); // N???
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Security');
        $setting->setSelectedValue('true');
        $setting->setTitle('AdminLoginAsAllowedTitle');
        $setting->setComment('AdminLoginAsAllowedComment');
        $setting->setScope('1');
        $setting->setSubkeytext('0');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('admins_can_set_users_pass');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('security');
        $setting->setSelectedValue('true');
        $setting->setTitle('AdminsCanChangeUsersPassTitle');
        $setting->setComment('AdminsCanChangeUsersPassComment');
        $setting->setScope('1');
        $setting->setSubkeytext('0');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('template');
        $setting->setSubkey('');
        $setting->setType('text');
        $setting->setCategory('stylesheets');
        $setting->setSelectedValue('default');
        $setting->setTitle('DefaultTemplateTitle');
        $setting->setComment('DefaultTemplateComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('breadcrumb_navigation_display');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('true');
        $setting->setTitle('BreadcrumbNavigationDisplayTitle');
        $setting->setComment('BreadcrumbNavigationDisplayComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('default_calendar_view');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('month');
        $setting->setTitle('DefaultCalendarViewTitle');
        $setting->setComment('DefaultCalendarViewComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('documents_default_visibility_defined_in_course');
        $setting->setSubkey('');
        $setting->setType('checkbox');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('DocumentsDefaultVisibilityDefinedInCourseTitle');
        $setting->setComment('DocumentsDefaultVisibilityDefinedInCourseComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('allow_personal_user_files');
        $setting->setSubkey('');
        $setting->setType('radio');
        $setting->setCategory('Tools');
        $setting->setSelectedValue('false');
        $setting->setTitle('AllowPersonalUserFilesTitle');
        $setting->setComment('AllowPersonalUserFilesComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('bug_report_link');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('Platform');
        $setting->setSelectedValue('');
        $setting->setTitle('BugReportLinkTitle');
        $setting->setComment('BugReportLinkComment');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('1');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $setting = new SettingsCurrent();
        $setting->setVariable('chamilo_database_version');
        $setting->setSubkey('');
        $setting->setType('textfield');
        $setting->setCategory('');
        $setting->setSelectedValue('10.001');
        $setting->setTitle('DatabaseVersion');
        $setting->setComment('');
        $setting->setScope('');
        $setting->setSubkeytext('');
        $setting->setAccessUrlChangeable('0');
        $setting->setUrl($accessUrl);
        $manager->persist($setting);


        $manager->flush();

    }

    /**
     * @param ObjectManager $manager
     */
    public function createOptions(ObjectManager $manager)
    {


        $option = new SettingsOptions();
        $option->setVariable('show_administrator_data');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_administrator_data');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_tutor_data');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_tutor_data');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_teacher_data');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_teacher_data');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('homepage_view');
        $option->setValue('activity');
        $option->setDisplayText('HomepageViewActivity');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('homepage_view');
        $option->setValue('2column');
        $option->setDisplayText('HomepageView2column');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('homepage_view');
        $option->setValue('3column');
        $option->setDisplayText('HomepageView3column');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('homepage_view');
        $option->setValue('vertical_activity');
        $option->setDisplayText('HomepageViewVerticalActivity');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('homepage_view');
        $option->setValue('activity_big');
        $option->setDisplayText('HomepageViewActivityBig');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_toolshortcuts');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_toolshortcuts');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_group_categories');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_group_categories');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_name_change');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_name_change');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_officialcode_change');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_officialcode_change');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_registration');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_registration');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_registration');
        $option->setValue('approval');
        $option->setDisplayText('AfterApproval');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_registration_as_teacher');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_registration_as_teacher');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_lostpassword');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_lostpassword');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_user_headings');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_user_headings');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_personal_agenda');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_personal_agenda');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('display_coursecode_in_courselist');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('display_coursecode_in_courselist');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('display_teacher_in_courselist');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('display_teacher_in_courselist');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('permanently_remove_deleted_files');
        $option->setValue('true');
        $option->setDisplayText('YesWillDeletePermanently');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('permanently_remove_deleted_files');
        $option->setValue('false');
        $option->setDisplayText('NoWillDeletePermanently');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('dropbox_allow_overwrite');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('dropbox_allow_overwrite');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('dropbox_allow_just_upload');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('dropbox_allow_just_upload');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('dropbox_allow_student_to_student');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('dropbox_allow_student_to_student');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('dropbox_allow_group');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('dropbox_allow_group');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('dropbox_allow_mailing');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('dropbox_allow_mailing');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('extended_profile');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('extended_profile');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('student_view_enabled');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('student_view_enabled');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_navigation_menu');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_navigation_menu');
        $option->setValue('icons');
        $option->setDisplayText('IconsOnly');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_navigation_menu');
        $option->setValue('text');
        $option->setDisplayText('TextOnly');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_navigation_menu');
        $option->setValue('iconstext');
        $option->setDisplayText('IconsText');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_tool_introduction');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_tool_introduction');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('page_after_login');
        $option->setValue('index.php');
        $option->setDisplayText('CampusHomepage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('page_after_login');
        $option->setValue('user_portal.php');
        $option->setDisplayText('MyCourses');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('page_after_login');
        $option->setValue('main/auth/courses.php');
        $option->setDisplayText('CourseCatalog');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('breadcrumbs_course_homepage');
        $option->setValue('get_lang');
        $option->setDisplayText('CourseHomepage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('breadcrumbs_course_homepage');
        $option->setValue('course_code');
        $option->setDisplayText('CourseCode');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('breadcrumbs_course_homepage');
        $option->setValue('course_title');
        $option->setDisplayText('CourseTitle');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('example_material_course_creation');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('example_material_course_creation');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('use_session_mode');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('use_session_mode');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_email_editor');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_email_editor');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_email_addresses');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_email_addresses');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('wcag_anysurfer_public_pages');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('wcag_anysurfer_public_pages');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('upload_extensions_list_type');
        $option->setValue('blacklist');
        $option->setDisplayText('Blacklist');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('upload_extensions_list_type');
        $option->setValue('whitelist');
        $option->setDisplayText('Whitelist');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('upload_extensions_skip');
        $option->setValue('true');
        $option->setDisplayText('Remove');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('upload_extensions_skip');
        $option->setValue('false');
        $option->setDisplayText('Rename');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_number_of_courses');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_number_of_courses');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_empty_course_categories');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_empty_course_categories');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_back_link_on_top_of_tree');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_back_link_on_top_of_tree');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_different_course_language');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_different_course_language');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('split_users_upload_directory');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('split_users_upload_directory');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('hide_dltt_markup');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('hide_dltt_markup');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('display_categories_on_homepage');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('display_categories_on_homepage');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('default_forum_view');
        $option->setValue('flat');
        $option->setDisplayText('Flat');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('default_forum_view');
        $option->setValue('threaded');
        $option->setDisplayText('Threaded');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('default_forum_view');
        $option->setValue('nested');
        $option->setDisplayText('Nested');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('survey_email_sender_noreply');
        $option->setValue('coach');
        $option->setDisplayText('CourseCoachEmailSender');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('survey_email_sender_noreply');
        $option->setValue('noreply');
        $option->setDisplayText('NoReplyEmailSender');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('openid_authentication');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('openid_authentication');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('gradebook_enable');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('gradebook_enable');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('user_selected_theme');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('user_selected_theme');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_course_theme');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_course_theme');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('display_mini_month_calendar');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('display_mini_month_calendar');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('display_upcoming_events');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('display_upcoming_events');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_closed_courses');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_closed_courses');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('ldap_version');
        $option->setValue('2');
        $option->setDisplayText('LDAPVersion2');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('ldap_version');
        $option->setValue('3');
        $option->setDisplayText('LDAPVersion3');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('visio_use_rtmpt');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('visio_use_rtmpt');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('add_users_by_coach');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('add_users_by_coach');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('extend_rights_for_coach');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('extend_rights_for_coach');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('extend_rights_for_coach_on_survey');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('extend_rights_for_coach_on_survey');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_session_coach');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_session_coach');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_users_to_create_courses');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_users_to_create_courses');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('breadcrumbs_course_homepage');
        $option->setValue('session_name_and_course_title');
        $option->setDisplayText('SessionNameAndCourseTitle');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('advanced_filemanager');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('advanced_filemanager');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_reservation');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_reservation');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_message_tool');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_message_tool');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_social_tool');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_social_tool');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_students_to_browse_courses');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_students_to_browse_courses');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_email_of_teacher_or_tutor ');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_email_of_teacher_or_tutor ');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_session_data ');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_session_data ');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_use_sub_language');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_use_sub_language');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_glossary_in_documents');
        $option->setValue('none');
        $option->setDisplayText('ShowGlossaryInDocumentsIsNone');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_glossary_in_documents');
        $option->setValue('ismanual');
        $option->setDisplayText('ShowGlossaryInDocumentsIsManual');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_glossary_in_documents');
        $option->setValue('isautomatic');
        $option->setDisplayText('ShowGlossaryInDocumentsIsAutomatic');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_terms_conditions');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_terms_conditions');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('search_enabled');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('search_enabled');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('search_show_unlinked_results');
        $option->setValue('true');
        $option->setDisplayText('SearchShowUnlinkedResults');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('search_show_unlinked_results');
        $option->setValue('false');
        $option->setDisplayText('SearchHideUnlinkedResults');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_courses_descriptions_in_catalog');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_courses_descriptions_in_catalog');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_coach_to_edit_course_session');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_coach_to_edit_course_session');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        /*$option = new SettingsOptions();
        $option->setVariable('show_glossary_in_extra_tools');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);*/


        /*$option = new SettingsOptions();
        $option->setVariable('show_glossary_in_extra_tools');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);*/


        $option = new SettingsOptions();
        $option->setVariable('send_email_to_admin_when_create_course');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('send_email_to_admin_when_create_course');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('go_to_course_after_login');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('go_to_course_after_login');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('math_mimetex');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('math_mimetex');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('math_asciimathML');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('math_asciimathML');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_asciisvg');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_asciisvg');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('include_asciimathml_script');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('include_asciimathml_script');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('youtube_for_students');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('youtube_for_students');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('block_copy_paste_for_students');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('block_copy_paste_for_students');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('more_buttons_maximized_mode');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('more_buttons_maximized_mode');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('students_download_folders');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('students_download_folders');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('users_copy_files');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('users_copy_files');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_students_to_create_groups_in_social');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_students_to_create_groups_in_social');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_send_message_to_all_platform_users');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_send_message_to_all_platform_users');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('use_users_timezone');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('use_users_timezone');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_user_course_subscription_by_course_admin');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_user_course_subscription_by_course_admin');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_link_bug_notification');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_link_bug_notification');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('course_validation');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('course_validation');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('sso_authentication');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('sso_authentication');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('sso_authentication_protocol');
        $option->setValue('http://');
        $option->setDisplayText('http://');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('sso_authentication_protocol');
        $option->setValue('https://');
        $option->setDisplayText('https://');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_wiris');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_wiris');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_spellcheck');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_spellcheck');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('force_wiki_paste_as_plain_text');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('force_wiki_paste_as_plain_text');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_googlemaps');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_googlemaps');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_imgmap');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_imgmap');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_support_svg');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_support_svg');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('pdf_export_watermark_enable');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('pdf_export_watermark_enable');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('pdf_export_watermark_by_course');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('pdf_export_watermark_by_course');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_insertHtml');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_insertHtml');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('students_export2pdf');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('students_export2pdf');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_users_folders');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_users_folders');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_default_folders');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_default_folders');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_chat_folder');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_chat_folder');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_text2audio');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_text2audio');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_support_pixlr');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enabled_support_pixlr');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_groups_to_users');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_groups_to_users');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('accessibility_font_resize');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('accessibility_font_resize');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('hide_courses_in_sessions');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('hide_courses_in_sessions');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_quiz_scenario');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_quiz_scenario');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_nanogong');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_nanogong');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_documents_preview');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_documents_preview');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('htmlpurifier_wiki');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('htmlpurifier_wiki');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('cas_activate');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('cas_activate');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('cas_protocol');
        $option->setValue('CAS1');
        $option->setDisplayText('CAS1Text');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('cas_protocol');
        $option->setValue('CAS2');
        $option->setDisplayText('CAS2Text');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('cas_protocol');
        $option->setValue('SAML');
        $option->setDisplayText('SAMLText');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('cas_add_user_activate');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('cas_add_user_activate');
        $option->setValue('platform');
        $option->setDisplayText('casAddUserActivatePlatform');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('cas_add_user_activate');
        $option->setValue('extldap');
        $option->setDisplayText('casAddUserActivateLDAP');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('update_user_info_cas_with_ldap');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('update_user_info_cas_with_ldap');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('scorm_cumulative_session_time');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('scorm_cumulative_session_time');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_hr_skills_management');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_hr_skills_management');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_help_link');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_help_link');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_users_to_change_email_with_no_password');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_users_to_change_email_with_no_password');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_admin_toolbar');
        $option->setValue('do_not_show');
        $option->setDisplayText('DoNotShow');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_admin_toolbar');
        $option->setValue('show_to_admin');
        $option->setDisplayText('ShowToAdminsOnly');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_admin_toolbar');
        $option->setValue('show_to_admin_and_teachers');
        $option->setDisplayText('ShowToAdminsAndTeachers');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_admin_toolbar');
        $option->setValue('show_to_all');
        $option->setDisplayText('ShowToAllUsers');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('use_custom_pages');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('use_custom_pages');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority1');
        $option->setValue('platform_lang');
        $option->setDisplayText('PlatformLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority1');
        $option->setValue('user_profil_lang');
        $option->setDisplayText('UserLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority1');
        $option->setValue('user_selected_lang');
        $option->setDisplayText('UserSelectedLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority1');
        $option->setValue('course_lang');
        $option->setDisplayText('CourseLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority2');
        $option->setValue('platform_lang');
        $option->setDisplayText('PlatformLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority2');
        $option->setValue('user_profil_lang');
        $option->setDisplayText('UserLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority2');
        $option->setValue('user_selected_lang');
        $option->setDisplayText('UserSelectedLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority2');
        $option->setValue('course_lang');
        $option->setDisplayText('CourseLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority3');
        $option->setValue('platform_lang');
        $option->setDisplayText('PlatformLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority3');
        $option->setValue('user_profil_lang');
        $option->setDisplayText('UserLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority3');
        $option->setValue('user_selected_lang');
        $option->setDisplayText('UserSelectedLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority3');
        $option->setValue('course_lang');
        $option->setDisplayText('CourseLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority4');
        $option->setValue('platform_lang');
        $option->setDisplayText('PlatformLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority4');
        $option->setValue('user_profil_lang');
        $option->setDisplayText('UserLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority4');
        $option->setValue('user_selected_lang');
        $option->setDisplayText('UserSelectedLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('languagePriority4');
        $option->setValue('course_lang');
        $option->setDisplayText('CourseLanguage');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_global_chat');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_global_chat');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('login_is_email');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('login_is_email');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('courses_default_creation_visibility');
        $option->setValue('3');
        $option->setDisplayText('OpenToTheWorld');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('courses_default_creation_visibility');
        $option->setValue('2');
        $option->setDisplayText('OpenToThePlatform');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('courses_default_creation_visibility');
        $option->setValue('1');
        $option->setDisplayText('Private');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('courses_default_creation_visibility');
        $option->setValue('0');
        $option->setDisplayText('CourseVisibilityClosed');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_browser_sniffer');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_browser_sniffer');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_wami_record');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_wami_record');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('teachers_can_change_score_settings');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('teachers_can_change_score_settings');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);

        $option = new SettingsOptions();
        $option->setVariable('gradebook_locking_enabled');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('gradebook_locking_enabled');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('gradebook_enable_grade_model');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('gradebook_enable_grade_model');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_session_admins_to_manage_all_sessions');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_session_admins_to_manage_all_sessions');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_skills_tool');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_skills_tool');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_public_certificates');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_public_certificates');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('platform_unsubscribe_allowed');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('platform_unsubscribe_allowed');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('activate_email_template');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('activate_email_template');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_iframe_inclusion');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_iframe_inclusion');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_hot_courses');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('show_hot_courses');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_webcam_clip');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('enable_webcam_clip');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('session_tutor_reports_visibility');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('session_tutor_reports_visibility');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('gradebook_show_percentage_in_reports');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('gradebook_show_percentage_in_reports');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('session_page_enabled');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('session_page_enabled');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_teachers_to_create_sessions');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_teachers_to_create_sessions');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('use_virtual_keyboard');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('use_virtual_keyboard');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('disable_copy_paste');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('disable_copy_paste');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('login_as_allowed');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('login_as_allowed');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('admins_can_set_users_pass');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('admins_can_set_users_pass');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('breadcrumb_navigation_display');
        $option->setValue('true');
        $option->setDisplayText('Show');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('breadcrumb_navigation_display');
        $option->setValue('false');
        $option->setDisplayText('Hide');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('default_calendar_view');
        $option->setValue('month');
        $option->setDisplayText('Month');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('default_calendar_view');
        $option->setValue('basicWeek');
        $option->setDisplayText('BasicWeek');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('default_calendar_view');
        $option->setValue('agendaWeek');
        $option->setDisplayText('Week');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('default_calendar_view');
        $option->setValue('agendaDay');
        $option->setDisplayText('Day');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('documents_default_visibility_defined_in_course');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('documents_default_visibility_defined_in_course');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_personal_user_files');
        $option->setValue('true');
        $option->setDisplayText('Yes');
        $manager->persist($option);


        $option = new SettingsOptions();
        $option->setVariable('allow_personal_user_files');
        $option->setValue('false');
        $option->setDisplayText('No');
        $manager->persist($option);

        $manager->flush();
    }


    /**
     * @param $manager
     */
    private function generateSettingsCode($manager)
    {
        $variables = array(
            'Variable',
            'Subkey',
            'Type',
            'Category',
            'SelectedValue',
            'Title',
            'Comment',
            'Scope',
            'Subkeytext',
            'AccessUrlChangeable'
        );

        echo $this->generateCode($manager, 'SettingsCurrent', 'setting', $variables);

        $variables = array(
            'Variable',
            'Value',
            'DisplayText'
        );
        echo $this->generateCode($manager, 'SettingsOptions', 'option', $variables);

    }

    /**
     * @return \FOS\UserBundle\Model\UserManagerInterface
     */
    public function getManager()
    {
        return $this->container->get('doctrine')->getManager();
    }

    /**
     * @return \Faker\Generator
     */
    public function getFaker()
    {
        return $this->container->get('faker.generator');
    }
}
