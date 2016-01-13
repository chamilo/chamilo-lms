<?php
/* For licensing terms, see /license.txt */

/**
 * Class for migrating the wiki
 * Wiki backup script
 * @package chamilo.backup
 * @author Matthias Crauwels <matthias.crauwels@UGent.be>, Ghent University
 */
class Wiki extends Coursecopy\Resource
{
	var $id;
	var $page_id;
	var $reflink;
	var $title;
	var $content;
	var $user_id;
	var $group_id;
	var $timestamp;
	var $progress;
	var $version;

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
		parent::__construct($id,RESOURCE_WIKI);
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

	function show()
	{
		parent::show();
		echo $this->reflink.' ('. (empty($this->group_id) ? get_lang('Everyone') : get_lang('Group') . ' ' .  $this->group_id) .') ' . '<i>(' . $this->dtime . ')</i>';
	}
}
