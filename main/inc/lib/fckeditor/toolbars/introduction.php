<?php
// Course tools
// Course introduction

// The toolbar set that is visible when the editor has "normal" size.
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

/*
// The toolbar set that is visible when the editor is maximized.
// If it has not been defined, then the toolbar set for the "normal" size is used.
$config['ToolbarSets']['IntroductionMaximized'] = array(
	array('FitWindow','-') // ...
);
*/

// Here new width and height of the editor may be set.
//$config['Width'] = '100%';
//$config['Height'] = '300';

// This setting activates on leaners only.
// For users with other statuses there is no blocking copy/paste.
//$config['BlockCopyPaste'] = false;
