<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\SequenceCondition;
use Chamilo\CoreBundle\Entity\SequenceFormula;
use Chamilo\CoreBundle\Entity\SequenceMethod;
use Chamilo\CoreBundle\Entity\SequenceRule;
use Chamilo\CoreBundle\Entity\SequenceRuleCondition;
use Chamilo\CoreBundle\Entity\SequenceRuleMethod;
use Chamilo\CoreBundle\Entity\SequenceTypeEntity;
use Chamilo\CoreBundle\Entity\SequenceValid;
use Chamilo\CoreBundle\Entity\SequenceVariable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SequenceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $sequenceRule = (new SequenceRule())
            ->setDescription(
                'If user completes 70% of an entity or group of items, he will be able to access another entity or group of items'
            )
        ;
        $manager->persist($sequenceRule);

        $sequenceCondition1 = (new SequenceCondition())
            ->setDescription('<= 100%')
            ->setMathOperation('<=')
            ->setParam('100.0')
            ->setActTrue('2')
            ->setActFalse('0')
        ;
        $manager->persist($sequenceCondition1);

        $sequenceCondition2 = (new SequenceCondition())
            ->setDescription('>= 70%')
            ->setMathOperation('>=')
            ->setParam('70.0')
            ->setActTrue('0')
            ->setActFalse('')
        ;
        $manager->persist($sequenceCondition2);

        $sequenceRuleCondition1 = (new SequenceRuleCondition())
            ->setCondition($sequenceCondition1)
            ->setRule($sequenceRule)
        ;
        $manager->persist($sequenceRuleCondition1);

        $sequenceRuleCondition2 = (new SequenceRuleCondition())
            ->setCondition($sequenceCondition2)
            ->setRule($sequenceRule)
        ;
        $manager->persist($sequenceRuleCondition2);

        $list = [
            [
                'description' => 'Add completed item',
                'formula' => 'v#2 + $complete_items;',
                'assign' => 2,
                'met_type' => 'add',
                'act_false' => '',
            ],
            [
                'description' => 'Update progress by division',
                'formula' => 'v#2 / v#3 * 100;',
                'assign' => 1,
                'met_type' => 'div',
                'act_false' => '',
            ],
            [
                'description' => 'Add completed item',
                'formula' => 'v#2 + $complete_items;',
                'assign' => 2,
                'met_type' => 'add',
                'act_false' => '',
            ],
            [
                'description' => 'Update items count',
                'formula' => '$total_items;',
                'assign' => 3,
                'met_type' => 'update',
                'act_false' => '',
            ],
            [
                'description' => 'Enable success',
                'formula' => '1;',
                'assign' => 4,
                'met_type' => 'success',
                'act_false' => '',
            ],
            [
                'description' => 'Store success date',
                'formula' => '(empty(v#5))? api_get_utc_datetime() : v#5;',
                'assign' => 5,
                'met_type' => 'success',
                'act_false' => '',
            ],
            [
                'description' => 'Enable availability',
                'formula' => '1;',
                'assign' => 6,
                'met_type' => 'pre',
                'act_false' => '',
            ],
            [
                'description' => 'Store availability start date',
                'formula' => '(empty(v#7))? api_get_utc_datetime() : v#7;',
                'assign' => 7,
                'met_type' => 'pre',
                'act_false' => '',
            ],
            [
                'description' => 'Store availability end date',
                'formula' => '(empty($available_end_date))? api_get_utc_datetime($available_end_date) : "0000-00-00 00:00:00";',
                'assign' => 8,
                'met_type' => 'pre',
                'act_false' => '',
            ],
            [
                'description' => 'Increase the items count',
                'formula' => 'v#3 + $total_items;',
                'assign' => 3,
                'met_type' => 'add',
                'act_false' => '',
            ],
            [
                'description' => 'Update completed items',
                'formula' => '$complete_items;',
                'assign' => 2,
                'met_type' => 'update',
                'act_false' => '',
            ],
            [
                'description' => 'Update progress',
                'formula' => '$complete_items / $total_items * 100;',
                'assign' => 1,
                'met_type' => 'update',
                'act_false' => '',
            ],
        ];
        $methods = [];
        foreach ($list as $key => $item) {
            $sequenceMethod = (new SequenceMethod())
                ->setDescription($item['description'])
                ->setFormula($item['formula'])
                ->setAssign((string) $item['assign'])
                ->setMetType($item['met_type'])
                ->setActFalse($item['act_false'])
            ;
            $manager->persist($sequenceMethod);

            $methods[] = $sequenceMethod;

            $sequenceRuleMethod = (new SequenceRuleMethod())
                ->setRule($sequenceRule)
                ->setMethod($sequenceMethod)
                ->setMethodOrder((string) ($key + 1))
            ;
            $manager->persist($sequenceRuleMethod);
        }

        $list = [
            [
                'name' => 'Percentile progress',
                'description' => 'advance',
                'default_val' => '0.0',
            ],
            [
                'name' => 'Completed items',
                'description' => 'complete_items',
                'default_val' => '0',
            ],
            [
                'name' => 'Items count',
                'description' => 'total_items',
                'default_val' => '0',
            ],
            [
                'name' => 'Completed',
                'description' => 'success',
                'default_val' => '0',
            ],
            [
                'name' => 'Completion date',
                'description' => 'success_date',
                'default_val' => '0000-00-00 00:00:00',
            ],
            [
                'name' => 'Available',
                'description' => 'available',
                'default_val' => '0',
            ],
            [
                'name' => 'Availability start date',
                'description' => 'available_start_date',
                'default_val' => '0000-00-00 00:00:00',
            ],
            [
                'name' => 'Availability end date',
                'description' => 'available_end_date',
                'default_val' => '0000-00-00 00:00:00',
            ],
        ];

        $variables = [];
        foreach ($list as $item) {
            $sequenceVariable = (new SequenceVariable())
                ->setName($item['name'])
                ->setDescription($item['description'])
                ->setDefaultValue($item['default_val'])
            ;
            $manager->persist($sequenceVariable);
            $variables[] = $sequenceVariable;
        }

        $list = [
            [
                'method' => 1,
                'variable' => 2,
            ],
            [
                'method' => 2,
                'variable' => 2,
            ],
            [
                'method' => 2,
                'variable' => 3,
            ],
            [
                'method' => 2,
                'variable' => 1,
            ],
            [
                'method' => 3,
                'variable' => 3,
            ],
            [
                'method' => 4,
                'variable' => 4,
            ],
            [
                'method' => 5,
                'variable' => 5,
            ],
            [
                'method' => 6,
                'variable' => 6,
            ],
            [
                'method' => 7,
                'variable' => 7,
            ],
            [
                'method' => 8,
                'variable' => 8,
            ],
            [
                'method' => 9,
                'variable' => 3,
            ],
            [
                'method' => 10,
                'variable' => 2,
            ],
            [
                'method' => 11,
                'variable' => 1,
            ],
        ];

        foreach ($list as $item) {
            $sequenceFormula = (new SequenceFormula())
                ->setMethod($methods[$item['method'] - 1])
                ->setVariable($variables[$item['variable'] - 1])
            ;
            $manager->persist($sequenceFormula);
        }

        $sequenceValid = (new SequenceValid())
            ->setVariable($variables[0])
            ->setCondition($sequenceCondition1)
        ;
        $manager->persist($sequenceValid);

        $sequenceValid = (new SequenceValid())
            ->setVariable($variables[0])
            ->setCondition($sequenceCondition2)
        ;
        $manager->persist($sequenceValid);

        $list = [
            [
                'name' => 'Lp',
                'description' => 'Learning Path',
                'entity_table' => 'c_lp',
            ],
            [
                'name' => 'Quiz',
                'description' => 'Quiz and Tests',
                'entity_table' => 'c_quiz',
            ],
            [
                'name' => 'LpItem',
                'description' => 'Items of a Learning Path',
                'entity_table' => 'c_lp_item',
            ],
        ];

        foreach ($list as $item) {
            $sequenceType = (new SequenceTypeEntity())
                ->setName($item['name'])
                ->setDescription($item['description'])
                ->setEntityTable($item['entity_table'])
            ;
            $manager->persist($sequenceType);
        }

        $manager->flush();
    }
}
