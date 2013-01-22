<?php //$id$
/**
 * This script contains the data to fill (or empty) the database with using
 * the fillers in this directory.
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 *
 */
/**
 * Initialisation section
 */

$courses = array();
$courses[] = array(
    'code'                  => 'ENGLISH101',
    'title'                 => 'English for beginners', 
    'description'           => 'English course',
    'category_code'         => 'PROJ',
    'course_language'       => 'english',
    'user_id'               => 1,
    'expiration_date'       => '2020-09-01 00:00:00',
    'exemplary_content'     => true,
    'exes'                  => array(
        'exe'                   => array(
            'title'                 => 'Demo',
            'time'                  => 180,
            'attempts'              => 1,
            'random'                => 100,
            'pass_percentage'       => 70,
            'review_answers'        => 1,
        ),
    ),
);
$qst = array(
    0 => array(
        'title'                 => 'Demo question',
        'desc'                  => 'What do you think about XYZ?',
        'type'                  => 1, //1=Unique answer, see question.class.php
        'answers'               => array(
            0 => array (
                'title'             => 'A',
                'correct'           => 1,
                'score'             => 1,
            ),
            1 => array (
                'title'             => 'B',
                'correct'           => 0,
                'score'             => 0,
            ),
            2 => array (
                'title'             => 'C',
                'correct'           => 0,
                'score'             => 0,
            ),
            3 => array (
                'title'             => 'D',
                'correct'           => 0,
                'score'             => 0,
            ),
        
        ),
    ),
);
