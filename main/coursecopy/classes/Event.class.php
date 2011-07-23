<?php
/* For licensing terms, see /license.txt */
/**
 * Event backup script
 * @package chamilo.backup
 */
require_once 'Resource.class.php';

/**
 * An event
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class Event extends Resource
{
	/**
	 * The title
	 */
	var $title;
	/**
	 * The content
	 */
	var $content;
	/**
	 * The start date
	 */
	var $start_date;
	/**
	 * The end date
	 */
	var $end_date;
	/**
	 * The attachment path
	 */
	var $attachment_path;
	
	/**
	 * The attachment filename
	 */
	var $attachment_filename;
	/**
	 * The attachment size
	 */
	var $attachment_size;
	
	/**
	 * The attachment comment
	 */
	var $attachment_comment;
	
	
	/**
	 * Create a new Event
	 * @param int $id
	 * @param string $title
	 * @param string $content
	 * @param string $date
	 * @param string $hour
	 * @param int $duration
	 */
	function Event($id,$title,$content,$start_date,$end_date, $attachment_path, $attachment_filename, $attachment_size, $attachment_comment) {
		parent::Resource($id,RESOURCE_EVENT);
		
		$this->title 				= $title;
		$this->content 				= $content;
		$this->start_date 			= $start_date;
		$this->end_date 			= $end_date;
		
		$this->attachment_path 		= $attachment_path;
		$this->attachment_filename	= $attachment_filename;
		$this->attachment_size		= $attachment_size;
		$this->attachment_comment	= $attachment_comment;
		
	}
	/**
	 * Show this Event
	 */
	function show()
	{
		parent::show();
		echo $this->title.' ('.$this->start_date.' -> '.$this->end_date.')';
	}
}
?>
