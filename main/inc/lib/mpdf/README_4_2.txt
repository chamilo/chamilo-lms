===========================
mPDF v4.2   (27/01/2010)
===========================

Bug fixes
---------
- empty variable (undefined var, false, null, array() etc.) sent to WriteHTML produced error message "Invalid UTF-8"
- CJK in tables when not using CJK (utf-8-s) autosized very small as characters did not word-wrap
- parsing stylesheets: background image not recognised if containbed uppercase characters in file name
- "double" border on table used white between the lines instead of current background colour
- mPDFI: template documents overwriting HTML headers
- $this->shrink_tables_to_fit = 0 or false caused fatal errors
- background color or images not printing correctly when breaking across pages
- background not printed for List inside a block element
- columns starting near end of page with no room for a line triggering column change (resulting in text misplaced) not page break
- table cell not calculating cell height correctly when "orphan" characters (;:,.?! etc.) at end of line
- table breaking page in column 2 when col 1 is rowspan'ned
- margin-collapse at top of page not working if bookmark/annotation/indexentry/toc
- column break triggered by HR triggering a second column break
- an empty 'position:fixed' element with no/auto width or height caused fatal error
- mPDFI: function Overwrite (to change text in existing PDF) - fatal error if using with encrypted file

Bug - not fixed - see below
- WriteHTML('',2) with '2' parameter not recognising 'margin-collapse:collapse' for DIVs or 'line-height' set in default CSS 'BODY'




New or Updated Files
--------------------
mpdf.php
classes/gif.php
classes/indic.php
compress.php
config.php
config_cp.php
config_fonts.php
mpdf.css
includes/sub_core.php
mpdfi/mpdfi.php
unifont/ar_k_001.uni2gn.php
All files in new folder: /progress/*.*

NEW FOLDER /tmp/ required with read/write permissions - used for temporary image files or progress bars

New fonts: zn_hannom_a and unBatang_0613 available as CJK font pack
	zn_hannom_a - contains all characters in SJIS, BIG-5, GBK, and HKCSS codepages (Japanes & Chinese)
		(except greek and cyrillic characters, and HKCS > U+x20000;)
	unbatang_0613 - all characters in UHC codepage (Korean)



Changes to configuration files
==============================
config_cp.php
-------------
Mainly just tidied up, and:
default:  $cp = "UTF-8"; $spacing = "";  break;	// Don't need to set unifonts - will make all available if omitted/left blank
Default spacing set to '' (i.e. mixed character/word)
spacing=C removed for Vietnamese (?why there) to allow spacing as for any european text
spacing=C added to Thai (should have been there all along)

config.php
----------
$defaultCSS changed to make appearance closer to that of browsers:
img { margin: 0; vertical-align: baseline; }
table { margin: 0; }
textarea { vertical-align: text-bottom; }

(See also notes on line-height)

New Configuration variables in 4.2:
$this->useSubstitutionsMB = false;	// Substitute missing characters in UTF-8(multibyte) documents - from core fonts
$this->falseBoldWeight = 5;		// Weight for bold text when using an artificial (outline) bold; value 0 (off) - 10 (rec. max)
$this->collapseBlockMargins = true; 	// Allows top and bottom margins to collapse between block elements
$this->progressBar = false;		// Shows progress-bars whilst generating file
$this->normalLineheight = 1.33;		// Value used for line-height when CSS specified as 'normal' (default)
// When writing a block element with position:fixed and overflow:auto, mPDF scales it down to fit in the space
// by repeatedly rewriting it and making adjustments. These values give the adjustments used, depending how far out
// the previous guess was. The higher the number, the quicker it will finish, but the less accurate the fit may be.
// FPR1 is for coarse adjustments, and FPR4 for fine adjustments when it is getting closer.
$this->incrementFPR1 = 10;
$this->incrementFPR2 = 20;
$this->incrementFPR3 = 30;
$this->incrementFPR4 = 50;


mpdf.css
--------
Now contains (commented out) lines to return behaviour to pre-4.2 behaviour:
img { margin: 0.83em 0; vertical-align: bottom; }
table { margin: 0.5em; }
textarea { vertical-align: top; }


Font updates:
=============
Indic Tamil numeral for Zero missing - converted to standard zero 0 (in classes/indic.php)
ar_k_001.uni2gn.php - reference to small 'z' missing - now added (works with subsets but not with full font)



WriteHTML($html,2)
==================
WriteHTML($html,2) i.e. with the ,2 did not set BODY CSS - this was unintentional, and has been changed in 4.2
Line-height and margin-collapse were therefore not cascaded through the document; the line-height defaulted to 1.2,
and margin-collapse (which collapses top and bottom margins at the top of pages) was not enabled.

If you used ",2" and want to keep layout:
- Change $this->normalLineheight = 1.2; in config.php
- Change defaultCSS: 'MARGIN-COLLAPSE' => 'none', in config.php

NB also $this->collapseBlockMargins = false;	NB This does between block elements

NB You cannot now reset default font during document by redefining vars e.g. $mpdf->default_font = 'xxx'

Now WriteHTML(,2) - does NOT read metatags, <style> or stylesheets from HTML, <html> or <body> inline CSS
	- DOES use defaultCSS, and stored CSS and stored cascading CSS
	- DOES use the above to overwrite defaultfont, defaultfontsize
	- DOES use the above to set (cascading) margin-collapse (pagetops) and line-height from BODY
	- therefore problem trying to set default_font, default_lineheight_correction etc programmatically
	- use SetDefaultFont(), SetDefaultFontSize() - (now altered to update $defaultCSS and $CSS['BODY'][''])
	- new SetDefaultBodyCSS($property, $value) - use to update [BODY] line-height etc. during program e.g. columns example



NEW FEATURES
============
PROGRESS BAR
------------
You can now show a progress bar whilst mPDF generates the file.
It is not recommended for regular use - it loads a separate HTML page to the browser, and may slow things down.
May be useful if the end-user is waiting a long time, or for development purposes.
1) You need to define _MPDF_URI as a relative path or URI (NOT a relative file system path)
	- call this in your script before instantiating the class: new mPDF()
2) Call $mpdf->StartProgressBarOutput(0|1|2); in your script before using WriteHTML(), OR
	- or set $this->progressBar = 0|1|2; in config.php file (0 off, 1 simple, 2 advanced)
	StartProgressBarOutput(2) shows a more advanced/complex set of progress bars (default = 1)

Note on defined Paths:
_MPDF_PATH must be a relative or absolute file system path
_MPDF_URI must be a relative or absolute URI (seen from the browser's point of view)
mPDF will usually be able to automatically set _MPDF_PATH if you do not define it, but it cannot set _MPDF_URI

Example:
Your script is at: http://subdomain.mydomain.com/script.php  = /homepages/123456/htdocs/public/subdomain/script.php
Your MPDF file is: http://www.mydomain.com/mpdf41/mpdf.php  = /homepages/123456/htdocs/public/mpdf41/mpdf.php
_MPDF_PATH (from script.php to mpdf folder) - can be:
	../mpdf41/	or
	/homepages/123456/htdocs/public/mpdf41/
	It cannot be http://www.mydomain.com/mpdf41/
	
_MPDF_URI must be:
	http://www.mydomain.com/mpdf41/
	It cannot be a relative path - because you can't have ../ from the subdomain URI

IF _MPDF_URI is not defined - mPDF silently ignores and leaves out the progress bar (or if debug gives warning)


LINE-HEIGHT
-----------
The handling of line-height has been generally overhauled. Also some anomalies have been unearthed, so the layout of 
your documents may change with v4.2.
The most significant change is that prior to v4.2, line-height was inherited as a factor of the fontsize i.e. 
<div id="1" style="font-size: 14pt; line-height: 28pt;"><div id="2" style="font-size: 28pt;">
When the line-height was applied to div 1, it was calculated to be 2 x the fontsize - this was the value inerited by div 2
which therefore set a line-height of 2 x 28 = 56pt.

Inheritance now follows the CSS2 recommendation:
    * normal is inherited as "normal"
    * 1.2 is inherited as a factor
    * 120% is converted to an actual value and then inherited as the computed value
    * em is converted to an actual value and then inherited as the computed value
    * px pt mm are inherited as fixed values

The value used for 'normal' is now defined in config.php by $normalLineheight (default 1.33) rather than $default_lineheight_correction (1.2)
The defaultCSS BODY>>LINE-HEIGHT is now set as 'normal' rather than 1.33
Block-level elements and lists use $normalLineheight as default now (unless overridden by CSS style)
Lists inherit from containing block-level element, and lists inherit from containing lists.
Tables do not inherit from containing block (as per browsers).
Table default line-height is set by $defaultCSS in config.php
So the $defaultCSS 'BODY' line-height sets for all except tables.
Line-height can be set on UL,OL at every level, but not on LI items
Line-height can only be set on top-level table (not nested tables, nor table cells TD/TH).
Textarea - does not support CSS line-height - can change for whole document using $this->textarea_lineheight in config.php

Algorithm used for line-height:
If line-height set on a block-level element is an ABSOLUTE value (including %, em) it is fixed for the line unless:
	A font size on the line (e.g. in a span) is greater than the computed line-height for that line
	An image has a height greater than the computed line-height for that line
	e.g. <div style="font-size: 12pt; line-height: 2em;">Hallo <span style="font-size: 26pt">World...
	- line-height will increase from 24pt (2em x 12pt) to 26pt
  The vertical positioning of the text baseline will remain equally spaced unless:
	images exceed the initial line-height.
	large fontsize >= 0.8 x the initial line-height.
	e.g. <div style="font-size: 10pt; line-height: 2em;">Hallo <span style="font-size: 18pt">World...
  If line-height is set to be less than the fontsize, this will be respected UNLESS a larger fontsize (eg in span) or an image is included
	on the line, in which case the line-height is expanded to fit -
  LISTS - will always expand to the largest fontsize (including the bullet or number).

If line-height is a number/factor: 
	Line height will be based on the largest fontsize (x factor) or image height (NOT x factor) on the line.
	The vertical positioning of the text baseline will be adjusted so the centre-line runs through the 
		middle of the largest font-size on the line.

This gives results which roughly match browsers, but note that not even IE8 and FF3 are exactly the same in detail.

A line-height cannot be defined as a number (factor) less than 1.0 - it will be set as 1.0
Absolute values that are less than 1 (including e.g. 80%) are respected - unless a larger fontsize or an image on the line.

Line-height - Backwards compatability?
--------------------------------------
The default settings should generally give the same results as pre-4.2 version, unless you specified absolute line-heights in your CSS.
If you did, the only way is to go back and edit your CSS stylesheets.

If you use WriteHTML($html,2) with the '2' parameter - see notes above.

If you set $mpdf->default_lineheight_correction programmatically - 
The old example file for columns used WriteHTML('',2) - see below - and
	$mpdf->default_lineheight_correction = 1.1;
This no longer works because the defaultCSS value for line-height overrides this.
You can use:
	$mpdf->SetDefaultBodyCSS('line-height', 1.1);	// A new function


MARGIN-COLLAPSE BETWEEN BLOCK ELEMENTS
--------------------------------------
mPDF has always allowed margins to be collapsed at the top and bottom of pages (although see notes on WruiteHTML('',2) )
This is specified by the custom CSS property "margin-collapse: collapse"

mPDF 4.2 also allows margins to collapse between block elements on the page. This is the default behaviour in browsers,
and has been enabled in mPDF by default.

NB IMPORTANT - THIS MAY CHANGE THE APPEARANCE OF YOUR DOCUMENTS *****

A configuration variable in config.php enables/disables this (default=true):
$this->collapseBlockMargins = true; 	// mPDF 4.2 Allows top and bottom margins to collapse between block elements

Change this to false if you wish to retain the layout of your previous files.

Margin collapse works between lists, tables and all standard block-level elements (DIV, P, H1-6 etc.)
NB Firefox does not collapse table margins, but IE8 does.




TABLE RESIZING
--------------
mPDF attempts to layout tables according to HTML and CSS specifications. However, because of
the difference between screen and paged media, mPDF resizes tables when necessary to make
them fit the page. This will happen if the minimum table-width is greater than the page-width.
Minimum table-width is defined as the minimum width to accomodate the longest word in each
column i.e. words will never be split.
This resizing (minimum-width) can be disabled using a custom CSS property "overflow" on the
TABLE tag. There are 4 options:
<table style="overflow: auto"> (this is the default, using resizing)
<table style="overflow: visible"> (disables resizing, but allows overflow to show)
<table style="overflow: hidden"> (disables resizing, and hides/clips any overflow)
<table style="overflow: wrap"> (forces words to break as necessary)

NB You cannot disable automatic resizing if a row-height is greater than the page-height.

This only works on the top-level table (i.e. ignored on "nested" tables).
overflow: visible will not extend the containing block element.
Ignored on rotated tables.
Ignored if columns are being used.


ARTIFICIAL BOLD & ITALIC
------------------------
mPDF will create "artificial" bold & italic font styles if they are not available as separate
font files.
A configuration variable in config.php can vary how bold is bold:
$this->falseBoldWeight = 5;	// Weight for bold text when using an artificial (outline) bold; value 0 (off) - 10 (rec. max)


CSS "DOUBLE" BORDER-STYLE
-------------------------
CSS support for "double" border on block elements. NB Tables support the full range of CSS values for 
border-style; block elements now support just solid and double.


CHARACTER SUBSTITUTION IN UTF-8
-------------------------------
Character substitution is used in codepaged PDF files (e.g. win-1252) to enable characters which exist in the core Adobe fonts
(including symbols and zapfdingbats) to be displayed when they do not exist in the font currently being used.
Character substitution in UTF-8 files was possible but erratic prior to v4.0 - then disabled in v4.0

v4.2 introduces a new implementation of character substitution. When enabled, any characters which do not exist in the current
font - but which do exist in the document's default font - will be substituted.
There will be a time penalty for using this, as each character is inspected to check if it exists in the current font.
This may be useful for some of the specialist fonts such as arabic, indic and CJK.

A configuration variable in config.php enables/disables this (default=false):
$this->useSubstitutionsMB = false;		// Substitute missing characters in UTF-8(multibyte) documents - from core font


IMAGES
------
Image handling has been completely overhauled (again!) in 4.2
(See the discussion: http://mpdf.bpm1.com/forum/comments.php?DiscussionID=180&page=1#Item_6)

IMPORTANT - a new folder is required  [your_mpdf_folder]/tmp/ with read/write permissions.

PNG files (unless with alpha channel or interlaced), JPG and WMF images are read directly and are most efficient on resources.
GIF files use the GD library - if available - this is quick, but can use enormous amount of memory for large files.
GIF files without the GD library are very, very slow - it is recommended to change the image type if at all possible.
PNG files with alpha channel or interlaced require the GD library, and can use large amoutns of memory.

Images generated by a script e.g. myimage.php should be handled exactly the same as a native image file type.

NB The 'compress' utility no longer does anything other than IMAGES or IMAGES-WMF


BACKGROUND-IMAGES
-----------------
Background-gradient and background-image can now co-exist (layers = bgcolor < gradient < image)
This works for BODY and also for block elements or tables.


IMAGE DATA FROM PHP
-------------------
A PHP variable containing image data can be passed directly to mPDF. You need to allocate the data to a class variable
(you can make any name up) e.g.
$mpdf->myvariable = file_get_contents('alpha.png');

The image can then be referred to by use of "var:varname" instead of a file name,
either in src="" or direct to Image() function e.g.

$html = '<img src="var:myvariable" />';
$mpdf->WriteHTML($html);
- OR -
$mpdf->Image('var: myvariable',0,0);



IMAGE - VERTICAL-ALIGN & MARGIN
-------------------------------
Vertical-alignment of images has been rewritten. All of the CSS properties are now supported:
top, bottom, middle, baseline, text-top, and text-bottom.

'baseline' is now set as the default value for images in defaultCSS (config.php)
and 'text-bottom' as the default for textarea. 

In-line images can now be individually aligned (vertically) i.e. different alignments can co-exist on one line.

The defaultCSS value for margin on images has been changed to 0. Prior to 4.2, the default values of margin (0.3-0.5em)
and vertical-align (bottom) could be used to approximately align the image with the text baseline.
The new default values should therefore not significantly change appearances in most cases.

These new values are consistent with most browsers.


IMAGE - PADDING
---------------
CSS property padding is now supported for images <IMG>. Default is set in defaultCSS to 0



CSS @PAGE SELECTOR
------------------
The functions AddPage() and TOCpagebreak() have an extra last parameter = $pagesel i.e. named @page selector
The @page can also be specified in:
<pagebreak page-selector="pagename"
<formfeed page-selector="pagename"
<tocpagebreak toc-page-selector="pagename" page-selector="pagename"

Different headers/footers can be specified on :first :left and :right pages.
"odd-header-name" etc is still recognised on @page or @page name (and takes priority over newer method "footer"/"header")
but new custom properties "header" and "footer" are recognised on ALL, i.e. @page :left

Other values are now recognised on :first, :left and :right selectors 
i.e. as well as header/footer, you can specify margins, backgrounds etc

One exception is margin-right and margin-left:
Left/right-margins must be the same for every page (of that @page name), or mirrored using $mpdf->mirrorMargins;
margin-right and margin-left are ignored when set on :left or :right selectors

See the example given in discussion forum: http://mpdf.bpm1.com/forum/comments.php?DiscussionID=177&page=1#Comment_658



LISTS IN TABLES
---------------
If using a UTF-8 document, and the current font contains the necessary characters, it will use disc, circle and square 
as bullets instead of a hyphen (-)



