<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\GraphQL;

use ApiPlatform\GraphQl\ExecutorInterface;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Psr\Log\LoggerInterface;

class LoggingExecutor implements ExecutorInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function executeQuery(
        Schema $schema,
        $source,
        $rootValue = null,
        $context = null,
        ?array $variableValues = null,
        ?string $operationName = null,
        ?callable $fieldResolver = null,
        ?array $validationRules = null
    ): ExecutionResult {
        $result = GraphQL::executeQuery(
            $schema,
            $source,
            $rootValue,
            $context,
            $variableValues,
            $operationName,
            $fieldResolver,
            $validationRules
        );

        if (!empty($result->errors)) {
            foreach ($result->errors as $error) {
                $msg = '[GraphQL Error] '.$error->getMessage();
                error_log($msg);

                $this->logger->error($msg, [
                    'debugMessage' => $error->getPrevious()?->getMessage() ?? null,
                    'path' => $error->getPath(),
                    'locations' => $error->getLocations(),
                    'extensions' => $error->getExtensions(),
                ]);
            }
        }

        return $result;
    }
}
