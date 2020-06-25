<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

trait Pagination
{
    use JsonDeserializableTrait;

    /** @var int */
    public $page_count;

    /** @var int counting from 1 */
    public $page_number;

    /** @var int */
    public $page_size;

    /** @var int */
    public $total_records;

    /** @var string The next page token is used to paginate through large result sets.
     * A next page token will be returned whenever the set of available results exceeds the current page size.
     * The expiration period for this token is 15 minutes.
     */
    public $next_page_token;
}
