<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookEventInterface;
use Doctrine\ORM\EntityManager;
use Exception;

/**
 * Class HookFactory.
 */
class HookFactory
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * HookFactory constructor.
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Exception
     *
     * @return HookEventInterface
     */
    public function build(string $type)
    {
        if (!class_exists($type)) {
            throw new Exception('Class "'.$type.'" fot found');
        }

        return $type::create($this->entityManager);
    }
}
