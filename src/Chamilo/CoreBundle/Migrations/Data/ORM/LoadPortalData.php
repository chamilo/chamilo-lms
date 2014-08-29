<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Data\ORM;

use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Entity\CourseField;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\SystemTemplate;
use Chamilo\CoreBundle\Entity\UserFriendRelationType;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelSkill;
use Chamilo\CoreBundle\Entity\CourseType;
use Chamilo\CoreBundle\Entity\BranchSync;
use Chamilo\CoreBundle\Entity\BranchTransactionStatus;
use Chamilo\CoreBundle\Entity\Tool;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Finder\Finder;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Class LoadPortalData
 * @package Chamilo\CoreBundle\DataFixtures\ORM
 */
class LoadPortalData extends AbstractFixture implements
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
        return 5;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var HttpKernelInterface $kernel */
        $kernel = $this->container->get('kernel');

        $courseCategory = new CourseCategory();
        $courseCategory->setName('Language skills');
        $courseCategory->setCode('LANG');
        $courseCategory->setTreePos(1);
        $courseCategory->setAuthCatChild('TRUE');
        $courseCategory->setAuthCourseChild('TRUE');
        $manager->persist($courseCategory);

        $courseCategory = new CourseCategory();
        $courseCategory->setName('PC Skills');
        $courseCategory->setCode('PC');
        $courseCategory->setTreePos(2);
        $courseCategory->setAuthCatChild('TRUE');
        $courseCategory->setAuthCourseChild('TRUE');
        $manager->persist($courseCategory);

        $courseCategory = new CourseCategory();
        $courseCategory->setName('Projects');
        $courseCategory->setCode('PROJ');
        $courseCategory->setTreePos(3);
        $courseCategory->setAuthCatChild('TRUE');
        $courseCategory->setAuthCourseChild('TRUE');
        $manager->persist($courseCategory);

        $courseField = new CourseField();
        $courseField->setFieldType(13);
        $courseField->setFieldVariable('special_course');
        $courseField->setFieldDisplayText('Special course');
        $courseField->setFieldDefaultValue('Yes');
        $courseField->setFieldVisible(1);
        $courseField->setFieldChangeable(1);
        $manager->persist($courseField);

        /*
            Saving available languages depending in
            the ChamiloCoreBundle/Resources/translations folder
        */
        $languages = Intl::getLocaleBundle()->getLocaleNames('en');

        // Getting po files inside the path
        $translationPath = $kernel->locateResource('@ChamiloCoreBundle/Resources/translations');

        $finder = new Finder();
        $finder->files()->in($translationPath);
        $avoidIsoCodeList = array('AvanzuAdminTheme.en.po');
        $availableIsoCode = array();
        foreach ($finder as $file) {
            $fileName = $file->getRelativePathname();
            if (in_array($fileName, $avoidIsoCodeList)) {
                continue;
            }
            $isoCodeInFolder = str_replace(
                array('all.', '.po'), '', $fileName
            );
            $availableIsoCode[] = $isoCodeInFolder;
        }

        foreach ($languages as $code => $languageName) {
            if (!in_array($code, $availableIsoCode)) {
                continue;
            }

            \Locale::setDefault($code);
            $localeName = Intl::getLocaleBundle()->getLocaleName($code);

            $lang = new Language();
            $lang->setAvailable(1);
            $lang->setIsocode($code);
            $lang->setOriginalName($localeName);
            $lang->setEnglishName($languageName);
            $manager->persist($lang);
        }

        $adminUser = $this->getUserManager()->findUserByUsername('admin');

        // Ids used
        $adminUserId = $adminUser->getId();
        $accessUrlId = 1;

        $accessUrl = new AccessUrl();
        $accessUrl->setUrl('http://localhost/');
        $accessUrl->setActive(1);
        $accessUrl->setDescription(' ');
        $accessUrl->setCreatedBy($adminUserId);
        $manager->persist($accessUrl);

        $accessUrlRelUser = new AccessUrlRelUser();
        $accessUrlRelUser->setUserId($adminUserId);
        $accessUrlRelUser->setAccessUrlId($accessUrlId);
        $manager->persist($accessUrlRelUser);

        /*$systemTemplate = new SystemTemplate();
        $systemTemplate->setTitle('');
        $systemTemplate->setComment('');
        $systemTemplate->setImage('');
        $systemTemplate->setContent('');*/

        $userFriendRelationType = new UserFriendRelationType();
        $userFriendRelationType->setId(1);
        $userFriendRelationType->setTitle('SocialUnknow');
        $manager->persist($userFriendRelationType);

        $userFriendRelationType = new UserFriendRelationType();
        $userFriendRelationType->setId(2);
        $userFriendRelationType->setTitle('SocialParent');
        $manager->persist($userFriendRelationType);

        $userFriendRelationType = new UserFriendRelationType();
        $userFriendRelationType->setId(3);
        $userFriendRelationType->setTitle('SocialFriend');
        $manager->persist($userFriendRelationType);

        $userFriendRelationType = new UserFriendRelationType();
        $userFriendRelationType->setId(4);
        $userFriendRelationType->setTitle('SocialGoodFriend');
        $manager->persist($userFriendRelationType);

        $userFriendRelationType = new UserFriendRelationType();
        $userFriendRelationType->setId(5);
        $userFriendRelationType->setTitle('SocialEnemy');
        $manager->persist($userFriendRelationType);

        $userFriendRelationType = new UserFriendRelationType();
        $userFriendRelationType->setId(6);
        $userFriendRelationType->setTitle('SocialDeleted');
        $manager->persist($userFriendRelationType);

        $skill = new Skill();
        $skill->setName('Root');
        $skill->setDescription(' ');
        $skill->setShortCode('root');
        $skill->setIcon(' ');
        $skill->setAccessUrlId($accessUrlId);
        $manager->persist($skill);

        $skillRelSkill = new SkillRelSkill();
        $skillRelSkill->setId(1);
        $skillRelSkill->setSkillId(1);
        $skillRelSkill->setParentId(0);
        $skillRelSkill->setRelationType(0);
        $skillRelSkill->setLevel(0);
        $manager->persist($skillRelSkill);

        $courseType = new CourseType();
        $courseType->setName('All Tools');
        $manager->persist($courseType);

        $courseType = new CourseType();
        $courseType->setName('Entry exam');
        $manager->persist($courseType);

        $branch = new BranchSync();
        $branch->setAccessUrlId($accessUrlId);
        $branch->setBranchName('Local');
        $branch->setBranchIp('127.0.0.1');
        $manager->persist($branch);

        $branchTransactionStatus = new BranchTransactionStatus();
        $branchTransactionStatus->setTitle('To be executed');
        $manager->persist($branchTransactionStatus);

        $branchTransactionStatus = new BranchTransactionStatus();
        $branchTransactionStatus->setTitle('Executed success');
        $manager->persist($branchTransactionStatus);

        $branchTransactionStatus = new BranchTransactionStatus();
        $branchTransactionStatus->setTitle('Execution deprecated');
        $manager->persist($branchTransactionStatus);

        $branchTransactionStatus = new BranchTransactionStatus();
        $branchTransactionStatus->setTitle('Execution failed');
        $manager->persist($branchTransactionStatus);

        $tool = new Tool();
        $tool->setName('agenda');
        $manager->persist($tool);

        $tool = new Tool();
        $tool->setName('announcements');
        $manager->persist($tool);

        $tool = new Tool();
        $tool->setName('exercise');
        $manager->persist($tool);

        $tool = new Tool();
        $tool->setName('document');
        $manager->persist($tool);

        $tool = new Tool();
        $tool->setName('link');
        $manager->persist($tool);

        $tool = new Tool();
        $tool->setName('forum');
        $manager->persist($tool);

        $tool = new Tool();
        $tool->setName('glossary');
        $manager->persist($tool);

        $manager->flush();
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

    /**
     * @return \FOS\UserBundle\Model\UserManagerInterface
     */
    public function getUserManager()
    {
        return $this->container->get('fos_user.user_manager');
    }

}
