<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const PREG_NO_ERROR;

final class Version20231022124700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Replace old cidReq url path by new version';
    }

    public function up(Schema $schema): void
    {
        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();

            // Tool intro
            $sql = "SELECT * FROM c_tool_intro WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalIntroText = $itemData['intro_text'];
                    if (!empty($originalIntroText)) {
                        $updatedIntroText = $this->replaceURLParametersInContent($originalIntroText);
                        if ($originalIntroText !== $updatedIntroText) {
                            $sql = 'UPDATE c_tool_intro SET intro_text = :newIntroText WHERE iid = :introId';
                            $params = [
                                'newIntroText' => $updatedIntroText,
                                'introId' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_tool_intro  cid ='.$courseId);
                        }
                    }
                }
            }

            // Course description
            $sql = "SELECT * FROM c_course_description WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalContent = $itemData['content'];
                    if (!empty($originalContent)) {
                        $updatedContent = $this->replaceURLParametersInContent($originalContent);
                        if ($originalContent !== $updatedContent) {
                            $sql = 'UPDATE c_course_description SET content = :newContent WHERE iid = :id';
                            $params = [
                                'newContent' => $updatedContent,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_course_description  cid ='.$courseId);
                        }
                    }
                }
            }

            // Quiz
            $sql = "SELECT * FROM c_quiz WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalDescription = $itemData['description'];
                    if (!empty($originalDescription)) {
                        $updatedDescription = $this->replaceURLParametersInContent($originalDescription);
                        if ($originalDescription !== $updatedDescription) {
                            $sql = 'UPDATE c_quiz SET description = :newDescription WHERE iid = :id';
                            $params = [
                                'newDescription' => $updatedDescription,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_quiz  cid ='.$courseId);
                        }
                    }

                    $originalText = $itemData['text_when_finished'];
                    if (!empty($originalText)) {
                        $updatedText = $this->replaceURLParametersInContent($originalText);
                        if ($originalText !== $updatedText) {
                            $sql = 'UPDATE c_quiz SET text_when_finished = :newText WHERE iid = :id';
                            $params = [
                                'newText' => $updatedText,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_quiz text_when_finished  cid ='.$courseId);
                        }
                    }
                }
            }

            // Quiz question
            $sql = "SELECT * FROM c_quiz_question WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalDescription = $itemData['description'];
                    if (!empty($originalDescription)) {
                        $updatedDescription = $this->replaceURLParametersInContent($originalDescription);
                        if ($originalDescription !== $updatedDescription) {
                            $sql = 'UPDATE c_quiz_question SET description = :newDescription WHERE iid = :id';
                            $params = [
                                'newDescription' => $updatedDescription,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_quiz_question  cid ='.$courseId);
                        }
                    }

                    $originalQuestion = $itemData['question'];
                    if (!empty($originalQuestion)) {
                        $updatedQuestion = $this->replaceURLParametersInContent($originalQuestion);
                        if ($originalQuestion !== $updatedQuestion) {
                            $sql = 'UPDATE c_quiz_question SET question = :newQuestion WHERE iid = :id';
                            $params = [
                                'newQuestion' => $updatedQuestion,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_quiz_question question cid ='.$courseId);
                        }
                    }
                }
            }

            // Quiz answer
            $sql = "SELECT * FROM c_quiz_answer WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalAnswer = $itemData['answer'];
                    if (!empty($originalAnswer)) {
                        $updatedAnswer = $this->replaceURLParametersInContent($originalAnswer);
                        if ($originalAnswer !== $updatedAnswer) {
                            $sql = 'UPDATE c_quiz_answer SET answer = :newAnswer WHERE iid = :id';
                            $params = [
                                'newAnswer' => $updatedAnswer,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_quiz_answer cid ='.$courseId);
                        }
                    }

                    $originalComment = $itemData['comment'];
                    if (!empty($originalComment)) {
                        $updatedComment = $this->replaceURLParametersInContent($originalComment);
                        if ($originalComment !== $updatedComment) {
                            $sql = 'UPDATE c_quiz_answer SET comment = :newComment WHERE iid = :id';
                            $params = [
                                'newComment' => $updatedComment,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_quiz_answer comment cid ='.$courseId);
                        }
                    }
                }
            }

            // Student publication
            $sql = "SELECT * FROM c_student_publication WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalWorkDescription = $itemData['description'];
                    if (!empty($originalWorkDescription)) {
                        $updatedWorkDescription = $this->replaceURLParametersInContent($originalWorkDescription);
                        if ($originalWorkDescription !== $updatedWorkDescription) {
                            $sql = 'UPDATE c_student_publication SET description = :newDescription WHERE iid = :id';
                            $params = [
                                'newDescription' => $updatedWorkDescription,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_student_publication cid ='.$courseId);
                        }
                    }
                }
            }

            // Student publication comment
            $sql = "SELECT * FROM c_student_publication_comment WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalWorkComment = $itemData['comment'];
                    if (!empty($originalWorkComment)) {
                        $updatedWorkComment = $this->replaceURLParametersInContent($originalWorkComment);
                        if ($originalWorkComment !== $updatedWorkComment) {
                            $sql = 'UPDATE c_student_publication_comment SET comment = :newComment WHERE iid = :id';
                            $params = [
                                'newComment' => $updatedWorkComment,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_student_publication_comment cid ='.$courseId);
                        }
                    }
                }
            }

            // Forum category
            $sql = "SELECT * FROM c_forum_category WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalCatComment = $itemData['cat_comment'];
                    if (!empty($originalCatComment)) {
                        $updatedCatComment = $this->replaceURLParametersInContent($originalCatComment);
                        if ($originalCatComment !== $updatedCatComment) {
                            $sql = 'UPDATE c_forum_category SET cat_comment = :newComment WHERE iid = :id';
                            $params = [
                                'newComment' => $updatedCatComment,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_forum_category cid ='.$courseId);
                        }
                    }
                }
            }

            // Forum
            $sql = "SELECT * FROM c_forum_forum WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalForumComment = $itemData['forum_comment'];
                    if (!empty($originalForumComment)) {
                        $updatedForumComment = $this->replaceURLParametersInContent($originalForumComment);
                        if ($originalForumComment !== $updatedForumComment) {
                            $sql = 'UPDATE c_forum_forum SET forum_comment = :newComment WHERE iid = :id';
                            $params = [
                                'newComment' => $updatedForumComment,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_forum_forum cid ='.$courseId);
                        }
                    }
                }
            }

            // Forum post
            $sql = "SELECT * FROM c_forum_post WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalPostText = $itemData['post_text'];
                    if (!empty($originalPostText)) {
                        $updatedPostText = $this->replaceURLParametersInContent($originalPostText);
                        if ($originalPostText !== $updatedPostText) {
                            $sql = 'UPDATE c_forum_post SET post_text = :newText WHERE iid = :id';
                            $params = [
                                'newText' => $updatedPostText,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_forum_post cid ='.$courseId);
                        }
                    }
                }
            }

            // Glossary
            $sql = "SELECT * FROM c_glossary WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalGlossaryDescription = $itemData['description'];
                    if (!empty($originalGlossaryDescription)) {
                        $updatedGlossaryDescription = $this->replaceURLParametersInContent($originalGlossaryDescription);
                        if ($originalGlossaryDescription !== $updatedGlossaryDescription) {
                            $sql = 'UPDATE c_glossary SET description = :newDescription WHERE iid = :id';
                            $params = [
                                'newDescription' => $updatedGlossaryDescription,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_glossary cid ='.$courseId);
                        }
                    }
                }
            }

            // Survey
            $sql = "SELECT * FROM c_survey WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalSurveyTitle = $itemData['title'];
                    if (!empty($originalSurveyTitle)) {
                        $updatedSurveyTitle = $this->replaceURLParametersInContent($originalSurveyTitle);
                        if ($originalSurveyTitle !== $updatedSurveyTitle) {
                            $sql = 'UPDATE c_survey SET title = :newTitle WHERE iid = :id';
                            $params = [
                                'newTitle' => $updatedSurveyTitle,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_survey cid ='.$courseId);
                        }
                    }

                    $originalSurveySubTitle = $itemData['subtitle'];
                    if (!empty($originalSurveySubTitle)) {
                        $updatedSurveySubTitle = $this->replaceURLParametersInContent($originalSurveySubTitle);
                        if ($originalSurveySubTitle !== $updatedSurveySubTitle) {
                            $sql = 'UPDATE c_survey SET subtitle = :newSubtitle WHERE iid = :id';
                            $params = [
                                'newSubtitle' => $updatedSurveySubTitle,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_survey subtitle cid ='.$courseId);
                        }
                    }
                }
            }

            // Survey question
            $sql = "SELECT * FROM c_survey_question WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalSurveyQuestion = $itemData['survey_question'];
                    if (!empty($originalSurveyQuestion)) {
                        $updatedSurveyQuestion = $this->replaceURLParametersInContent($originalSurveyQuestion);
                        if ($originalSurveyQuestion !== $updatedSurveyQuestion) {
                            $sql = 'UPDATE c_survey_question SET survey_question = :newQuestion WHERE iid = :id';
                            $params = [
                                'newQuestion' => $updatedSurveyQuestion,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_survey_question cid ='.$courseId);
                        }
                    }

                    $originalSurveyQuestionComment = $itemData['survey_question_comment'];
                    if (!empty($originalSurveyQuestionComment)) {
                        $updatedSurveyQuestionComment = $this->replaceURLParametersInContent($originalSurveyQuestionComment);
                        if ($originalSurveyQuestionComment !== $updatedSurveyQuestionComment) {
                            $sql = 'UPDATE c_survey_question SET survey_question_comment = :newComment WHERE iid = :id';
                            $params = [
                                'newComment' => $updatedSurveyQuestionComment,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_survey_question survey_question_comment cid ='.$courseId);
                        }
                    }
                }
            }

            // Survey question option
            $sql = "SELECT * FROM c_survey_question_option WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalOptionText = $itemData['option_text'];
                    if (!empty($originalOptionText)) {
                        $updatedOptionText = $this->replaceURLParametersInContent($originalOptionText);
                        if ($originalOptionText !== $updatedOptionText) {
                            $sql = 'UPDATE c_survey_question_option SET option_text = :newText WHERE iid = :id';
                            $params = [
                                'newText' => $updatedOptionText,
                                'id' => $itemData['iid'],
                            ];
                            $this->connection->executeQuery($sql, $params);
                            error_log('Updated c_survey_question_option cid ='.$courseId);
                        }
                    }
                }
            }
        }
    }

    private function replaceURLParametersInContent($htmlContent)
    {
        $pattern = '/((https?:\/\/[^\/\s]*|)\/[^?\s]+?)\?cidReq=([a-zA-Z0-9_]+)(&(?:amp;)?id_session=([0-9]+))(&(?:amp;)?gidReq=([0-9]+))([^"\s]*)/i';

        // Replace URLs with a callback function.
        $newContent = @preg_replace_callback(
            $pattern,
            function ($matches) {
                $code = $matches[3]; // The 'code' is extracted from the captured URL.

                $courseId = null;
                $sql = 'SELECT id FROM course WHERE code = :code ORDER BY id DESC LIMIT 1';
                $stmt = $this->connection->executeQuery($sql, ['code' => $code]);
                $course = $stmt->fetch();

                if ($course) {
                    $courseId = $course['id'];
                }

                if (null === $courseId) {
                    return $matches[0]; // Complete original URL.
                }

                // Processing the remaining part of the URL.
                $remainingParams = '';
                if (!empty($matches[8])) {
                    $remainingParams = $matches[8];
                    if ('&' !== $remainingParams[0]) {
                        $remainingParams = '&'.$remainingParams;
                    }
                }

                // Reconstructing the URL with the new courseId and adjusted parameters.
                return $matches[1].'?cid='.$courseId.'&sid='.$matches[5].'&gid='.$matches[7].$remainingParams;
                // Return the new URL.
            },
            $htmlContent
        );

        if (PREG_NO_ERROR !== preg_last_error()) {
            error_log('Error encountered in preg_replace_callback: '.preg_last_error());
        }

        return $newContent;
    }
}
