<?php
/* For licensing terms, see /license.txt */

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
	 * Create a new Event
	 * @param int $id
	 * @param string $title
	 * @param string $content
	 * @param string $date
	 * @param string $hour
	 * @param int $duration
	 */
	function Event($id,$title,$content,$start_date,$end_date)
	{
		parent::Resource($id,RESOURCE_EVENT);
		$this->title = $title;
		$this->content = $content;
		$this->start_date = $start_date;
		$this->end_date = $end_date;
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