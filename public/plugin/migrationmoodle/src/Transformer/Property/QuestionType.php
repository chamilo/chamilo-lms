<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;
use Doctrine\DBAL\DBALException;

/**
 * Class QuestionType.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class QuestionType implements TransformPropertyInterface
{
    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public function transform(array $data)
    {
        list($qtype, $id) = array_values($data);

        switch ($qtype) {
            case 'multichoice':
                if (!$this->isMultiChoiceSingle($id)) {
                    return MULTIPLE_ANSWER;
                }
                // no break
            case 'truefalse':
                return UNIQUE_ANSWER;
            case 'match':
                return MATCHING_DRAGGABLE;
            case 'shortanswer':
            case 'numerical':
            case 'gapselect':
                return FILL_IN_BLANKS;
            case 'essay':
                return FREE_ANSWER;
            case 'calculated':
                return CALCULATED_ANSWER;
            case 'calculatedmulti':
            case 'calculatedsimple':
            case 'ddwtos':
            case 'ddmarker':
            case 'ddimageortext':
            case 'multianswer':
            case 'randomsamatch':
            case 'description':
                throw new \Exception("Question type \"$qtype\" not supported in question \"$id\".");
        }
    }

    /**
     * @param int $id
     *
     * @throws \Exception
     *
     * @return bool
     */
    private function isMultiChoiceSingle($id)
    {
        try {
            $connection = \MigrationMoodlePlugin::create()->getConnection();
        } catch (DBALException $e) {
            throw new \Exception('Unable to start connection.', 0, $e);
        }

        $query = "SELECT single FROM mdl_qtype_multichoice_options WHERE questionid = ?";

        try {
            $result = $connection->fetchAssoc($query, [$id]);
        } catch (DBALException $e) {
            throw new \Exception("Unable to execute query \"$query\".", 0, $e);
        }

        $connection->close();

        if (false === $result || !empty($result['single'])) {
            return true;
        }

        return false;
    }
}
