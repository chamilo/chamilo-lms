<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\Level;
use Chamilo\CoreBundle\Entity\SkillLevelProfile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class SkillLevelFixtures extends Fixture
{
    /**
     * @return list<array{
     *     title: string,
     *     levels: list<array{title: string, position: int, shortTitle: string, description: string}>
     * }>
     */
    public static function getProfiles(): array
    {
        return [
            [
                'title' => 'Language skills - CEFR',
                'levels' => [
                    ['title' => 'A1 - Basic', 'position' => 0, 'shortTitle' => 'A1', 'description' => ''],
                    ['title' => 'A2 - Upper Basic', 'position' => 1, 'shortTitle' => 'A2', 'description' => '0-41 in TOEFL iBT scale'],
                    ['title' => 'B1 - Intermediate', 'position' => 2, 'shortTitle' => 'B1', 'description' => '42-71 in TOEFL iBT scale'],
                    ['title' => 'B2 - Upper Intermediate', 'position' => 3, 'shortTitle' => 'B2', 'description' => '72-94 in TOEFL iBT scale'],
                    ['title' => 'C1 - Advanced', 'position' => 4, 'shortTitle' => 'C1', 'description' => '95-113 in TOEFL iBT scale'],
                    ['title' => 'C2 - Mastery', 'position' => 5, 'shortTitle' => 'C2', 'description' => '114-120+ in TOEFL iBT scale'],
                ],
            ],
            [
                'title' => 'Dreyfus model',
                'levels' => [
                    ['title' => 'Novice', 'position' => 0, 'shortTitle' => 'NOVI', 'description' => 'Rigid adherence to rules and context-free instructions; little situational judgment.'],
                    ['title' => 'Advanced Beginner', 'position' => 2, 'shortTitle' => 'BEGI', 'description' => 'Starts recognizing situational aspects and patterns but still needs guidelines.'],
                    ['title' => 'Competent', 'position' => 1, 'shortTitle' => 'CMPT', 'description' => 'Develops planning and mental models; takes responsibility for outcomes but may feel overwhelmed by complexity.'],
                    ['title' => 'Proficient', 'position' => 3, 'shortTitle' => 'PROF', 'description' => 'Sees the "big picture," prioritizes intuitively, and learns from experience.'],
                    ['title' => 'Expert', 'position' => 4, 'shortTitle' => 'XPER', 'description' => 'Intuitive, fluid performance; relies on deep tacit knowledge; can innovate or handle novel situations effortlessly.'],
                ],
            ],
            [
                'title' => "Bloom's taxonomy",
                'levels' => [
                    ['title' => 'Remembering', 'position' => 0, 'shortTitle' => 'RMBG', 'description' => 'Retrieve, recall, or recognize relevant knowledge from long-term memory.'],
                    ['title' => 'Understanding', 'position' => 1, 'shortTitle' => 'NDRT', 'description' => 'Construct meaning from instructional messages, including oral, written, and graphic communication. Demonstrate comprehension by interpreting, exemplifying, classifying, summarizing, inferring, comparing, or explaining.'],
                    ['title' => 'Applying', 'position' => 2, 'shortTitle' => 'APLY', 'description' => 'Carry out or use a procedure through executing or implementing it in a given situation. Use knowledge or skills in new or concrete situations.'],
                    ['title' => 'Analyzing', 'position' => 3, 'shortTitle' => 'ANYZ', 'description' => 'Break material into its constituent parts and determine how the parts relate to one another and to an overall structure or purpose. Identify motives, causes, or relationships.'],
                    ['title' => 'Evaluating', 'position' => 4, 'shortTitle' => 'EVAL', 'description' => 'Make judgments based on criteria and standards. Check, critique, or justify decisions or courses of action.'],
                    ['title' => 'Creating', 'position' => 5, 'shortTitle' => 'CREA', 'description' => 'Put elements together to form a coherent or functional whole; reorganize elements into a new pattern or structure. Produce original work or generate new ideas.'],
                ],
            ],
            [
                'title' => 'Skills Framework for the Information Age',
                'levels' => [
                    ['title' => 'Follow', 'position' => 0, 'shortTitle' => 'FOLW', 'description' => 'Works under close supervision and direction. Performs routine tasks with limited discretion. Focuses on following instructions and basic execution.'],
                    ['title' => 'Assist', 'position' => 1, 'shortTitle' => 'ASIS', 'description' => 'Works under routine supervision. Provides assistance to others, contributes to team tasks, and begins to take some initiative within defined boundaries.'],
                    ['title' => 'Apply', 'position' => 2, 'shortTitle' => 'APLY', 'description' => 'Works under general supervision. Applies knowledge and skills to a range of routine and non-routine activities. Exercises some discretion and judgment.'],
                    ['title' => 'Enable', 'position' => 3, 'shortTitle' => 'NABL', 'description' => 'Works under broad direction. Enables others by providing specialist guidance, support, or services. Takes responsibility for own work and may supervise small teams or tasks.'],
                    ['title' => 'Ensure and advise', 'position' => 4, 'shortTitle' => 'NSUR', 'description' => 'Works under broad direction but with significant autonomy. Ensures that work meets objectives and advises on complex matters. Often leads teams or projects and influences decisions.'],
                    ['title' => 'Initiate and influence', 'position' => 5, 'shortTitle' => 'INFL', 'description' => 'Has defined authority and responsibility for a significant area of work. Initiates actions, influences strategy and policy in their domain, and leads substantial projects or teams.'],
                    ['title' => 'Set strategy, inspire, mobilise', 'position' => 6, 'shortTitle' => 'INSP', 'description' => 'Holds authority at the highest organisational level for a major area of work. Sets long-term strategy, inspires and mobilises others, and makes decisions critical to organisational success. Influences at industry or external levels.'],
                ],
            ],
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $profileRepository = $manager->getRepository(SkillLevelProfile::class);
        $levelRepository = $manager->getRepository(Level::class);

        foreach (self::getProfiles() as $profileData) {
            $profile = $profileRepository->findOneBy(['title' => $profileData['title']]);
            if (!$profile instanceof SkillLevelProfile) {
                $profile = (new SkillLevelProfile())->setTitle($profileData['title']);
                $manager->persist($profile);
                $manager->flush();
            }

            foreach ($profileData['levels'] as $levelData) {
                $level = $levelRepository->findOneBy([
                    'profile' => $profile,
                    'shortTitle' => $levelData['shortTitle'],
                ]);

                if (!$level instanceof Level) {
                    $level = new Level();
                }

                $level
                    ->setTitle($levelData['title'])
                    ->setPosition($levelData['position'])
                    ->setShortTitle($levelData['shortTitle'])
                    ->setDescription($levelData['description'])
                    ->setProfile($profile)
                ;
                $manager->persist($level);
            }
        }

        $manager->flush();
    }
}
