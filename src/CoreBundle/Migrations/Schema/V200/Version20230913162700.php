<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20230913162700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Replace old document path by resource file path';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $em = $this->getEntityManager();

        /** @var Connection $connection */
        $connection = $em->getConnection();
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        /** @var CDocumentRepository $documentRepo */
        $documentRepo = $container->get(CDocumentRepository::class);

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $courseDirectory = $course->getDirectory();

            if (empty($courseDirectory)) {
                continue;
            }

            // Tool intro
            $sql = "SELECT * FROM c_tool_intro WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalIntroText = $itemData['intro_text'];
                    if (!empty($originalIntroText)) {
                        $updatedIntroText = $this->replaceOldURLsWithNew($originalIntroText, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalIntroText !== $updatedIntroText) {
                            $sql = "UPDATE c_tool_intro SET intro_text = :newIntroText WHERE iid = :introId";
                            $params = [
                                'newIntroText' => $updatedIntroText,
                                'introId' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Course description
            $sql = "SELECT * FROM c_course_description WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalContent = $itemData['content'];
                    if (!empty($originalContent)) {
                        $updatedContent = $this->replaceOldURLsWithNew($originalContent, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalContent !== $updatedContent) {
                            $sql = "UPDATE c_course_description SET content = :newContent WHERE iid = :id";
                            $params = [
                                'newContent' => $updatedContent,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Quiz
            $sql = "SELECT * FROM c_quiz WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalDescription = $itemData['description'];
                    if (!empty($originalDescription)) {
                        $updatedDescription = $this->replaceOldURLsWithNew($originalDescription, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalDescription !== $updatedDescription) {
                            $sql = "UPDATE c_quiz SET description = :newDescription WHERE iid = :id";
                            $params = [
                                'newDescription' => $updatedDescription,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }

                    $originalText = $itemData['text_when_finished'];
                    if (!empty($originalText)) {
                        $updatedText = $this->replaceOldURLsWithNew($originalText, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalText !== $updatedText) {
                            $sql = "UPDATE c_quiz SET text_when_finished = :newText WHERE iid = :id";
                            $params = [
                                'newText' => $updatedText,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Quiz question
            $sql = "SELECT * FROM c_quiz_question WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalDescription = $itemData['description'];
                    if (!empty($originalDescription)) {
                        $updatedDescription = $this->replaceOldURLsWithNew($originalDescription, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalDescription !== $updatedDescription) {
                            $sql = "UPDATE c_quiz_question SET description = :newDescription WHERE iid = :id";
                            $params = [
                                'newDescription' => $updatedDescription,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }

                    $originalQuestion = $itemData['question'];
                    if (!empty($originalQuestion)) {
                        $updatedQuestion = $this->replaceOldURLsWithNew($originalQuestion, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalQuestion !== $updatedQuestion) {
                            $sql = "UPDATE c_quiz_question SET question = :newQuestion WHERE iid = :id";
                            $params = [
                                'newQuestion' => $updatedQuestion,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Quiz answer
            $sql = "SELECT * FROM c_quiz_answer WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalAnswer = $itemData['answer'];
                    if (!empty($originalAnswer)) {
                        $updatedAnswer = $this->replaceOldURLsWithNew($originalAnswer, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalAnswer !== $updatedAnswer) {
                            $sql = "UPDATE c_quiz_answer SET answer = :newAnswer WHERE iid = :id";
                            $params = [
                                'newAnswer' => $updatedAnswer,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }

                    $originalComment = $itemData['comment'];
                    if (!empty($originalComment)) {
                        $updatedComment = $this->replaceOldURLsWithNew($originalComment, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalComment !== $updatedComment) {
                            $sql = "UPDATE c_quiz_answer SET comment = :newComment WHERE iid = :id";
                            $params = [
                                'newComment' => $updatedComment,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Student publication
            $sql = "SELECT * FROM c_student_publication WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalWorkDescription = $itemData['description'];
                    if (!empty($originalWorkDescription)) {
                        $updatedWorkDescription = $this->replaceOldURLsWithNew($originalWorkDescription, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalWorkDescription !== $updatedWorkDescription) {
                            $sql = "UPDATE c_student_publication SET description = :newDescription WHERE iid = :id";
                            $params = [
                                'newDescription' => $updatedWorkDescription,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Student publication comment
            $sql = "SELECT * FROM c_student_publication_comment WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalWorkComment = $itemData['comment'];
                    if (!empty($originalWorkComment)) {
                        $updatedWorkComment = $this->replaceOldURLsWithNew($originalWorkComment, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalWorkComment !== $updatedWorkComment) {
                            $sql = "UPDATE c_student_publication_comment SET comment = :newComment WHERE iid = :id";
                            $params = [
                                'newComment' => $updatedWorkComment,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Forum category
            $sql = "SELECT * FROM c_forum_category WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalCatComment = $itemData['cat_comment'];
                    if (!empty($originalCatComment)) {
                        $updatedCatComment = $this->replaceOldURLsWithNew($originalCatComment, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalCatComment !== $updatedCatComment) {
                            $sql = "UPDATE c_forum_category SET cat_comment = :newComment WHERE iid = :id";
                            $params = [
                                'newComment' => $updatedCatComment,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Forum
            $sql = "SELECT * FROM c_forum_forum WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalForumComment = $itemData['forum_comment'];
                    if (!empty($originalForumComment)) {
                        $updatedForumComment = $this->replaceOldURLsWithNew($originalForumComment, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalForumComment !== $updatedForumComment) {
                            $sql = "UPDATE c_forum_forum SET forum_comment = :newComment WHERE iid = :id";
                            $params = [
                                'newComment' => $updatedForumComment,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Forum post
            $sql = "SELECT * FROM c_forum_post WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalPostText = $itemData['post_text'];
                    if (!empty($originalPostText)) {
                        $updatedPostText = $this->replaceOldURLsWithNew($originalPostText, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalPostText !== $updatedPostText) {
                            $sql = "UPDATE c_forum_post SET post_text = :newText WHERE iid = :id";
                            $params = [
                                'newText' => $updatedPostText,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Glossary
            $sql = "SELECT * FROM c_glossary WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalGlossaryDescription = $itemData['description'];
                    if (!empty($originalGlossaryDescription)) {
                        $updatedGlossaryDescription = $this->replaceOldURLsWithNew($originalGlossaryDescription, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalGlossaryDescription !== $updatedGlossaryDescription) {
                            $sql = "UPDATE c_glossary SET description = :newDescription WHERE iid = :id";
                            $params = [
                                'newDescription' => $updatedGlossaryDescription,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Survey
            $sql = "SELECT * FROM c_survey WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalSurveyTitle = $itemData['title'];
                    if (!empty($originalSurveyTitle)) {
                        $updatedSurveyTitle = $this->replaceOldURLsWithNew($originalSurveyTitle, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalSurveyTitle !== $updatedSurveyTitle) {
                            $sql = "UPDATE c_survey SET title = :newTitle WHERE iid = :id";
                            $params = [
                                'newTitle' => $updatedSurveyTitle,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }

                    $originalSurveySubTitle = $itemData['subtitle'];
                    if (!empty($originalSurveySubTitle)) {
                        $updatedSurveySubTitle = $this->replaceOldURLsWithNew($originalSurveySubTitle, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalSurveySubTitle !== $updatedSurveySubTitle) {
                            $sql = "UPDATE c_survey SET subtitle = :newSubtitle WHERE iid = :id";
                            $params = [
                                'newSubtitle' => $updatedSurveySubTitle,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Survey question
            $sql = "SELECT * FROM c_survey_question WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalSurveyQuestion = $itemData['survey_question'];
                    if (!empty($originalSurveyQuestion)) {
                        $updatedSurveyQuestion = $this->replaceOldURLsWithNew($originalSurveyQuestion, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalSurveyQuestion !== $updatedSurveyQuestion) {
                            $sql = "UPDATE c_survey_question SET survey_question = :newQuestion WHERE iid = :id";
                            $params = [
                                'newQuestion' => $updatedSurveyQuestion,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }

                    $originalSurveyQuestionComment = $itemData['survey_question_comment'];
                    if (!empty($originalSurveyQuestionComment)) {
                        $updatedSurveyQuestionComment = $this->replaceOldURLsWithNew($originalSurveyQuestionComment, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalSurveyQuestionComment !== $updatedSurveyQuestionComment) {
                            $sql = "UPDATE c_survey_question SET survey_question_comment = :newComment WHERE iid = :id";
                            $params = [
                                'newComment' => $updatedSurveyQuestionComment,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

            // Survey question option
            $sql = "SELECT * FROM c_survey_question_option WHERE c_id = {$courseId} ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $originalOptionText = $itemData['option_text'];
                    if (!empty($originalOptionText)) {
                        $updatedOptionText = $this->replaceOldURLsWithNew($originalOptionText, $courseDirectory, $courseId, $connection, $documentRepo);
                        if ($originalOptionText !== $updatedOptionText) {
                            $sql = "UPDATE c_survey_question_option SET option_text = :newText WHERE iid = :id";
                            $params = [
                                'newText' => $updatedOptionText,
                                'id' => $itemData['iid'],
                            ];
                            $connection->executeQuery($sql, $params);
                        }
                    }
                }
            }

        }
    }

    private function replaceOldURLsWithNew($itemDataText, $courseDirectory, $courseId, $connection, $documentRepo): array|string|null
    {
        $contentText = $itemDataText;

        $pattern = '/(src|href)=(["\'])(\/courses\/' . preg_quote($courseDirectory, '/') . '\/[^"\']+\.\w+)\2/i';
        preg_match_all($pattern, $contentText, $matches);
        $videosSrcPath = $matches[3];

        if (!empty($videosSrcPath)) {
            foreach ($videosSrcPath as $index => $videoPath) {
                $documentPath = str_replace('/courses/' . $courseDirectory . '/document/', '/', $videoPath);
                $sql = "SELECT iid, path, resource_node_id
                        FROM c_document
                        WHERE
                              c_id = $courseId AND
                              path LIKE '$documentPath'
                ";
                $result = $connection->executeQuery($sql);
                $documents = $result->fetchAllAssociative();

                if (!empty($documents)) {
                    foreach ($documents as $documentData) {
                        $resourceNodeId = (int) $documentData['resource_node_id'];
                        $documentFile = $documentRepo->getResourceFromResourceNode($resourceNodeId);
                        if ($documentFile) {
                            $newUrl = $documentRepo->getResourceFileUrl($documentFile);
                            if (!empty($newUrl)) {
                                $patternForReplacement = '/' . $matches[1][$index] . '=(["\'])' . preg_quote($videoPath, '/') . '\1/i';
                                $replacement = $matches[1][$index] . '=$1' . $newUrl . '$1';
                                $contentText = preg_replace($patternForReplacement, $replacement, $contentText);
                                error_log('$documentPath ->' . $documentPath);
                                error_log('newUrl ->' . $newUrl);
                            }
                        }
                    }
                }
            }
        }

        return $contentText; // Return the updated content text.
    }
}
