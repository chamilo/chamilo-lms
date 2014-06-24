<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\GraphNavigator;
use Sonata\CoreBundle\Model\ManagerInterface;

/**
 * @author Sylvain Deloux <sylvain.deloux@ekino.com>
 */
abstract class BaseSerializerHandler implements SerializerHandlerInterface
{
    /**
     * @var ManagerInterface
     */
    protected $manager;

    public function __construct(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods()
    {
        $type    = static::getType();
        $formats = array('json', 'xml', 'yml');
        $methods = array();

        foreach ($formats as $format) {
            $methods[] = array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format'    => $format,
                'type'      => $type,
                'method'    => 'serializeObjectToId',
            );

            $methods[] = array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format'    => $format,
                'type'      => $type,
                'method'    => 'deserializeObjectFromId',
            );
        }

        return $methods;
    }

    /**
     * Serialize data object to id.
     *
     * @param VisitorInterface $visitor
     * @param object           $data
     * @param array            $type
     * @param Context          $context
     *
     * @return int|null
     */
    public function serializeObjectToId(VisitorInterface $visitor, $data, array $type, Context $context)
    {
        $className = $this->manager->getClass();

        if ($data instanceof $className) {
            return $visitor->visitInteger($data->getId(), $type, $context);
        }

        return null;
    }

    /**
     * Deserialize object from its id.
     *
     * @param VisitorInterface $visitor
     * @param int              $data
     * @param array            $type
     *
     * @return null|object
     */
    public function deserializeObjectFromId(VisitorInterface $visitor, $data, array $type)
    {
        return $this->manager->findOneBy(array('id' => $data));
    }
}
