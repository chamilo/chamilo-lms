<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\SkillRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class SkillRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreateSkill(): void
    {
        self::bootKernel();

        $skillRepo = self::getContainer()->get(SkillRepository::class);

        $accessUrl = $this->getAccessUrl();

        $skill = (new Skill())
            ->setName('php')
            ->setShortCode('php')
            ->setAccessUrlId($accessUrl->getId())
        ;

        $this->assertHasNoEntityViolations($skill);
        $skillRepo->update($skill);

        // By default, there's 1 root skill + this newwly skill created.
        $this->assertSame(2, $skillRepo->count([]));
    }

    public function testDeleteSkill(): void
    {
        self::bootKernel();

        $skillRepo = self::getContainer()->get(SkillRepository::class);
        $accessUrl = $this->getAccessUrl();

        $skill = (new Skill())
            ->setName('php')
            ->setShortCode('php')
            ->setAccessUrlId($accessUrl->getId())
        ;
        $skillRepo->update($skill);

        $skillRepo->delete($skill);

        $this->assertSame(1, $skillRepo->count([]));
    }

    public function testCreateSkillWithAsset(): void
    {
        self::bootKernel();

        $em = $this->getManager();

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
        self::bootKernel();

        $em = $this->getManager();

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
