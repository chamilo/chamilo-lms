<?php

/* For licensing terms, see /license.txt */
/**
 * Exercises questions backup script
 * @package chamilo.backup
 */
/**
 * Code
 */
require_once 'Resource.class.php';

/**
 * An QuizQuestion
 * @author Hubert Borderiou <hubert.borderiou@grenet.fr>
 * @package chamilo.backup
 */
class CourseCopyTestcategory extends Resource
{
    /**
     * The title
     */
    var $title;

    /**
     * The description
     */
    var $description;

    /**
     * Create a new TestCategory
     * @param string $title
     * @param string $description
     */
    function CourseCopyTestcategory($id, $title, $description) {
        parent::Resource($id, RESOURCE_TEST_CATEGORY);
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * Show the test_category title, used in the partial recycle_course.php form
     */
    function show() {
        parent::show();
        echo $this->title;
    }
}