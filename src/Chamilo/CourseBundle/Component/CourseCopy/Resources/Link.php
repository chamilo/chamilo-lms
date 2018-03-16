<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Class Link
 * A WWW-link from the Links-module in a Chamilo-course.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 */
class Link extends Resource
{
    /**
     * The title.
     */
    public $title;
    /**
     * The URL.
     */
    public $url;
    /**
     * The description.
     */
    public $description;
    /**
     * Id of this links category.
     */
    public $category_id;
    /**
     * Display link on course homepage.
     */
    public $on_homepage;
    /**
     * @var string The link target
     */
    public $target;

    /**
     * Create a new Link.
     *
     * @param int    $id          The id of this link in the Chamilo-course
     * @param string $title
     * @param string $url
     * @param string $description
     */
    public function __construct(
        $id,
        $title,
        $url,
        $description,
        $category_id,
        $on_homepage
    ) {
        parent::__construct($id, RESOURCE_LINK);
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
        $this->category_id = $category_id;
        $this->on_homepage = $on_homepage;
    }

    /**
     * Show this resource.
     */
    public function show()
    {
        parent::show();
        echo $this->title.' ('.$this->url.')';
    }
}
