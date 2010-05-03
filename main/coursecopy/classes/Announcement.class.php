<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

/**
 * An announcement
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 */
class Announcement extends Resource
{
	/**
	 * The title of the announcement
	 */
	var $title;
	/**
	 * The content of the announcement
	 */
	var $content;
	/**
	 * The date on which this announcement was made
	 */
	var $date;
	/**
	 * The display order of this announcement
	 */
	var $display_order;
	/**
	 * Has the e-mail been sent?
	 */
	var $email_sent;
	/**
	 * Create a new announcement
	 * @param int $id
	 * @param string $title
	 * @param string $content
	 * @param string $date
	 * @param int display_order
	 */
	function Announcement($id,$title,$content,$date,$display_order,$email_sent)
	{
		parent::Resource($id,RESOURCE_ANNOUNCEMENT);
		$this->content = $content;
		$this->title = $title;
		$this->date = $date;
		$this->display_order = $display_order;
		$this->email_sent = $email_sent;
	}
	/**
	 * Show this announcement
	 */
	function show()
	{
		parent::show();
		echo $this->date.': '.$this->title;
	}
}
?>