<?php



$html = '
<style>
img { margin: 0.3em; }
table { border-collapse: collapse; margin-top: 0; text-align: center; }
td { padding: 0.5em; }
h1 { margin-bottom: 0; }
</style>
<h1>mPDF Images</h1>

<table>
<tr>
<td></td>
<td>GIF</td>
<td>JPG (RGB)</td>
<td>JPG (CMYK)</td>
<td>PNG</td>
<td>WMF</td>
</tr>
<tr>
<td>Image types</td>
<td><img style="vertical-align: top" src="tiger.gif" width="90" /></td>
<td><img style="vertical-align: top" src="tiger.jpg" width="90" /></td>
<td><img style="vertical-align: top" src="tigercmyk.jpg" width="90" /></td>
<td><img style="vertical-align: top" src="tiger.png" width="90" /></td>
<td><img style="vertical-align: top" src="tiger.wmf" width="90" /></td>
</tr>
<tr>
<td>Opacity 50% </td>
<td><img style="vertical-align: top; opacity: 0.5" src="tiger.gif" width="90" /></td>
<td><img style="vertical-align: top; opacity: 0.5" src="tiger.jpg" width="90" /></td>
<td><img style="vertical-align: top; opacity: 0.5" src="tigercmyk.jpg" width="90" /></td>
<td><img style="vertical-align: top; opacity: 0.5" src="tiger.png" width="90" /></td>
<td><img style="vertical-align: top; opacity: 0.5" src="tiger.wmf" width="90" /></td>
</tr>
</table>

<h4>Alpha channel</h4>
<table>
<tr>
<td>PNG</td>
<td><img style="vertical-align: top" src="alpha.png" width="90" /></td>
<td style="background-color:#FFCCFF; "><img style="vertical-align: top" src="alpha.png" width="90" /></td>
<td style="background-color:#FFFFCC;"><img style="vertical-align: top" src="alpha.png" width="90" /></td>
<td style="background-color:#CCFFFF;"><img style="vertical-align: top" src="alpha.png" width="90" /></td>
<td style="background-color:#CCFFFF; background: transparent url(\'bg.jpg\') repeat scroll right top;"><img style="vertical-align: top" src="alpha.png" width="90" /></td>
</tr>
</table>
<h4>Transparency</h4>
<table><tr>
<td>PNG</td>
<td style="background-color:#FFCCFF; "><img style="vertical-align: top" src="tiger24trns.png" width="90" /></td>
<td style="background-color:#FFFFCC;"><img style="vertical-align: top" src="tiger24trns.png" width="90" /></td>
<td style="background-color:#CCFFFF;"><img style="vertical-align: top" src="tiger24trns.png" width="90" /></td>
<td style="background-color:#CCFFFF; background: transparent url(\'bg.jpg\') repeat scroll right top;"><img style="vertical-align: top" src="tiger24trns.png" width="90" /></td>
</tr><tr>
<td>GIF</td>
<td style="background-color:#FFCCFF;"><img style="vertical-align: top" src="tiger8trns.gif" width="90" /></td>
<td style="background-color:#FFFFCC;"><img style="vertical-align: top" src="tiger8trns.gif" width="90" /></td>
<td style="background-color:#CCFFFF;"><img style="vertical-align: top" src="tiger8trns.gif" width="90" /></td>
<td style="background-color:#CCFFFF; background: transparent url(\'bg.jpg\') repeat scroll right top;"><img style="vertical-align: top" src="tiger8trns.gif" width="90" /></td>
</tr></table>


Images returned from tiger.php with mime-type header (not WMF)
<div>
GIF <img style="vertical-align: top" src="tiger.php?t=gif" width="90" />
JPG <img style="vertical-align: top" src="tiger.php?t=jpg" width="90" />
PNG <img style="vertical-align: top" src="tiger.php?t=png" width="90" />
</div>

<pagebreak />


<h3>Image Alignment</h3>
<div>When printing in-line images, the whole line is vertically aligned to to the biggest image. This includes the top/bottom margin of the image. In the original mPDF v1.x this was set to 0.2em; by chance this neatly aligned the text to look as though it was perfectly inline with the bottom or top of the image. The default setting in v2+ is 0.5em (to be consistent with HTML standards) and the alignment is not so good.<br />
This example sets a CSS style of img { margin: 0.3em; }
</div>
<br />

<div style="background-color:#CCFFFF;">
Images <img src="sunset.jpg" width="100" style="vertical-align: top;" />
top-aligned <img src="sunset.jpg" width="50" style="vertical-align: top;" />
with text <img src="sunset.jpg" width="150" style="vertical-align: top;" />
</div>
<br />
<div style="background-color:#CCFFFF;">
Images <img src="sunset.jpg" width="100" style="vertical-align: bottom;" />
bottom-aligned <img src="sunset.jpg" width="50" style="vertical-align: bottom;" />
with text <img src="sunset.jpg" width="150" style="vertical-align: bottom;" />
</div>

<h3>Image Border and padding</h3>
<img src="sunset.jpg" width="100" style="border:3px solid #44FF44; padding: 1em;" />


';
//==============================================================
//==============================================================
//==============================================================
include("../mpdf.php");

$mpdf=new mPDF(); 

$mpdf->useOnlyCoreFonts = true;	// false is default

$mpdf->WriteHTML($html);

$mpdf->Output();
exit;
//==============================================================
//==============================================================
//==============================================================
//==============================================================
//==============================================================


?>