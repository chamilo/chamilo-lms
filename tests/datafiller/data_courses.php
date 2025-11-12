<?php

/* For licensing terms, see /license.txt */

/**
 * This script contains the data to fill (or empty) the database using
 * the fillers in this directory.
 * It contains more than 10 courses to enable testing pagination in the
 * course catalogue. Courses are distributed in several languages and categories.
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
$courses = [];
// 1
$courses[] = [
    'code' => 'ENGLISH101',
    'title' => 'English for beginners',
    'description' => 'English course',
    'category_code' => 'LANG',
    'course_language' => 'en_US',
    'user_id' => 1,
    'expiration_date' => '2030-09-01 00:00:00',
    'exemplary_content' => true,
];
// 2
$courses[] = [
    'code' => 'SPANISH101',
    'title' => 'Español para iniciantes',
    'description' => 'Curso de español',
    'category_code' => 'LANG',
    'course_language' => 'es',
    'user_id' => 1,
    'expiration_date' => '2030-09-01 00:00:00',
    'exemplary_content' => true,
];
// 3
$courses[] = [
    'code' => 'FRENCH101',
    'title' => 'Français pour débutants',
    'description' => 'Cours de français',
    'category_code' => 'LANG',
    'course_language' => 'fr_FR',
    'user_id' => 1,
    'expiration_date' => '2030-09-01 00:00:00',
    'exemplary_content' => true,
];
// 4
$courses[] = [
    'code' => 'ARABIC101',
    'title' => 'العربية للمبتدئين',
    'description' => 'دورة العربية للمبتدئين',
    'category_code' => 'LANG',
    'course_language' => 'ar',
    'user_id' => 1,
    'expiration_date' => '2030-09-01 00:00:00',
    'exemplary_content' => true,
];
// 5
$courses[] = [
    'code' => 'SOLARSYSTEM',
    'title' => 'Our solar system',
    'description' => 'Introduction to our solar system and the interactions between planets',
    'category_code' => 'PROJ',
    'course_language' => 'en_US',
    'user_id' => 1,
    'expiration_date' => '2030-09-01 00:00:00',
    'exemplary_content' => true,
];
// 6
$courses[] = [
    'code' => 'MARNAVIGATION',
    'title' => 'Maritime Navigation',
    'description' => 'Preparation course for the International Maritime Navigation exam',
    'category_code' => 'PROJ',
    'course_language' => 'en_US',
    'user_id' => 1,
    'expiration_date' => '2030-09-01 00:00:00',
    'exemplary_content' => true,
];
// 7
$courses[] = [
    'code' => 'NATGEO',
    'title' => 'National Geography',
    'description' => 'Introduction to geography at a national level',
    'category_code' => 'PROJ',
    'course_language' => 'en_US',
    'user_id' => 1,
    'expiration_date' => '2030-09-01 00:00:00',
    'exemplary_content' => true,
];
// 8
$courses[] = [
    'code' => 'JAPANESE101',
    'title' => '日本語',
    'description' => 'Japanese course for beginners',
    'category_code' => 'LANG',
    'course_language' => 'ja',
    'user_id' => 1,
    'expiration_date' => '2030-09-01 00:00:00',
    'exemplary_content' => true,
];
// 9
$courses[] = [
    'code' => 'TIMEMGMT',
    'title' => 'Time management',
    'description' => 'Learn to manage your time efficiently',
    'category_code' => 'PROJ',
    'course_language' => 'en_US',
    'user_id' => 1,
    'expiration_date' => '2030-09-01 00:00:00',
    'exemplary_content' => true,
];
// 10
$courses[] = [
    'code' => 'HEBREW101',
    'title' => 'עברית למתחילים',
    'description' => '',
    'category_code' => 'LANG',
    'course_language' => 'he',
    'user_id' => 1,
    'expiration_date' => '2030-09-01 00:00:00',
    'exemplary_content' => true,
];
// 11
$courses[] = [
    'code' => 'CHINESE101',
    'title' => '初级汉语',
    'description' => '初级汉语课程',
    'category_code' => 'LANG',
    'course_language' => 'zh_CN',
    'user_id' => 1,
    'expiration_date' => '2030-09-01 00:00:00',
    'exemplary_content' => true,
];
// 12
$courses[] = [
    'code' => 'MYANMAR101',
    'title' => 'မြန်မာဘာသာ ဝါနှုများ အတွက်',
    'description' => 'မြန်မာဘာသာ သင်တန်း ဝါနှုများ အတွက်',
    'category_code' => 'LANG',
    'course_language' => 'my_MM',
    'user_id' => 1,
    'expiration_date' => '2030-09-01 00:00:00',
    'exemplary_content' => true,
];
