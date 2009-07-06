<?php

/*
 *	Dokeos - elearning and course management software
 *
 *	Copyright (c) 2009 Dokeos SPRL
 *	Copyright (c) 2009 Juan Carlos Raña
 *	Copyright (c) 2009 Ivan Tcholakov
 *
 *	For a full list of contributors, see "credits.txt".
 *	The full license can be read in "license.txt".
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 2
 *	of the License, or (at your option) any later version.
 *
 *	See the GNU General Public License for more details.
 *
 * Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
 * Mail: info@dokeos.com
 */

/*
 * Custom editor configuration settings, php-side.
 *
 * Follow this link for more information:
 * http://docs.fckeditor.net/FCKeditor_2.x/Developers_Guide/Configuration/Configuration_Options
 *
 */

/*
 * Editor's toolbar definitions.
 */

$config['ToolbarSets']['Full'] = array(
	array('FitWindow','PasteWord','Link','Unlink','Anchor','-','Image','flvPlayer','Flash','EmbedMovies','MP3','YouTube','Table','Rule','-','Subscript', 'Superscript','-','OrderedList','UnorderedList','Outdent','Indent','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'),'/',
	array('FontFormat','Style','FontName','FontSize','Bold','Italic','Underline','StrikeThrough','TextColor', 'BGColor','-','Source')
);

$config['ToolbarSets']['Middle'] = array(
	array('FontSize','Bold','Italic','Underline','StrikeThrough','TextColor','-','OrderedList','UnorderedList','-','Rule','Link','Unlink','Table','-','Image','Flash','Source')
);

$config['ToolbarSets']['Small'] = array(
	array('Bold','Italic','Underline','StrikeThrough','Link','Unlink','Image','Flash','OrderedList','UnorderedList','Table')
);//used by test ? exercice/feedback.php 
////


///// Admin tools /////

// Edit platform home page
$config['ToolbarSets']['EditHomePage'] = array(
	array('NewPage','Templates','Save','Print','PageBreak','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll','-','Find'),
	array('Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','Smiley','SpecialChar','googlemaps'),
	array('FontFormat','FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'),
	array('Source')
);

// Insert or Edit a page link in the platform home page
$config['ToolbarSets']['LinksHomePage'] = array(
	array('NewPage','Templates','Save','Print','PageBreak','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll','-','Find'),
	array('Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','Smiley','SpecialChar','googlemaps'),
	array('FontFormat','FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'),
	array('Source')
);

// System Announcements
$config['ToolbarSets']['SystemAnnouncements'] = array(
	array('Save','FitWindow','PasteWord','-','Undo','Redo'),
    array('Link','Unlink','Anchor'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor','-','Source'),
    '/',
    array('Style','FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('JustifyLeft','JustifyCenter','JustifyRight')
);

// Global Agenda
$config['ToolbarSets']['GlobalAgenda'] = array(
	array('FitWindow','-','PasteWord','-','Undo','Redo'),
	array('Link','Unlink'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','SpecialChar','googlemaps'),
	array('FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('OrderedList','UnorderedList','-','TextColor','BGColor'),
	array('Source')
);

// Admin Templates
$config['ToolbarSets']['AdminTemplates'] = array(
	array('NewPage','Templates','Save','Print','PageBreak','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll','-','Find'),
	array('Bold','Italic','Underline'),
	array('Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','Smiley','SpecialChar','googlemaps'),
	array('FontFormat','FontName','FontSize'),
	array('JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'),
	array('Source')
);

// FAQ
$config['ToolbarSets']['FAQ'] = array(
	array('Save','Preview','Source')
);

///// users tools /////

// My Profile (optional fields)
$config['ToolbarSets']['Profil'] = array(
	array('FitWindow','-','PasteWord','-','Undo','Redo'),							   
	array('Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','Smiley'),
	'/',
	array('FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('JustifyLeft','JustifyCenter','-','OrderedList','UnorderedList','-','TextColor','BGColor'),
	array('Source')
);

// Messages
$config['ToolbarSets']['Messages'] = array(
	array('FitWindow','Undo','Redo'),
	array('Link','Unlink'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','Smiley','googlemaps'),
	array('Bold','Italic','Underline'),
	array('OrderedList','UnorderedList','-','Blockquote','-','TextColor'),
	array('ShowBlocks')
);

///// Course tools /////

// Course introduction
$config['ToolbarSets']['Introduction'] = array(
	array('NewPage','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll'),
	array('Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','SpecialChar'),
	array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor','-','Source'),
	'/',
	array('Style','FontFormat','FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('JustifyLeft','JustifyCenter','JustifyRight')
);

// Agenda
$config['ToolbarSets']['Agenda'] = array(
	array('Save','FitWindow','PasteWord','-','Undo','Redo'),
    array('Link','Unlink','Anchor'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    '/',   
    array('Style','FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'),
	array('Source')
);

$config['ToolbarSets']['Agenda_Student'] = array(
	array('Save','FitWindow','PasteWord','-','Undo','Redo'),
    array('Link','Unlink','Anchor'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    '/',   
    array('FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'),
	array('ShowBlocks')
);

// Announcements
$config['ToolbarSets']['Announcements'] = array(
	array('Save','FitWindow','PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor','-','Source'),
    '/',
    array('Style','FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('JustifyLeft','JustifyCenter','JustifyRight')
);

$config['ToolbarSets']['Announcements_Student'] = array(
	array('Save','FitWindow','PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'),
    '/',   
    array('Style','FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('JustifyLeft','JustifyCenter','JustifyRight'),
	array('ShowBlocks')
);

// Blog
$config['ToolbarSets']['Blog'] = array(
	array('FitWindow','-','PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','googlemaps'),
	array('FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('JustifyLeft','JustifyCenter','-','OrderedList','UnorderedList','-','TextColor','BGColor'),
	array('Source')
);

$config['ToolbarSets']['Blog_Student'] = array(
	array('FitWindow','-','PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','googlemaps'),
	array('FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('JustifyLeft','JustifyCenter','-','OrderedList','UnorderedList','-','TextColor','BGColor'),
	array('ShowBlocks')
);

$config['ToolbarSets']['BlogComment'] = array(
	array('FitWindow','-','PasteWord','-','Undo','Redo'),
	array('Link','Unlink'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','googlemaps'),
	array('Bold','Italic','Underline'),
	array('JustifyLeft','JustifyCenter','-','OrderedList','UnorderedList','-','TextColor','BGColor'),
	array('Source')
);

$config['ToolbarSets']['BlogComment_Student'] = array(
	array('FitWindow','-','PasteWord','-','Undo','Redo'),
	array('Link','Unlink'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','googlemaps'),
	array('Bold','Italic','Underline'),
	array('JustifyLeft','JustifyCenter','-','OrderedList','UnorderedList','-','TextColor','BGColor'),
	array('ShowBlocks')
);

// Course Description
$config['ToolbarSets']['CourseDescription'] = array(
	array('NewPage','Save','FitWindow','PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','SpecialChar'),
	array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor','Source'),
	'/',	
	array('Style','FontFormat','FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('JustifyLeft','JustifyCenter','JustifyRight')
);

// Documents
$config['ToolbarSets']['Documents'] = array(
	array('Save','FitWindow','PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','SpecialChar'),
	array('Outdent','Indent','-','TextColor','BGColor','-','OrderedList','UnorderedList','-','Source'),
	'/',	
	array('Style','FontFormat','FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('JustifyLeft','JustifyCenter','JustifyRight')
);

$config['ToolbarSets']['Documents_Student'] = array(
	array('Save','FitWindow','PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','SpecialChar'),
	array('Outdent','Indent','-','TextColor','BGColor','-','OrderedList','UnorderedList'),
	'/',	
	array('Style','FontFormat','FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('JustifyLeft','JustifyCenter','JustifyRight'),
	array('ShowBlocks')
);

// Forum
$config['ToolbarSets']['ForumLight'] = array(
	array('Bold','Italic','Underline','StrikeThrough')
);

$config['ToolbarSets']['Forum'] = array(
    array('Save','FitWindow','PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor','Source'),
    '/',
    array('Style','FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('JustifyLeft','JustifyCenter','JustifyRight')
);

$config['ToolbarSets']['Forum_Student'] = array(
	array('Save','FitWindow','PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'),
    '/',   
    array('Style','FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('JustifyLeft','JustifyCenter','JustifyRight'),
	array('ShowBlocks')
);

// Glossary
$config['ToolbarSets']['Glossary'] = array(
	array('Save','FitWindow','PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor','-','Source'),
    '/',
    array('Style','FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('JustifyLeft','JustifyCenter','JustifyRight')
);

// Learning Path
$config['ToolbarSets']['LearnPath'] = array(
	array('PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3','Table','SpecialChar'),
	array('Outdent','Indent','TextColor','BGColor','-','OrderedList','UnorderedList','JustifyLeft','JustifyCenter','JustifyRight'),
	'/',	
	array('Style','FontFormat','FontName','FontSize'),
	array('Bold','Italic','Underline','-','Source'),
);//save, FitWindow don't run well here

$config['ToolbarSets']['CommentLearningPath'] = array(
	array('Link','Unlink','Bold','Italic','TextColor','BGColor','Source')
);

// Notebook
$config['ToolbarSets']['Notebook'] = array(
	array('Save','FitWindow','-','PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor','-','Source'),
    '/',
    array('Style','FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('JustifyLeft','JustifyCenter','JustifyRight')
);

$config['ToolbarSets']['Notebook_Student'] = array(
	array('Save','FitWindow','-','PasteWord','-','Undo','Redo'),
	array('Link','Unlink','Anchor'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'),
    '/',   
    array('Style','FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('JustifyLeft','JustifyCenter','JustifyRight'),
	array('ShowBlocks')
);

// Survey
$config['ToolbarSets']['Survey'] = array(
	array('FitWindow'),
	array('Link','Unlink'),
	array('Image'),
	array('Table'),
	array('FontSize'),
	array('Bold','Italic'),
	array('OrderedList','UnorderedList','-','TextColor'),
	array('Source')
);

// Test
$config['ToolbarSets']['TestDescription'] = array(
	array('FitWindow','-','PasteWord','-','Undo','Redo'),
    array('Link','Unlink','Anchor'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor','-','Source'),
    '/',   
    array('Style','FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('JustifyLeft','JustifyCenter','JustifyRight')
);

$config['ToolbarSets']['QuestionDescription'] = array(
	array('FitWindow','-','PasteWord','-','Undo','Redo'),
    array('Link','Unlink'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor','-','Source'),
    '/',   
    array('Style','FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('JustifyLeft','JustifyCenter','JustifyRight')
);

$config['ToolbarSets']['Answer'] = array(
    array('FitWindow','Bold','Image','Link','PasteWord','MP3','Table','Subscript','Superscript','Source')	
);

$config['ToolbarSets']['FreeAnswer'] = array(
    array('FitWindow','Bold','Image','Link','PasteWord','MP3','Table','Subscript','Superscript','ShowBlocks')	
);

$config['ToolbarSets']['CommentAnswers'] = array(
	array('Link','Unlink','Bold','Italic','TextColor','BGColor')
);


// Wiki
$config['ToolbarSets']['Wiki'] = array(
	array('NewPage','Templates','Save','PageBreak','Preview','FitWindow','-','PasteText','-','Undo','Redo','-','SelectAll','-','Find'),
	array('Wikilink','Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','Smiley','SpecialChar','googlemaps'),
	array('FontFormat','FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'),
	array('Source')
);

$config['ToolbarSets']['Wiki_Student'] = array(
	array('NewPage','Save','PageBreak','Preview','FitWindow','-','PasteText','-','Undo','Redo','-','SelectAll','-','Find'),
	array('Wikilink','Link','Unlink','Anchor'),
	array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
	array('Table','Smiley','SpecialChar','googlemaps'),
	array('FontFormat','FontName','FontSize'),
	array('Bold','Italic','Underline'),
	array('Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'),
	array('ShowBlocks')
);

// Gradebook
$config['ToolbarSets']['Gradebook'] = array(
    array('Save','FitWindow','-','PasteWord','-','Undo','Redo'),
    array('Link','Unlink','Anchor'),
    array('Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'),
    array('Table','SpecialChar'),
    array('OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'),
    '/',   
    array('Style','FontFormat','FontName','FontSize'),
    array('Bold','Italic','Underline'),
    array('Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'),
    array('Source')
);
