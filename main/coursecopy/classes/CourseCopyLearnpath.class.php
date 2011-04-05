<?php
/* For licensing terms, see /license.txt */
/**
 * A learnpath
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class CourseCopyLearnpath extends Resource {
	/**
	 * Type of learnpath (can be dokeos (1), scorm (2), aicc (3))
	 */
	var $lp_type;
	/**
	 * The name
	 */
	var $name;
	/**
	 * The reference
	 */
	var $ref;
	/**
	 * The description
	 */
	var $description;
	/**
	 * Path to the learning path files
	 */
	var $path;
	/**
	 * Whether additional commits should be forced or not
	 */
	var $force_commit;
	/**
	 * View mode by default ('embedded' or 'fullscreen')
	 */
	var $default_view_mod;
	/**
	 * Default character encoding
	 */
	var $default_encoding;
	/**
	 * Display order
	 */
	var $display_order;
	/**
	 * Content editor/publisher
	 */
	var $content_maker;
	/**
	 * Location of the content (local or remote)
	 */
	var $content_local;
	/**
	 * License of the content
	 */
	var $content_license;
	/**
	 * Whether to prevent reinitialisation or not
	 */
	var $prevent_reinit;
	/**
	 * JavaScript library used
	 */
	var $js_lib;
	/**
	 * Debug level for this lp
	 */
	var $debug;
	/**
	 * The items
	 */
	var $items;
	/**
	 * The learnpath visibility on the homepage
	 */
	var $visibility;
	
	/**
	 * Author info
	 */
	var $author;
	
	/**
	 * Author's image
	 */
	var $preview_image;
								

	/**
	 * Create a new learnpath
	 * @param integer ID
	 * @param integer Type (1,2,3,...)
	 * @param string $name
	 * @param string $path
	 * @param string $ref
	 * @param string $description
	 * @param string $content_local
	 * @param string $default_encoding
	 * @param string $default_view_mode
	 * @param bool   $prevent_reinit
	 * @param bool   $force_commit
	 * @param string $content_maker
	 * @param integer $display_order
	 * @param string $js_lib
	 * @param string $content_license
	 * @param integer $debug
	 * @param string $visibility
	 * @param array  $items
	 */
	function CourseCopyLearnpath($id,$type,$name, $path,$ref,$description,$content_local,$default_encoding,$default_view_mode,$prevent_reinit,$force_commit,
	                             $content_maker, $display_order,$js_lib,$content_license,$debug, $visibility, $author, $preview_image,
	                             $use_max_score, $autolunch, $created_on, $modified_on, $publicated_on, $expired_on, $session_id, $items) {
		parent::Resource($id,RESOURCE_LEARNPATH);
		$this->lp_type = $type;
		$this->name = $name;
		$this->path = $path;
		$this->ref = $ref;
		$this->description = $description;
		$this->content_local = $content_local;
		$this->default_encoding = $default_encoding;
		$this->default_view_mod = $default_view_mode;
		$this->prevent_reinit = $prevent_reinit;
		$this->force_commit = $force_commit;
		$this->content_maker = $content_maker;
		$this->display_order = $display_order;
		$this->js_lib = $js_lib;
		$this->content_license = $content_license;
		$this->debug = $debug;
		$this->visibility=$visibility;
		
		$this->use_max_score=$use_max_score;
		$this->autolunch=$autolunch;
		$this->created_on=$created_on;
		$this->modified_on=$modified_on;
		$this->publicated_on=$publicated_on;
		$this->expired_on=$expired_on;
		$this->session_id=$session_id;	
		
		$this->author= $author;
		$this->preview_image= $preview_image;
		
		$this->items = $items;
	}
	/**
	 * Get the items
	 */
	function get_items()
	{
		return $this->items;
	}
	/**
	 * Check if a given resource is used as an item in this chapter
	 */
	function has_item($resource)
	{
		foreach($this->items as $index => $item) {
			if( $item['id'] == $resource->get_id() && $item['type'] == $resource->get_type()) {
				return true;
			}
		}
		return false;
	}
	/**
	 * Show this learnpath
	 */
	function show() {
		parent::show();
		echo $this->name;
	}
}