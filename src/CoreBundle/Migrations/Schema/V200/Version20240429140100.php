<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Exception;

final class Version20240429140100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove unnecessary cid parameters from URLs';
    }

    public function up(Schema $schema): void
    {
        $this->connection->beginTransaction();
        try {
            $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
            /** @var Course $course */
            foreach ($q->toIterable() as $course) {
                $courseId = $course->getId();
                $this->processTextFields('c_tool_intro', 'intro_text');
                $this->processTextFields('c_course_description', 'content');
                $this->processTextFields('c_quiz', 'description');
                $this->processTextFields('c_quiz', 'text_when_finished');
                $this->processTextFields('c_quiz_question', 'description');
                $this->processTextFields('c_quiz_question', 'question');
                $this->processTextFields('c_quiz_answer', 'answer');
                $this->processTextFields('c_quiz_answer', 'comment');
                $this->processTextFields('c_student_publication', 'description');
                $this->processTextFields('c_student_publication_comment', 'comment');
                $this->processTextFields('c_forum_category', 'cat_comment');
                $this->processTextFields('c_forum_forum', 'forum_comment');
                $this->processTextFields('c_forum_post', 'post_text');
                $this->processTextFields('c_glossary', 'description');
                $this->processTextFields('c_survey', 'title');
                $this->processTextFields('c_survey_question', 'survey_question');
                $this->processTextFields('c_survey_question_option', 'option_text');
            }
            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    private function processTextFields(string $tableName, string $fieldName)
    {
        $sql = "SELECT iid, {$fieldName} FROM {$tableName}";
        $result = $this->connection->executeQuery($sql);
        $items = $result->fetchAllAssociative();

        foreach ($items as $item) {
            $originalText = $item[$fieldName];
            if ($originalText === null) continue;

            $updatedText = $this->removeCidParameter($originalText);

            if ($originalText !== $updatedText) {
                $sqlUpdate = "UPDATE {$tableName} SET {$fieldName} = :updatedText WHERE iid = :iid";
                $this->connection->executeQuery($sqlUpdate, ['updatedText' => $updatedText, 'iid' => $item['iid']]);
                error_log($tableName, $item['iid'], $originalText, $updatedText);
            }
        }
    }

    private function removeCidParameter(?string $text): string
    {
        if ($text === null) return '';

        $pattern = '/(\/r\/document\/files\/[\w-]+\/view)(\?|\&)(cid=\d+)/';

        $text = preg_replace_callback(
            $pattern,
            function ($matches) {
                $url = $matches[1];
                $queryDelimiter = $matches[2];
                $newQuery = str_replace($matches[3], '', $queryDelimiter);
                $newQuery = trim($newQuery, '?&');
                return $url . ($newQuery ? '?' . $newQuery : '');
            },
            $text
        );

        $text = str_replace('?&', '?', $text);
        $text = str_replace('&&', '&', $text);
        $text = rtrim($text, '?');
        $text = rtrim($text, '&');

        return $text;
    }
}
