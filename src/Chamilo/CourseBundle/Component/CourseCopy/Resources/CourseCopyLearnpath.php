<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Class CourseCopyLearnpath.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class CourseCopyLearnpath extends Resource
{
    /**
     * Type of learnpath (can be dokeos (1), scorm (2), aicc (3)).
     */
    public $lp_type;
    /**
     * The name.
     */
    public $name;
    /**
     * The reference.
     */
    public $ref;
    /**
     * The description.
     */
    public $description;
    /**
     * Path to the learning path files.
     */
    public $path;
    /**
     * Whether additional commits should be forced or not.
     */
    public $force_commit;
    /**
     * View mode by default ('embedded' or 'fullscreen').
     */
    public $default_view_mod;
    /**
     * Default character encoding.
     */
    public $default_encoding;
    /**
     * Display order.
     */
    public $display_order;
    /**
     * Content editor/publisher.
     */
    public $content_maker;
    /**
     * Location of the content (local or remote).
     */
    public $content_local;
    /**
     * License of the content.
     */
    public $content_license;
    /**
     * Whether to prevent reinitialisation or not.
     */
    public $prevent_reinit;
    /**
     * JavaScript library used.
     */
    public $js_lib;
    /**
     * Debug level for this lp.
     */
    public $debug;
    /**
     * The items.
     */
    public $items;
    /**
     * The learnpath visibility on the homepage.
     */
    public $visibility;

    /**
     * Author info.
     */
    public $author;

    /**
     * Lp previous requisite.
     */
    public $prerequisite;

    /**
     * Author's image.
     */
    public $preview_image;

    public $subscribeUsers;
    public $hideTableOfContents;
    public $accumulateWorkTime;

    /**
     * Create a new learnpath.
     *
     * @param int ID
     * @param int Type (1,2,3,...)
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
     * @param int    $display_order
     * @param string $js_lib
     * @param string $content_license
     * @param int    $debug
     * @param string $visibility
     * @param int    $categoryId
     * @param array  $items
     * @param int    $accumulateWorkTime
     * @param int    $prerequisite
     */
    public function __construct(
        $id,
        $type,
        $name,
        $path,
        $ref,
        $description,
        $content_local,
        $default_encoding,
        $default_view_mode,
        $prevent_reinit,
        $force_commit,
        $content_maker,
        $display_order,
        $js_lib,
        $content_license,
        $debug,
        $visibility,
        $author,
        $preview_image,
        $use_max_score,
        $autolaunch,
        $created_on,
        $modified_on,
        $publicated_on,
        $expired_on,
        $session_id,
        $categoryId,
        $subscribeUsers,
        $hideTableOfContents,
        $items,
        $accumulateWorkTime,
        $prerequisite
    ) {
        parent::__construct($id, RESOURCE_LEARNPATH);
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
        $this->visibility = $visibility;
        $this->use_max_score = $use_max_score;
        $this->autolaunch = $autolaunch;
        $this->created_on = $created_on;
        $this->modified_on = $modified_on;
        $this->publicated_on = $publicated_on;
        $this->expired_on = $expired_on;
        $this->session_id = $session_id;
        $this->author = $author;
        $this->preview_image = $preview_image;
        $this->categoryId = $categoryId;
        $this->subscribeUsers = $subscribeUsers;
        $this->hideTableOfContents = $hideTableOfContents;
        $this->items = $items;
        $this->accumulateWorkTime = $accumulateWorkTime;
        $this->prerequisite = $prerequisite;
    }

    /**
     * Get the items.
     */
    public function get_items()
    {
        return $this->items;
    }

    /**
     * Check if a given resource is used as an item in this chapter.
     */
    public function has_item($resource)
    {
        foreach ($this->items as $item) {
            if ($item['id'] == $resource->get_id() &&
                isset($item['type']) && $item['type'] == $resource->get_type()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Show this learnpath.
     */
    public function show()
    {
        parent::show();
        echo $this->name;
    }
}
