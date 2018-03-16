<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Class for migrating the wiki
 * Wiki backup script.
 *
 * @package chamilo.backup
 *
 * @author Matthias Crauwels <matthias.crauwels@UGent.be>, Ghent University
 */
class Wiki extends Resource
{
    public $id;
    public $page_id;
    public $reflink;
    public $title;
    public $content;
    public $user_id;
    public $group_id;
    public $timestamp;
    public $progress;
    public $version;

    /**
     * Wiki constructor.
     *
     * @param int $id
     * @param int $page_id
     * @param $reflink
     * @param $title
     * @param $content
     * @param $user_id
     * @param $group_id
     * @param $timestamp
     * @param $progress
     * @param $version
     */
    public function __construct(
        $id,
        $page_id,
        $reflink,
        $title,
        $content,
        $user_id,
        $group_id,
        $timestamp,
        $progress,
        $version
    ) {
        parent::__construct($id, RESOURCE_WIKI);
        $this->id = $id;
        $this->page_id = $page_id;
        $this->reflink = $reflink;
        $this->title = $title;
        $this->content = $content;
        $this->user_id = $user_id;
        $this->group_id = $group_id;
        $this->dtime = $timestamp;
        $this->progress = $progress;
        $this->version = $version;
    }

    public function show()
    {
        parent::show();
        echo $this->reflink.' ('.(empty($this->group_id) ? get_lang('Everyone') : get_lang('Group').' '.$this->group_id).') '.'<i>('.$this->dtime.')</i>';
    }
}
