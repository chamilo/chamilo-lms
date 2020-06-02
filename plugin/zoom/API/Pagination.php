<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

class Pagination
{
    /** @var integer */
    public $page_count;
    /** @var integer counting from 1 */
    public $page_number;
    /** @var integer */
    public $page_size;
    /** @var integer */
    public $total_records;
}
