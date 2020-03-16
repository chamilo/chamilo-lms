<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;
use Doctrine\DBAL\DBALException;

/**
 * Class UserQuestionAnswerTruefalse.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class UserQuestionAnswerTruefalse implements TransformPropertyInterface
{
    /**
     * @inheritDoc
     */
    public function transform(array $data)
    {
        list(
            $mQType,
            $mRightAnswer,
            $mResponseSummary,
            $mFraction,
            $mDefaultMark,
            $mQuestionSummary,
            $mQuestionId
        ) = array_values($data);

        try {
            $connection = \MigrationMoodlePlugin::create()->getConnection();
        } catch (DBALException $exception) {
            throw new \Exception('Unable to start connection.', 0, $exception);
        }

        try {
            $sql = "SELECT id
                FROM mdl_question_answers
                WHERE question = ? and answer = ?";

            $result = $connection->fetchAssoc($sql, [$mQuestionId, $mResponseSummary]);
        } catch (DBALException $exception) {
            throw new \Exception("Unable to execute query \"{$this->query}\".", 0, $exception);
        }

        $connection->close();

        return $result['id'];
    }
}
