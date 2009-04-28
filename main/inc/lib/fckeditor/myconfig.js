/*
 *	Dokeos - elearning and course management software
 *
 *	Copyright (c) 2008-2009 Dokeos SPRL
 *	Copyright (c) 2008-2009 Julio Montoya
 *	Copyright (c) 2008-2009 Juan Carlos Ra�a
 *	Copyright (c) 2008-2009 Ivan Tcholakov
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
 * Editor's toolbar definitions.
 */

FCKConfig.ToolbarSets["Full"] = [
	['FitWindow','PasteWord','Link','Unlink','Anchor','-','Image','flvPlayer','Flash','EmbedMovies','MP3','YouTube','Table','Rule','-','Subscript', 'Superscript','-','OrderedList','UnorderedList','Outdent','Indent','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],'/',['FontFormat','Style','FontName','FontSize','Bold','Italic','Underline','StrikeThrough','TextColor', 'BGColor','-','Source']
] ;

FCKConfig.ToolbarSets["Middle"] = [
	['FontSize','Bold','Italic','Underline','StrikeThrough','TextColor','-','OrderedList','UnorderedList','-','Rule','Link','Unlink','Table','-','Image','Flash','Source']
] ;

FCKConfig.ToolbarSets["Small"] = [
	['Bold','Italic','Underline','StrikeThrough','Link','Unlink','Image','Flash','OrderedList','UnorderedList','Table']
] ;//used by test ? exercice/feedback.php 
////

///// admin tools /////

//Edit platform home page
FCKConfig.ToolbarSets["EditHomePage"] = [
	['NewPage','Templates','Save','Print','PageBreak','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll','-','Find'],
	['Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Smiley','SpecialChar','googlemaps'],
	['FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['Source']
] ;

//Insert or Edit a page link in platform home page
FCKConfig.ToolbarSets["LinksHomePage"] = [
	['NewPage','Templates','Save','Print','PageBreak','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll','-','Find'],
	['Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Smiley','SpecialChar','googlemaps'],
	['FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['Source']
] ;

//System Announcements
FCKConfig.ToolbarSets["SystemAnnouncements"] = [
	['NewPage','FitWindow','-','Undo','Redo','-','SelectAll'],
	['Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Smiley','SpecialChar'],
	['FontName','FontSize'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','TextColor','BGColor'],
	['Source']
] ;

//Global Agenda
FCKConfig.ToolbarSets["GlobalAgenda"] = [
	['FitWindow','-','PasteWord','-','Undo','Redo'],
	['Link','Unlink'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','SpecialChar','googlemaps'],
	['FontName','FontSize'],
	['Bold','Italic','Underline'],
	['OrderedList','UnorderedList','-','TextColor','BGColor'],
	['Source']
] ;

//Admin Templates
FCKConfig.ToolbarSets["AdminTemplates"] = [
	['NewPage','Templates','Save','Print','PageBreak','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll','-','Find'],
	['Bold','Italic','Underline'],
	['Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Smiley','SpecialChar','googlemaps'],
	['FontFormat','FontName','FontSize'],
	['JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['Source']
] ;

//FAQ
FCKConfig.ToolbarSets["FAQ"] = [
	['Save','Preview','Source']
];

///// users tools /////

//My Profile (Optional fields)
FCKConfig.ToolbarSets["Profil"] = [
	['FitWindow','-','PasteWord','-','Undo','Redo'],							   
	['Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Smiley'],
	'/',
	['FontName','FontSize'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','-','OrderedList','UnorderedList','-','TextColor','BGColor'],
	['Source']
] ;

//Messages
FCKConfig.ToolbarSets["Messages"] = [
	['FitWindow','Undo','Redo'],
	['Link','Unlink'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Smiley','googlemaps'],
	['Bold','Italic','Underline'],
	['OrderedList','UnorderedList','-','Blockquote','-','TextColor'],
	['ShowBlocks']
] ;

///// course tools /////

//Course introduction
FCKConfig.ToolbarSets["Introduction"] = [
	['NewPage','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll'],
	['Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','SpecialChar'],
	['OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	'-',['Source'],'/',
	['Style','FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','JustifyRight']
] ;

//Agenda
FCKConfig.ToolbarSets["Agenda"] = [
	['Save','FitWindow','PasteWord','-','Undo','Redo'],
    ['Link','Unlink','Anchor'],
    ['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
    ['Table','SpecialChar'],
    '/',    
    ['Style','FontFormat','FontName','FontSize'],
    ['Bold','Italic','Underline'],
    ['JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
    ['Source']
] ;

FCKConfig.ToolbarSets["Agenda_Student"] = [
	['Save','FitWindow','PasteWord','-','Undo','Redo'],
    ['Link','Unlink','Anchor'],
    ['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
    ['Table','SpecialChar'],
    '/',    
    ['FontFormat','FontName','FontSize'],
    ['Bold','Italic','Underline'],
    ['JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
    ['ShowBlocks']
] ;

//Announcements
FCKConfig.ToolbarSets["Announcements"] = [
	['Save','FitWindow','PasteWord','-','Undo','Redo'],
    ['Link','Unlink','Anchor'],
    ['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
    ['Table','SpecialChar'],
    ['OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
    '-',['Source'],'/',
    ['Style','FontFormat','FontName','FontSize'],
    ['Bold','Italic','Underline'],
    ['JustifyLeft','JustifyCenter','JustifyRight']
] ;

FCKConfig.ToolbarSets["Announcements_Student"] = [
	['Save','FitWindow','PasteWord','-','Undo','Redo'],
    ['Link','Unlink','Anchor'],
    ['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
    ['Table','SpecialChar'],
    ['OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
    '/',    
    ['Style','FontFormat','FontName','FontSize'],
    ['Bold','Italic','Underline'],
    ['JustifyLeft','JustifyCenter','JustifyRight'],
	['ShowBlocks']
] ;

//Blog
FCKConfig.ToolbarSets["Blog"] = [
	['FitWindow','-','PasteWord','-','Undo','Redo'],
	['Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','googlemaps'],
	['FontName','FontSize'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','-','OrderedList','UnorderedList','-','TextColor','BGColor'],
	['Source']
] ;

FCKConfig.ToolbarSets["Blog_Student"] = [
	['FitWindow','-','PasteWord','-','Undo','Redo'],
	['Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','googlemaps'],
	['FontName','FontSize'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','-','OrderedList','UnorderedList','-','TextColor','BGColor'],
	['ShowBlocks']
] ;

FCKConfig.ToolbarSets["BlogComment"] = [
	['FitWindow','-','PasteWord','-','Undo','Redo'],
	['Link','Unlink'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','googlemaps'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','-','OrderedList','UnorderedList','-','TextColor','BGColor'],
	['Source']
] ;

FCKConfig.ToolbarSets["BlogComment_Student"] = [
	['FitWindow','-','PasteWord','-','Undo','Redo'],
	['Link','Unlink'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','googlemaps'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','-','OrderedList','UnorderedList','-','TextColor','BGColor'],
	['ShowBlocks']
] ;

//Course Description
FCKConfig.ToolbarSets["CourseDescription"] = [
	['NewPage','Save','FitWindow','PasteWord','-','Undo','Redo'],
	['Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','SpecialChar'],
	['OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	'/',	
	['Style','FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','JustifyRight'],
	['Source']
] ;

//Documents
FCKConfig.ToolbarSets["Documents"] = [
	['Save','FitWindow','PasteWord','-','Undo','Redo'],
	['Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','SpecialChar'],
	['Outdent','Indent','-','TextColor','BGColor','-','OrderedList','UnorderedList'],
	'-',['Source'],'/',	
	['Style','FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','JustifyRight']
] ;

FCKConfig.ToolbarSets["Documents_Student"] = [
	['Save','FitWindow','PasteWord','-','Undo','Redo'],
	['Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','SpecialChar'],
	['Outdent','Indent','-','TextColor','BGColor','-','OrderedList','UnorderedList'],
	'/',	
	['Style','FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','JustifyRight'],
	['ShowBlocks']
];

//Forum
FCKConfig.ToolbarSets["ForumLight"] = [
	['Bold','Italic','Underline','StrikeThrough']
] ;

FCKConfig.ToolbarSets["Forum"] = [
    ['Save','FitWindow','PasteWord','-','Undo','Redo'],
    ['Link','Unlink','Anchor'],
    ['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
    ['Table','SpecialChar'],
    ['OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
    '/',
    ['Style','FontFormat','FontName','FontSize'],
    ['Bold','Italic','Underline'],
    ['JustifyLeft','JustifyCenter','JustifyRight'],
    ['Source']
];

FCKConfig.ToolbarSets["Forum_Student"] = [
	['Save','FitWindow','PasteWord','-','Undo','Redo'],
    ['Link','Unlink','Anchor'],
    ['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
    ['Table','SpecialChar'],
    ['OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
    '/',    
    ['Style','FontFormat','FontName','FontSize'],
    ['Bold','Italic','Underline'],
    ['JustifyLeft','JustifyCenter','JustifyRight'],
	['ShowBlocks']
] ;

//Glossary
FCKConfig.ToolbarSets["Glossary"] = [
	['Save','FitWindow','PasteWord','-','Undo','Redo'],
    ['Link','Unlink','Anchor'],
    ['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
    ['Table','SpecialChar'],
    ['OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
    '-',['Source'],'/',
    ['Style','FontFormat','FontName','FontSize'],
    ['Bold','Italic','Underline'],
    ['JustifyLeft','JustifyCenter','JustifyRight']
] ;

//Learning Path
FCKConfig.ToolbarSets["LearnPath"] = [
	['PasteWord','-','Undo','Redo'],
	['Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','SpecialChar'],
	['Outdent','Indent','-','TextColor','BGColor','-','OrderedList','UnorderedList'],
	'/',	
	['Style','FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline'],
	['JustifyLeft','JustifyCenter','JustifyRight'],
	['Source']
] ;//save, FitWindow don't run well here

FCKConfig.ToolbarSets["CommentLearningPath"] = [
	['Link','Unlink','Bold','Italic','TextColor','BGColor','Source']
] ;

//Notebook
FCKConfig.ToolbarSets["Notebook"] = [
	['Save','FitWindow','-','PasteWord','-','Undo','Redo'],
    ['Link','Unlink','Anchor'],
    ['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
    ['Table','SpecialChar'],
    ['OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
    '-',['Source'],'/',
    ['Style','FontFormat','FontName','FontSize'],
    ['Bold','Italic','Underline'],
    ['JustifyLeft','JustifyCenter','JustifyRight']
] ;

FCKConfig.ToolbarSets["Notebook_Student"] = [
	['Save','FitWindow','-','PasteWord','-','Undo','Redo'],
    ['Link','Unlink','Anchor'],
    ['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
    ['Table','SpecialChar'],
    ['OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
    '/',    
    ['Style','FontFormat','FontName','FontSize'],
    ['Bold','Italic','Underline'],
    ['JustifyLeft','JustifyCenter','JustifyRight'],    
	['ShowBlocks']
] ;

//Survey
FCKConfig.ToolbarSets["Survey"] = [
	['FitWindow'],
	['Link','Unlink'],
	['Image'],
	['Table'],
	['FontSize'],
	['Bold','Italic'],
	['OrderedList','UnorderedList','-','TextColor'],
	['Source']
] ;

//Test
FCKConfig.ToolbarSets["TestDescription"] = [
	['FitWindow','-','PasteWord','-','Undo','Redo'],
    ['Link','Unlink','Anchor'],
    ['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
    ['Table','SpecialChar'],
    ['OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
    '/',    
    ['Style','FontFormat','FontName','FontSize'],
    ['Bold','Italic','Underline'],
    ['JustifyLeft','JustifyCenter','JustifyRight'],
    ['Source']
] ;

FCKConfig.ToolbarSets["QuestionDescription"] = [
	['FitWindow','-','PasteWord','-','Undo','Redo'],
    ['Link','Unlink'],
    ['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
    ['Table','SpecialChar'],
    ['OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
    '/',    
    ['Style','FontFormat','FontName','FontSize'],
    ['Bold','Italic','Underline'],
    ['JustifyLeft','JustifyCenter','JustifyRight'],
    ['Source']
] ;

FCKConfig.ToolbarSets["Answer"] = [
    ['FitWindow','Bold','Image','Link','PasteWord','MP3','Table','Subscript','Superscript','Source']	
] ;

FCKConfig.ToolbarSets["FreeAnswer"] = [
    ['FitWindow','Bold','Image','Link','PasteWord','MP3','Table','Subscript','Superscript','ShowBlocks']	
] ;

FCKConfig.ToolbarSets["CommentAnswers"] = [
	['Link','Unlink','Bold','Italic','TextColor','BGColor']
] ;


//Wiki
FCKConfig.ToolbarSets["Wiki"] = [
	['NewPage','Templates','Save','PageBreak','Preview','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll','-','Find'],
	['Wikilink','Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Smiley','SpecialChar','googlemaps'],
	['FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline'],
	['Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['Source']
] ;

FCKConfig.ToolbarSets["Wiki_Student"] = [
	['NewPage','Save','PageBreak','Preview','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll','-','Find'],
	['Wikilink','Link','Unlink','Anchor'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Smiley','SpecialChar','googlemaps'],
	['FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline'],
	['Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['ShowBlocks']
] ;

//Gradebook
FCKConfig.ToolbarSets["Gradebook"] = [
    ['Save','FitWindow','-','PasteWord','-','Undo','Redo'],
    ['Link','Unlink','Anchor'],
    ['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
    ['Table','SpecialChar'],
    ['OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
    '/',    
    ['Style','FontFormat','FontName','FontSize'],
    ['Bold','Italic','Underline'],
    ['Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
    ['Source']
] ;

/*
 * Toolbar drop-down lists customizations.
 */

// Reduction of the format list.
FCKConfig.FontFormats	= 'p;h1;h2;h3;h4;h5' ;


/*
 * Helper variables.
 */

// At this moment of loading editor's javascripts, the setting FCKConfig.AdvancedFileManager
// has not been read yet from the php-integration file. We are able to detect which file manager
// will be used in another way. The following property has pure boolean type: true/false.
FCK.AdvancedFileManager = FCKConfig.PageConfig.AdvancedFileManager ;

var sOtherPluginPath = FCKConfig.BasePath.substr(0, FCKConfig.BasePath.length - 7) + 'editor/plugins/' ;


/*
 * Plugins.
 * If you want to add a non-existing language code in some of the pligin
 * declarations, it is mandatory a corresponding language file to be opened
 * in the plugin's "lang" directory.
 */

// The "customizations" plugin modifies some internal functionalities of the editor.
// It should be loaded before the other plugins.
FCKConfig.Plugins.Add('customizations', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// These plugins improve table-operations.
FCKConfig.Plugins.Add('dragresizetable') ;
FCKConfig.Plugins.Add('tablecommands') ;

// Audio files insertion.
FCKConfig.Plugins.Add('MP3', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// Another dialog for insertion audio files.
// If you wish to use it, disable the "MP3" plugin first.
//FCKConfig.Plugins.Add('audio', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// A specialised browser/editor for images.
if (!FCK.AdvancedFileManager)
{
	// It is not needed in the advanced file manager mode.
	// The laanguage sub-system of the Image Manager is different.
	// There is no need available languages to be enumerated.
	FCKConfig.Plugins.Add('ImageManager') ;
}

// This is the old flash plugin. Now the editor has a built-in flash dialog.
//FCKConfig.Plugins.Add('Flash', 'en') ;

// Embeding video files.
FCKConfig.Plugins.Add('fckEmbedMovies', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh');

// flv video files insertion.
FCKConfig.Plugins.Add('flvPlayer', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// Video streams insertion, YouTube service.
FCKConfig.Plugins.Add('youtube', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// Digital maps insertion, GoogleMaps service.
FCKConfig.Plugins.Add('googlemaps', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;
// This key is for http://localhost. You must get one for each server where you want to use the plugin,
// just get the key for free here after agreeing to the Terms of Use of the GoogleMaps API:
// http://www.google.com/apis/maps/signup.html. // If you leave an empty string then the toolbar icon won't be shown.
FCKConfig.GoogleMaps_Key = 'ABQIAAAAlXu5Pw6DFAUgqM2wQn01gxT2yXp_ZAY8_ufC3CFXhHIE1NvwkxSy5hTGQdsosYD3dz6faZHVrO-02A' ;

// Mathematical formulas insertion.
// In order the "mimetex" plugin to work prpoperly, preliminary changes
// in your server configuration have to be done. See the installation guide.
FCKConfig.Plugins.Add('mimetex', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// Wiki-formatted links insertion.
FCKConfig.Plugins.Add('wikilink', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// A dialog for assigning hyperlinks to specified image areas.
FCKConfig.Plugins.Add('imgmap', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh');


/*
 * Settings for browsing the server.
 */

// For all acceptable types of resources (files).

// true/false to enable/disable the browser.
FCKConfig.LinkBrowser = true ;

// Browser window sizes.
FCKConfig.LinkBrowserWindowWidth	= 782 ;
FCKConfig.LinkBrowserWindowHeight	= 490 ;

// For images.

// true/false to enable/disable the browser.
FCKConfig.ImageBrowser = true ;

// Browser window sizes.
FCKConfig.ImageBrowserWindowWidth   = 782 ;
FCKConfig.ImageBrowserWindowHeight  = 490 ;

// Upload path for the Image manager. Leave it empty.
FCKConfig.IMUploadPath = '' ;

// For flash objects.

// true/false to enable/disable the browser.
FCKConfig.FlashBrowser = true ;

// Browser window sizes.
FCKConfig.FlashBrowserWindowWidth   = 782 ;
FCKConfig.FlashBrowserWindowHeight  = 490 ;

// For audio files.

// true/false to enable/disable the browser.
FCKConfig.MP3Browser = true ;

// Browser window sizes.
FCKConfig.MP3BrowserWindowWidth     = 782 ;
FCKConfig.MP3BrowserWindowHeight    = 490 ;

// For video files.

// true/false to enable/disable the browser.
FCKConfig.VideoBrowser = true ;

// Browser window sizes.
FCKConfig.VideoBrowserWindowWidth   = 782 ;
FCKConfig.VideoBrowserWindowHeight  = 490 ;

// For video (flv) files.

// true/false to enable/disable the browser.
FCKConfig.MediaBrowser = true ;

// Browser window sizes.
FCKConfig.MediaBrowserWindowWidth   = 782 ;
FCKConfig.MediaBrowserWindowHeight  = 490 ;


/*
 * Settings for direct uploads on the server, without using browsers.
 * Some of the editor's dialogs have quick-upload tabs for this purpose.
 */

// For all acceptable types of resources (files).

// true/false to enable/disable the quick-upload tab.
FCKConfig.LinkUpload = true ;

// To be moved in the php-integration file.
FCKConfig.LinkUploadAllowedExtensions	= ".(7z|aiff|asf|avi|bmp|csv|doc|fla|flv|gif|gz|gzip|jpeg|jpg|mid|mov|mp3|mp4|mpc|mpeg|mpg|ods|odt|pdf|png|ppt|pxd|qt|ram|rar|rm|rmi|rmvb|rtf|sdc|sitd|swf|sxc|sxw|tar|tgz|tif|tiff|txt|vsd|wav|wma|wmv|xls|xml|zip)$" ;			// empty for all
FCKConfig.LinkUploadDeniedExtensions	= "" ;

// For images.

// true/false to enable/disable the quick-upload tab.
FCKConfig.ImageUpload = true ;

// To be moved in the php-integration file.
FCKConfig.ImageUploadAllowedExtensions	= ".(jpg|gif|jpeg|png|bmp)$" ;
FCKConfig.ImageUploadDeniedExtensions	= "" ;

// For flash objects.

// true/false to enable/disable the quick-upload tab.
FCKConfig.FlashUpload = true ;

// To be moved in the php-integration file.
FCKConfig.FlashUploadAllowedExtensions	= ".(swf)$" ;
FCKConfig.FlashUploadDeniedExtensions	= "" ;

// For audio files.

// true/false to enable/disable the quick-upload tab.
FCKConfig.MP3Upload = true ;

// To be moved in the php-integration file.
FCKConfig.MP3UploadAllowedExtensions	= ".(mp3)$" ;
FCKConfig.MP3UploadDeniedExtensions	= "" ;

// For video files.

// true/false to enable/disable the quick-upload tab.
FCKConfig.VideoUpload = true ;

// To be moved in the php-integration file.
FCKConfig.VideoUploadAllowedExtensions	= ".(mpg|mpeg|mp4|avi|wmv|mov|asf)$" ;
FCKConfig.VideoUploadDeniedExtensions	= "" ;

// For video (flv) files.

// true/false to enable/disable the quick-upload tab.
FCKConfig.MediaUpload = true ;

// To be moved in the php-integration file.
FCKConfig.MediaUploadAllowedExtensions	= ".(flv)$" ;
FCKConfig.MediaUploadDeniedExtensions	= "" ;


/*
 * Alternative settings for the advanced file manager mode.
 */

// If you wish to alter some of the settings above and to make them
// specific for the advanced file manager mode, you may do this within
// the following "if" block.
if (FCK.AdvancedFileManager)
{
	FCKConfig.LinkUpload = false ;
	FCKConfig.ImageUpload = false ;
	FCKConfig.MP3Upload = false ;
	//FCKConfig.FlashUpload = false ; //See plugin
}


/*
 * Other settings.
 */

FCKConfig.UserStatus = 'teacher' ;
