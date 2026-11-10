<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\Skill;

use Chamilo\CoreBundle\Entity\Level;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillLevelProfile;
use Chamilo\CoreBundle\State\Skill\SkillAssignmentLevelResolver;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class SkillAssignmentLevelResolverTest extends TestCase
{
    public function testCustomLevelNamesRemainUntranslated(): void
    {
        $profile = new SkillLevelProfile();
        $skill = new Skill();
        $skill->setLevelProfile($profile);

        $gold = $this->createMock(Level::class);
        $gold->method('getId')->willReturn(10);
        $gold->method('getTitle')->willReturn('Or');

        $silver = $this->createMock(Level::class);
        $silver->method('getId')->willReturn(11);
        $silver->method('getTitle')->willReturn('Argent');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['profile' => $profile], ['position' => 'ASC'])
            ->willReturn([$gold, $silver])
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Level::class)
            ->willReturn($repository)
        ;

        $resolver = new SkillAssignmentLevelResolver($entityManager);

        $this->assertSame(
            [
                ['label' => 'Or', 'value' => 10],
                ['label' => 'Argent', 'value' => 11],
            ],
            $resolver->getOptions($skill),
            'User-defined level titles must not be interpreted as translation keys.'
        );
    }
}
