<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Framework\Container;
use Database;
use Doctrine\ORM\EntityManagerInterface;
use SkillRelGradebookModel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SkillRelGradebookModelTest extends KernelTestCase
{
    public function testLinkingGradebooksSuppliesTheRequiredTypeInStrictMode(): void
    {
        self::bootKernel();
        Container::setContainer(self::getContainer());
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $connection = $em->getConnection();
        Database::setManager($em);
        Database::setConnection($connection);

        $skill = (new Skill())
            ->setTitle('Strict mode skill')
            ->setShortCode('strict')
            ->setAccessUrlId(1)
        ;
        $gradebook = (new GradebookCategory())
            ->setTitle('Strict mode gradebook')
            ->setVisible(true)
            ->setWeight(100)
        ;
        $em->persist($skill);
        $em->persist($gradebook);
        $em->flush();

        $sqlMode = $connection->fetchOne('SELECT @@SESSION.sql_mode');
        $connection->executeStatement("SET SESSION sql_mode = 'STRICT_TRANS_TABLES'");

        try {
            $model = new SkillRelGradebookModel();
            $model->updateGradeBookListBySkill($skill->getId(), [$gradebook->getId()]);

            $relation = $model->getSkillInfo($skill->getId(), $gradebook->getId());
            self::assertNotEmpty($relation);
            self::assertSame('', $relation['type']);

            $model->updateGradeBookListBySkill($skill->getId(), [$gradebook->getId()]);
            self::assertCount(1, $model->find('all', ['where' => ['skill_id = ?' => [$skill->getId()]]]));

            $model->updateGradeBookListBySkill($skill->getId(), []);
            self::assertSame([], $model->getSkillInfo($skill->getId(), $gradebook->getId()));

            $model->updateGradeBookListBySkill($skill->getId(), [$gradebook->getId()], false);
            self::assertSame([], $model->getSkillInfo($skill->getId(), $gradebook->getId()));
        } finally {
            $connection->executeStatement('SET SESSION sql_mode = ?', [$sqlMode]);
        }
    }
}
