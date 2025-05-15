<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class UserQuestionAnswer.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class UserQuestionAnswer implements TransformPropertyInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(array $data)
    {
        list($mQType) = array_values($data);

        $userQuestionAnswer = null;

        switch ($mQType) {
            case 'shortanswer':
                $userQuestionAnswer = new UserQuestionAnswerShortanswer();
                break;
            case 'gapselect':
                $userQuestionAnswer = new UserQuestionAnswerGapselect();
                break;
            case 'truefalse':
                $userQuestionAnswer = new UserQuestionAnswerTruefalse();
                break;
            default:
                return '';
        }

        return $userQuestionAnswer->transform($data);
    }
}
