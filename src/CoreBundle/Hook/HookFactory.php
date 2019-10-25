<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookEventInterface;
use Doctrine\ORM\EntityManager;

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
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $type
     *
     * @throws \Exception
     *
     * @return HookEventInterface
     */
    public function build(string $type)
    {
        if (!class_exists($type)) {
            throw new \Exception('Class "'.$type.'" fot found');
        }

        return $type::create($this->entityManager);
    }
}
