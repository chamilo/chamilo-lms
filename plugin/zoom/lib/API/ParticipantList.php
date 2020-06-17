<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

class ParticipantList
{
    use Pagination;

    /** @var string The next page token is used to paginate through large result sets.
     * A next page token will be returned whenever the set of available results exceeds the current page size.
     * The expiration period for this token is 15 minutes.
     */
    public $next_page_token;

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
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('participants' === $propertyName) {
            return ParticipantListItem::class;
        }
        throw new Exception("No such array property $propertyName");
    }
}
