<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\ExtractorInterface;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformerInterface;
use Chamilo\PluginBundle\MigrationMoodle\Messages\ExtractMessage;
use Chamilo\PluginBundle\MigrationMoodle\Messages\LoadMessage;
use Chamilo\PluginBundle\MigrationMoodle\Messages\TransformMessage;
use Chamilo\PluginBundle\MigrationMoodle\Traits\MapTrait\MapTrait;

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
     * @var \MigrationMoodlePlugin
     */
    protected $plugin;

    /**
     * BaseTask constructor.
     */
    public function __construct()
    {
        $this->plugin = \MigrationMoodlePlugin::create();

        $this->extractor = $this->getExtractor();

        $this->transformer = $this->getTransformer();

        $this->loader = $this->getLoader();

        $this->calledClass = get_called_class();
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
        $outputBuffering = isset($GLOBALS['outputBuffering']) ? $GLOBALS['outputBuffering'] : true;

        $taskId = \Database::insert(
            'plugin_migrationmoodle_task',
            ['name' => $this->getTaskName()]
        );

        $i = 0;

        foreach ($this->executeETL() as $hash => $ids) {
            \Database::insert(
                'plugin_migrationmoodle_item',
                [
                    'hash' => $hash,
                    'extracted_id' => (int) $ids['extracted'],
                    'loaded_id' => (int) $ids['loaded'],
                    'task_id' => $taskId,
                ]
            );

            echo "[".date(\DateTime::ATOM)."]\tData migrated: $hash".PHP_EOL;

            $i++;

            if ($i % 10 === 0 && $outputBuffering) {
                flush();
                ob_flush();
            }
        }

        if ($outputBuffering) {
            ob_end_flush();
        }
    }

    /**
     * @return \Generator
     */
    private function executeETL()
    {
        foreach ($this->extractFiltered() as $extractedData) {
            try {
                $incomingData = $this->transformer->transform($extractedData);
            } catch (\Exception $exception) {
                new TransformMessage($extractedData, $exception);

                continue;
            }

            try {
                $loadedId = $this->loader->load($incomingData);
            } catch (\Exception $exception) {
                new LoadMessage($incomingData, $exception);

                continue;
            }

            yield md5("{$extractedData['id']}@@$loadedId") => [
                'extracted' => $extractedData['id'],
                'loaded' => $loadedId,
            ];
        }
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
            new ExtractMessage($exception);
        }
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
