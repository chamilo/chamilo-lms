<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Extractor;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\ExtractorInterface;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;

/**
 * Class Extractor.
 */
class BaseExtractor implements ExtractorInterface
{
    /**
     * @var mixed
     */
    private $query;

    /**
     * Extractor constructor.
     */
    public function __construct(array $configuration)
    {
        $this->query = $configuration['query'];
    }

    /**
     * @return bool
     */
    public function filter(array $sourceData)
    {
        return false;
    }

    /**
     * @throws \Exception
     *
     * @return \Generator|iterable
     */
    public function extract()
    {
        $plugin = \MigrationMoodlePlugin::create();

        try {
            $connection = $plugin->getConnection();
        } catch (DBALException $e) {
            throw new \Exception('Unable to start connection.', 0, $e);
        }

        try {
            $statement = $connection->executeQuery($this->query);
        } catch (DBALException $e) {
            throw new \Exception("Unable to execute query \"{$this->query}\".", 0, $e);
        }

        while ($sourceRow = $statement->fetch(FetchMode::ASSOCIATIVE)) {
            yield $sourceRow;
        }

        $connection->close();
    }
}
