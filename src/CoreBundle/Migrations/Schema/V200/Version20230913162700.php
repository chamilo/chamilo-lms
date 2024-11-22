<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Schema\Schema;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use const PHP_URL_PATH;

final class Version20230913162700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Replace old document path by resource file path';
    }

    public function up(Schema $schema): void
    {
        $documentRepo = $this->container->get(CDocumentRepository::class);
        $resourceNodeRepo = $this->container->get(ResourceNodeRepository::class);

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        $updateConfigurations = [
            ['table' => 'c_tool_intro', 'field' => 'intro_text'],
            ['table' => 'c_course_description', 'field' => 'content'],
            ['table' => 'c_quiz', 'fields' => ['description', 'text_when_finished']],
            ['table' => 'c_quiz_question', 'fields' => ['description', 'question']],
            ['table' => 'c_quiz_answer', 'fields' => ['answer', 'comment']],
            ['table' => 'c_course_description', 'field' => 'content'],
            ['table' => 'c_student_publication', 'field' => 'description'],
            ['table' => 'c_student_publication_comment', 'field' => 'comment'],
            ['table' => 'c_forum_category', 'field' => 'cat_comment'],
            ['table' => 'c_forum_forum', 'field' => 'forum_comment'],
            ['table' => 'c_forum_post', 'field' => 'post_text'],
            ['table' => 'c_glossary', 'field' => 'description'],
            ['table' => 'c_survey', 'fields' => ['title', 'subtitle']],
            ['table' => 'c_survey_question', 'fields' => ['survey_question', 'survey_question_comment']],
            ['table' => 'c_survey_question_option', 'field' => 'option_text'],
        ];

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $courseDirectory = $course->getDirectory();

            if (empty($courseDirectory)) {
                continue;
            }

            foreach ($updateConfigurations as $config) {
                $this->updateContent($config, $courseDirectory, $courseId, $documentRepo);
            }

            $this->updateHtmlContent($courseDirectory, $courseId, $documentRepo, $resourceNodeRepo);
        }
    }


    private function updateContent($config, $courseDirectory, $courseId, $documentRepo): void
    {
        if (isset($config['field'])) {
            $fields = [$config['field']];
        } elseif (isset($config['fields'])) {
            $fields = $config['fields'];
        } else {
            throw new Exception('No field or fields specified for updating.');
        }

        foreach ($fields as $field) {
            $sql = "SELECT iid, {$field} FROM {$config['table']} WHERE c_id = {$courseId}";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();

            foreach ($items as $item) {
                $originalText = $item[$field];
                if (!empty($originalText)) {
                    $updatedText = $this->replaceOldURLsWithNew($originalText, $courseDirectory, $courseId, $documentRepo);
                    if ($originalText !== $updatedText) {
                        error_log("SIMULACIÓN: Actualizar campo {$field} en {$config['table']} para ID {$item['iid']}");
                        // Simular sin guardar cambios
                        // $sql = "UPDATE {$config['table']} SET {$field} = :newText WHERE iid = :id";
                        // $params = ['newText' => $updatedText, 'id' => $item['iid']];
                        // $this->connection->executeQuery($sql, $params);
                    }
                }
            }
        }
    }

    private function updateHtmlContent($courseDirectory, $courseId, $documentRepo, $resourceNodeRepo): void
    {
        $sql = "SELECT iid, resource_node_id FROM c_document WHERE filetype = 'file' AND resource_node_id IS NOT NULL";
        $result = $this->connection->executeQuery($sql);
        $items = $result->fetchAllAssociative();

        foreach ($items as $item) {
            /** @var CDocument $document */
            $document = $documentRepo->find($item['iid']);
            if (!$document) {
                continue;
            }

            $resourceNode = $document->getResourceNode();

            if (!$resourceNode || !$resourceNode->hasResourceFile()) {
                continue;
            }

            $resourceFile = $resourceNode->getResourceFiles()->first();

            if (!$resourceFile) {
                continue;
            }

            $filePath = $resourceFile->getTitle();
            if ('text/html' === $resourceFile->getMimeType()) {
                error_log("Verificando archivo HTML: $filePath");

                try {
                    $content = $resourceNodeRepo->getResourceNodeFileContent($resourceNode);
                    $updatedContent = $this->replaceOldURLsWithNew($content, $courseDirectory, $courseId, $documentRepo);

                    if ($content !== $updatedContent) {
                        error_log("SIMULACIÓN: Actualizar contenido HTML en {$filePath}");
                        // Simular sin guardar cambios
                        // $documentRepo->updateResourceFileContent($document, $updatedContent);
                        // $documentRepo->update($document);
                    }
                } catch (Exception $e) {
                    error_log("Error al procesar archivo $filePath: " . $e->getMessage());
                }
            }
        }
    }

    private function replaceOldURLsWithNew($itemDataText, $courseDirectory, $courseId, $documentRepo): array|string|null
    {
        $contentText = $itemDataText;
        $specificCoursePattern = '/(src|href)=["\']((https?:\/\/[^\/]+)?(\/courses\/([^\/]+)\/document\/[^"\']+\.\w+))["\']/i';
        preg_match_all($specificCoursePattern, $contentText, $matches);

        foreach ($matches[2] as $index => $fullUrl) {
            $videoPath = parse_url($fullUrl, PHP_URL_PATH) ?: $fullUrl;
            $actualCourseDirectory = $matches[5][$index];
            if ($actualCourseDirectory !== $courseDirectory) {
                $videoPath = preg_replace("/^\\/courses\\/$actualCourseDirectory\\//i", "/courses/$courseDirectory/", $videoPath);
            }

            $documentPath = str_replace('/courses/'.$courseDirectory.'/document/', '/', $videoPath);

            $sql = "SELECT iid, path, resource_node_id FROM c_document WHERE c_id = $courseId AND path LIKE '$documentPath'";
            $result = $this->connection->executeQuery($sql);
            $documents = $result->fetchAllAssociative();

            if (!empty($documents)) {
                $this->replaceDocumentLinks($documents, $documentRepo, $matches, $index, $videoPath, $courseId, $contentText);
            } else {
                $document = $this->createNewDocument($videoPath, $courseId);
                if ($document) {
                    $newUrl = $documentRepo->getResourceFileUrl($document);
                    if ($newUrl) {
                        $replacement = $matches[1][$index].'="'.$newUrl.'"';
                        $contentText = str_replace($matches[0][$index], $replacement, $contentText);
                    }
                }
            }
        }

        return $contentText;
    }

    private function replaceDocumentLinks($documents, $documentRepo, $matches, $index, $videoPath, $courseId, &$contentText): void
    {
        foreach ($documents as $documentData) {
            $resourceNodeId = (int) $documentData['resource_node_id'];
            $documentFile = $documentRepo->getResourceFromResourceNode($resourceNodeId);
            if ($documentFile) {
                $newUrl = $documentRepo->getResourceFileUrl($documentFile);
                if (!empty($newUrl)) {
                    $patternForReplacement = '/'.preg_quote($matches[0][$index], '/').'/';
                    $replacement = $matches[1][$index].'="'.$newUrl.'"';
                    $contentText = preg_replace($patternForReplacement, $replacement, $contentText, 1);
                }
            }
        }
    }

    private function createNewDocument($videoPath, $courseId)
    {
        try {
            $documentRepo = $this->container->get(CDocumentRepository::class);
            $kernel = $this->container->get('kernel');
            $rootPath = $kernel->getProjectDir();
            $appCourseOldPath = $rootPath . '/app' . $videoPath;
            $title = basename($appCourseOldPath);

            $courseRepo = $this->container->get(CourseRepository::class);
            $course = $courseRepo->find($courseId);
            if (!$course) {
                throw new Exception("Course with ID $courseId not found.");
            }

            error_log("SIMULACIÓN: Intentando localizar archivo {$title} en la ruta esperada: {$appCourseOldPath}");

            // Verificar si el archivo existe en la ubicación esperada
            if (file_exists($appCourseOldPath) && !is_dir($appCourseOldPath)) {
                error_log("SIMULACIÓN: Archivo encontrado en ruta esperada: {$appCourseOldPath}");
                return null; // Retorna null en modo de solo lectura
            }

            // Buscar en directorios alternativos usando recursiveFileSearch
            $generalCoursesPath = $this->getUpdateRootPath() . '/app/courses/';
            $foundPath = $this->recursiveFileSearch($generalCoursesPath, $title);
            if ($foundPath) {
                // Registrar el valor de foundPath sin realizar ninguna operación de escritura
                error_log("SIMULACIÓN: Archivo encontrado en nueva ubicación: {$foundPath}");
            } else {
                error_log("SIMULACIÓN: Archivo {$title} no encontrado en {$generalCoursesPath}");
            }

            return null; // No realizar ninguna operación de creación
        } catch (Exception $e) {
            error_log('Error en la migración: ' . $e->getMessage());
            return null;
        }
    }


    private function recursiveFileSearch($directory, $title)
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $title) {
                return $file->getRealPath();
            }
        }

        return null;
    }
}
