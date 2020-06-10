<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

class ParticipantList
{
    use Pagination;

    /** @var ParticipantListItem[] */
    public $participants;

    /**
     * ParticipantList constructor.
     */
    public function __construct()
    {
        $this->participants = [];
    }

    /**
     * @see JsonDeserializable::itemClass()
     *
     * @param string $propertyName array property name
     *
     * @throws Exception on wrong propertyName
     *
     * @return string
     */
    protected function itemClass($propertyName)
    {
        if ('participants' === $propertyName) {
            return ParticipantListItem::class;
        }
        throw new Exception("No such array property $propertyName");
    }
}
