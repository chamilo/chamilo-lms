<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\DataFixtures\SkillLevelFixtures;
use PHPUnit\Framework\TestCase;

final class SkillLevelFixturesTest extends TestCase
{
    public function testStandardProfilesAndLevelsMatchIssueData(): void
    {
        $profiles = SkillLevelFixtures::getProfiles();

        $this->assertCount(4, $profiles);
        $this->assertSame(
            [
                'Language skills - CEFR',
                'Dreyfus model',
                "Bloom's taxonomy",
                'Skills Framework for the Information Age',
            ],
            array_column($profiles, 'title')
        );

        $levels = array_merge(...array_column($profiles, 'levels'));
        $this->assertCount(24, $levels);

        $dreyfus = $profiles[1]['levels'];
        $this->assertSame('Advanced Beginner', $dreyfus[1]['title']);
        $this->assertSame(2, $dreyfus[1]['position']);
        $this->assertSame('Competent', $dreyfus[2]['title']);
        $this->assertSame(1, $dreyfus[2]['position']);

        $sfia = $profiles[3]['levels'];
        $this->assertSame('Set strategy, inspire, mobilise', $sfia[6]['title']);
        $this->assertNotSame('', $sfia[6]['description']);
    }
}
