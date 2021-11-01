<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Level;
use Chamilo\CoreBundle\Entity\Profile;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillProfile;
use Chamilo\CoreBundle\Entity\SkillRelCourse;
use Chamilo\CoreBundle\Entity\SkillRelGradebook;
use Chamilo\CoreBundle\Entity\SkillRelItem;
use Chamilo\CoreBundle\Entity\SkillRelProfile;
use Chamilo\CoreBundle\Entity\SkillRelSkill;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\SkillRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class SkillRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreateSkill(): void
    {
        $skillRepo = self::getContainer()->get(SkillRepository::class);
        $accessUrl = $this->getAccessUrl();

        $skill = (new Skill())
            ->setName('php')
            ->setShortCode('php')
            ->setDescription('desc')
            ->setStatus(Skill::STATUS_ENABLED)
            ->setCriteria('criteria')
            ->setIcon('icon')
            ->setAccessUrlId($accessUrl->getId())
            ->setUpdatedAt(new DateTime())
        ;

        $this->assertHasNoEntityViolations($skill);
        $skillRepo->update($skill);

        // By default, there's 1 root skill + this newly skill created.
        $this->assertSame(2, $skillRepo->count([]));
        $this->assertSame('php', (string) $skill);
        $this->assertSame('desc', $skill->getDescription());
        $this->assertSame($accessUrl->getId(), $skill->getAccessUrlId());
        $this->assertSame(0, $skill->getItems()->count());
        $this->assertSame(0, $skill->getCourses()->count());
        $this->assertSame(0, $skill->getIssuedSkills()->count());
        $this->assertSame(0, $skill->getGradeBookCategories()->count());
        $this->assertSame(0, $skill->getSkills()->count());
    }

    public function testCreateWithProfile(): void
    {
        $em = $this->getEntityManager();
        $skillRepo = self::getContainer()->get(SkillRepository::class);
        $profileRepo = $em->getRepository(Profile::class);

        $course = $this->createCourse('new');
        $session = $this->createSession('session');

        $courseId = $course->getId();

        $accessUrl = $this->getAccessUrl();

        $skillProfile = (new SkillProfile())
            ->setDescription('desc')
            ->setName('title')
        ;
        $em->persist($skillProfile);

        $profile = (new Profile())
            ->setName('profile')
        ;
        $em->persist($profile);

        $level = (new Level())
            ->setName('level')
            ->setPosition(1)
            ->setProfile($profile)
            ->setShortName('level')
        ;
        $em->persist($level);


        $skill = (new Skill())
            ->setName('Dev')
            ->setShortCode('Dev')
            ->setStatus(Skill::STATUS_ENABLED)
            ->setAccessUrlId($accessUrl->getId())
            ->setProfile($profile)
        ;
        $skillRepo->update($skill);

        $subSkill = (new Skill())
            ->setName('php')
            ->setShortCode('php')
            ->setStatus(Skill::STATUS_ENABLED)
            ->setAccessUrlId($accessUrl->getId())
        ;
        $skillRepo->update($subSkill);

        $skillRelSkill = (new SkillRelSkill())
            ->setLevel(1)
            ->setRelationType(1)
            ->setParent($skill)
            ->setSkill($subSkill)
        ;
        $em->persist($skillRelSkill);

        $skillRelProfile = (new SkillRelProfile())
            ->setProfile($skillProfile)
            ->setSkill($skill)
        ;
        $em->persist($skillRelProfile);

        $skillRelItem = (new SkillRelItem())
            ->setItemId(1)
            ->setUpdatedAt(new DateTime())
            ->setCreatedAt(new DateTime())
            ->setCourseId($courseId)
            ->setIsReal(true)
            ->setObtainConditions('obtain')
            ->setCreatedBy(1)
            ->setItemType(1)
            ->setSkill($skill)
            ->setUpdatedBy(1)
        ;
        $skill->addItem($skillRelItem);

        $skillRelCourse = (new SkillRelCourse())
            ->setSkill($skill)
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime())
            ->setCourse($course)
            ->setSession($session)
        ;
        $em->persist($skillRelCourse);
        $skill->addToCourse($skillRelCourse);

        $gradeBookCategory = (new GradebookCategory())
            ->setName('title')
            ->setCourse($course)
            ->setVisible(true)
            ->setWeight(100)
        ;
        $em->persist($gradeBookCategory);

        $skillRelGradeBook = (new SkillRelGradebook())
            ->setSkill($skill)
            ->setType('type')
            ->setGradeBookCategory($gradeBookCategory)
        ;

        $em->persist($skillRelGradeBook);
        $em->persist($skillRelItem);
        $em->flush();
        $em->clear();

        /** @var Profile $profile */
        $profile = $profileRepo->find($profile->getId());

        /** @var Skill $skill */
        $skill = $skillRepo->find($skill->getId());

        /** @var Skill $subSkill */
        $subSkill = $skillRepo->find($subSkill->getId());

        // By default, there's 1 root skill + this newly skill created.
        $this->assertSame(3, $skillRepo->count([]));
        $this->assertTrue($skill->hasItem(1, 1));
        $this->assertFalse($skill->hasItem(1, 99));
        $this->assertNotNull($skill->getId());
        $this->assertSame(1, $skill->getCourses()->count());
        $this->assertSame(1, $skill->getItems()->count());
        $this->assertSame(1, $subSkill->getSkills()->count());

        $this->assertTrue($skill->hasCourseAndSession($skillRelCourse));

        $this->assertSame('profile', $profile->getName());
        $this->assertSame('profile', (string) $profile);
        $this->assertSame(1, $profile->getLevels()->count());
        $this->assertSame(1, $profile->getSkills()->count());

        $skillRepo->delete($skill);

        $this->assertSame(2, $skillRepo->count([]));
    }

    public function testGetLastByUser(): void
    {
        $skillRepo = self::getContainer()->get(SkillRepository::class);
        $settingsManager = self::getContainer()->get(SettingsManager::class);
        $settingsManager->updateSetting('skill.badge_assignation_notification', 'true');

        $accessUrl = $this->getAccessUrl();

        $skill = (new Skill())
            ->setName('php')
            ->setShortCode('php')
            ->setDescription('desc')
            ->setStatus(Skill::STATUS_ENABLED)
            ->setCriteria('criteria')
            ->setIcon('icon')
            ->setAccessUrlId($accessUrl->getId())
        ;
        $skillRepo->update($skill);

        $course = $this->createCourse('new');
        $session = $this->createSession('new');
        $user = $this->createUser('test');

        $em = $this->getEntityManager();

        $skillRelUser = (new SkillRelUser())
            ->setSkill($skill)
            ->setCourse($course)
            ->setSession($session)
            ->setUser($user)
            ->setArgumentation('argumentation')
            ->setAssignedBy(1)
            ->setArgumentationAuthorId(1)
        ;
        $this->assertHasNoEntityViolations($skillRelUser);
        $em->persist($skillRelUser);
        $em->flush();

        $skill = $skillRepo->getLastByUser($user, $course, $session);
        $this->assertNotNull($skill);
    }

    public function testDeleteSkill(): void
    {
        $skillRepo = self::getContainer()->get(SkillRepository::class);
        $accessUrl = $this->getAccessUrl();

        // Root skill.
        $this->assertSame(1, $skillRepo->count([]));

        $skill = (new Skill())
            ->setName('php')
            ->setShortCode('php')
            ->setAccessUrlId($accessUrl->getId())
        ;
        $skillRepo->update($skill);

        $this->assertSame(2, $skillRepo->count([]));

        $skillRepo->delete($skill);

        $this->assertSame(1, $skillRepo->count([]));
    }

    public function testCreateSkillWithAsset(): void
    {
        $em = $this->getEntityManager();

        $skillRepo = self::getContainer()->get(SkillRepository::class);
        $assetRepo = self::getContainer()->get(AssetRepository::class);

        $file = $this->getUploadedFile();
        $accessUrl = $this->getAccessUrl();

        // Create skill.
        $skill = (new Skill())
            ->setName('php')
            ->setShortCode('php')
            ->setAccessUrlId($accessUrl->getId())
        ;

        $this->assertHasNoEntityViolations($skill);

        // Create asset.
        $asset = (new Asset())
            ->setTitle($skill->getName())
            ->setCategory(Asset::SKILL)
            ->setFile($file)
        ;
        $em->persist($asset);

        $skill->setAsset($asset);

        $skillRepo->update($skill);

        // Root + php skills
        $this->assertSame(2, $skillRepo->count([]));
        // 1 asset
        $this->assertSame(1, $assetRepo->count([]));
        // Asset has an URL
        $this->assertNotEmpty($assetRepo->getAssetUrl($asset));
    }

    public function testDeleteSkillWithAsset(): void
    {
        $em = $this->getEntityManager();

        $skillRepo = self::getContainer()->get(SkillRepository::class);
        $assetRepo = self::getContainer()->get(AssetRepository::class);

        $file = $this->getUploadedFile();
        $accessUrl = $this->getAccessUrl();

        $skill = (new Skill())
            ->setName('php')
            ->setShortCode('php')
            ->setAccessUrlId($accessUrl->getId())
        ;

        $this->assertHasNoEntityViolations($skill);

        $asset = (new Asset())
            ->setTitle($skill->getName())
            ->setCategory(Asset::SKILL)
            ->setFile($file)
        ;
        $em->persist($asset);

        $skill->setAsset($asset);
        $skillRepo->update($skill);

        $this->assertNotEmpty($assetRepo->getAssetUrl($asset));
        $this->assertSame(1, $skillRepo->count(['name' => 'php']));
        $this->assertSame(1, $assetRepo->count([]));

        // Remove asset from skill
        $skillRepo->deleteAsset($skill);

        // Asset removed:
        $this->assertSame(0, $assetRepo->count([]));

        // Skill exists.
        $this->assertSame(1, $skillRepo->count(['name' => 'php']));

        $em->clear();

        // Check skill
        /** @var Skill $skill */
        $skill = $skillRepo->find($skill->getId());
        $this->assertFalse($skill->hasAsset());

        // Delete skill
        $skillRepo->delete($skill);

        // Only root skill exists.
        $this->assertSame(1, $skillRepo->count([]));
    }
}
