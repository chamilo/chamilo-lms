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
global $_configuration;
$courses = array();
$courses[] = array(
    'code' => 'ENGLISH101',
    'title' => 'English for beginners',
    'tutor' => '',
    'category' => 'PROJ',
    'language' => 'english',
    'admin_id' => 1,
    'expires' => '2020-09-01 00:00:00',
    'fill' => true,
);
