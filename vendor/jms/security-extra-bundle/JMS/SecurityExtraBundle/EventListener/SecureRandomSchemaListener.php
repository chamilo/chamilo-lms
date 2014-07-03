<?php

namespace JMS\SecurityExtraBundle\EventListener;

use JMS\SecurityExtraBundle\Security\Util\SecureRandomSchema;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

class SecureRandomSchemaListener
{
    private $schema;

    public function __construct(SecureRandomSchema $schema)
    {
        $this->schema = $schema;
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $args)
    {
        $this->schema->addToSchema($args->getSchema());
    }
}