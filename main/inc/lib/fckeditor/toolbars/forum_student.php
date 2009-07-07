<?php
// Course tools
// Forum (student)
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
$config['BlockCopyPaste']['Forum_Student'] = true;
