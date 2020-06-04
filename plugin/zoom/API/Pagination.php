<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

trait Pagination
{
    use JsonDeserializable;

    /** @var int */
    public $page_count;

    /** @var int counting from 1 */
    public $page_number;

    /** @var int */
    public $page_size;

    /** @var int */
    public $total_records;
}
