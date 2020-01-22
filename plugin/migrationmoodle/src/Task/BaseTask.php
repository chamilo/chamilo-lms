<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\ExtractorInterface;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformerInterface;
use Chamilo\PluginBundle\MigrationMoodle\Traits\MapTrait\MapTrait;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class BaseTask.
 */
abstract class BaseTask
{
    use MapTrait;

    /**
     * @var int
     */
    protected $taskId;
    /**
     * @var ExtractorInterface
     */
    protected $extractor;
    /**
     * @var TransformerInterface
     */
    protected $transformer;
    /**
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * BaseTask constructor.
     */
    public function __construct()
    {
        $this->extractor = $this->getExtractor();

        $this->transformer = $this->getTransformer();

        $this->loader = $this->getLoader();

        $this->calledClass = get_called_class();

        $this->initMapLog();
    }

    /**
     * @return array
     */
    abstract public function getExtractConfiguration();

    /**
     * @return array
     */
    abstract public function getTransformConfiguration();

    /**
     * @return array
     */
    abstract public function getLoadConfiguration();

    public function execute()
    {
        foreach ($this->extractFiltered() as $extractedData) {
            try {
                $incomingData = $this->transformer->transform($extractedData);
            } catch (\Exception $exception) {
                $this->showMessage('Error while transforming extracted data.', $exception->getMessage(), $extractedData);

                continue;
            }

            try {
                $loadedId = $this->loader->load($incomingData);
            } catch (\Exception $exception) {
                $this->showMessage('Error while loading transformed data.', $exception->getMessage(), $incomingData);

                continue;
            }

            $hash = $this->saveMapLog($extractedData['id'], $loadedId);

            $this->showMessage('Data migrated.', "{$extractedData['id']} -> $loadedId", $hash);
        }
    }

    /**
     * @param string $first
     * @param string $second
     * @param string $data
     */
    private function showMessage($first, $second, $data)
    {
        echo '<p>'
            ."$first "
            ."<em>$second</em><br>"
            .'<code>'.print_r($data, true).'</code>'
            .'</p>';
    }

    /**
     * @throws \Exception
     */
    private function initMapLog()
    {
        $taskName = $this->getTaskName();

        $id = \Database::insert(
            'plugin_migrationmoodle_task',
            ['name' => $taskName]
        );

        if (empty($id)) {
            throw new \Exception("Failed to save task ($taskName).");
        }

        $this->taskId = $id;
    }

    /**
     * @return \Generator
     */
    private function extractFiltered()
    {
        try {
            foreach ($this->extractor->extract() as $extractedData) {
                if ($this->extractor->filter($extractedData)) {
                    continue;
                }

                yield $extractedData;
            }
        } catch (\Exception $exception) {
            echo 'Error while extracting data. ';
            echo $exception->getMessage();
        }
    }

    /**
     * @param int $extractedId
     * @param int $loadedId
     *
     * @throws \Exception
     *
     * @return string
     */
    private function saveMapLog($extractedId, $loadedId)
    {
        $hash = md5("$extractedId@@$loadedId");

        \Database::insert(
            'plugin_migrationmoodle_item',
            [
                'hash' => $hash,
                'extracted_id' => $extractedId,
                'loaded_id' => $loadedId,
                'task_id' => $this->taskId,
            ]
        );

        return $hash;
    }

    /**
     * @return ExtractorInterface
     */
    private function getExtractor()
    {
        $configuration = $this->getExtractConfiguration();

        $extractorClass = $configuration['class'];
        /** @var ExtractorInterface $extractor */
        $extractor = new $extractorClass($configuration);

        return $extractor;
    }

    /**
     * @return TransformerInterface
     */
    private function getTransformer()
    {
        $configuration = $this->getTransformConfiguration();

        $transformerClass = $configuration['class'];
        /** @var TransformerInterface $extractor */
        $extractor = new $transformerClass($configuration);

        return $extractor;
    }

    /**
     * @return LoaderInterface
     */
    private function getLoader()
    {
        $configuration = $this->getLoadConfiguration();

        $loaderClass = $configuration['class'];
        /** @var LoaderInterface $extractor */
        $extractor = new $loaderClass($configuration);

        return $extractor;
    }
}
