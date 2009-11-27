<?php
require_once('Resource.class.php');

/**
 * Class for migrating the wiki
 *
 *@author Matthias Crauwels <matthias.crauwels@UGent.be>, Ghent University
 */
class Wiki extends Resource
{
	var $id;
	var $reflink;
	var $title;
	var $content;
	var $user_id;
	var $group_id;
	var $timestamp;
	var $template;
	var $menu;

	function Wiki($id, $reflink, $title, $content, $user_id, $group_id, $timestamp, $template, $menu)
	{
		parent::Resource($id,RESOURCE_WIKI);
		$this->page_id					= $id;
		$this->reflink 					= $reflink;
		$this->title 					= $title;
		$this->content					= $content;
		$this->user_id					= $user_id;
		$this->group_id					= $group_id;
		$this->dtime					= $timestamp;
	}

	function show()
	{
		parent::show();
		echo $this->reflink.' ('. (empty($this->group_id) ? get_lang('Everyone') : get_lang('Group') . ' ' .  $this->group_id) .') ' . '<i>(' . $this->dtime . ')</i>';
	}
}
?>