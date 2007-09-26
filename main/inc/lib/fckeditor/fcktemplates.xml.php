<?php header('Content-Type: text/xml; charset=utf-8');

require_once('../../global.inc.php');
echo '<?xml version="1.0" encoding="utf-8" ?>';
$IMConfig['base_url'] = $_configuration['root_web'].'main/img/gallery/';

function loadCSS($css_name){
	$template_css = '<style type="text/css">'.file_get_contents(api_get_path(SYS_PATH).'main/css/'.$css_name.'/default.css').'</style>';
	$template_css=str_replace('images/',api_get_path(WEB_PATH).'main/css/'.$css_name.'/images/',$template_css);
	return $template_css;
}
$css = loadCSS(api_get_setting('stylesheets'));

?>
<Templates imagesBasePath="fck_template/images/">

	
	<Template title="Text page" image="Text">
		<Description>Theory, content section, chapter...</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">Text</font><br />
				    <br />
				
				    <table width="720" cellspacing="1" cellpadding="1" border="0" align="left" summary="">
				      <tbody>
				        <tr>
				          <td>
				            <span style="font-style: italic; font-weight: bold;">Tip</span> <span style="font-style: italic;">: this template is plain text divided in sections. All Dokeos templates can be edited.
				            Replace text by selecting it. This text is placed in a 720 pixels wide table cell. You can add more cells, divide the table in two colums etc. by clicking on the arrows and cross in a
				            circle icons on the borders of the table (this feature will work in Firefox and recent Internet Explorer browsers). For other templates, select the templates gallery at the top of your
				            WYSIWYG editor. You may want to add media (images, audio, video, flash animations). Select the corresponding buttons and add the media. You can click on an image to resize and right-click
				            on it for more options.</span><br />
				            <br />
				            <span style="font-weight: bold;">Second section<br />
				
				            <br /></span>Your text<br />
				            <br />
				            <span style="font-weight: bold;">Third section<br />
				            <br /></span>Your text<br />
				            <br />
				          </td>
				        </tr>
				
				      </tbody>
				    </table><br />
				    <br />
				    <br />
				    <br />
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Teacher explaining" image="Teacher_explaining">
		<Description>Mr Dokeos points to your content</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">Teacher explaining</font><br />
				    <br />
				
				    <table width="720" cellspacing="1" cellpadding="5" border="0" align="left" summary="">
				      <tbody>
				        <tr>
				          <td style="vertical-align: top;" colspan="2">
				            <span style="font-style: italic;"><span style="font-weight: bold;">Tip</span> : to replace the teacher mascot with another one, remove it then select the Images icon in the editor, enter
				            Gallery &gt; Mr Dokeos and choose a static or an animated Mr Dokeos. Alternatively : import your own gallery of mascots or order one at Dokeos.</span><br />
				          </td>
				
				        </tr>
				        <tr>
				          <td valign="top">
				            <span style="font-weight: bold;"><img width="250" height="250" align="bottom" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/mr_dokeos/anim_teaching.jpg" alt=
				            "anim_teaching.jpg" /><br /></span>
				          </td>
				          <td style="vertical-align: top;">
				            <font size="4"><span style="font-weight: bold;">The cell</span></font><br />
				            <br />
				
				            The cell is the structural and functional unit of all known living organisms. It is the simplest unit of an organism that is classified as living, and is sometimes called the building
				            block of life.<br />
				            <br />
				            Some organisms, such as bacteria, are unicellular (consist of a single cell). Other organisms, such as humans, are multicellular. (Humans have an estimated 100 trillion or 1014 cells; a
				            typical cell size is 10 µm; a typical cell mass is 1 nanogram.)<br />
				            <br />
				            <br />
				          </td>
				        </tr>
				      </tbody>
				
				    </table><br />
				    <br />
				    <br />
				    <br />
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Comparison" image="Comparison">
		<Description>2 columns text page</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">Comparison</font><br />
				    <br />
				    
				    <span style="font-style: italic; font-size: 15px; "><span style="font-weight: bold">Tip : </span>use this template to compare two sets of propositions.</span>
				    
				    <br /><br />
				    <table width="720" cellspacing="1" cellpadding="1" border="0" align="left" summary="">
				      <tbody>
				        <tr>
				          <td>
				            <span style="font-weight: bold;"><font size="4">Set 1</font><br /></span>
				
				            <ul>
				              <li>item 1
				              </li>
				              <li>item 2
				              </li>
				              <li>item 3
				              </li>
				              <li>item 4
				              </li>
				              <li>item 5
				              </li>
				
				            </ul><span style="font-weight: bold;"><br /></span><br />
				            <br />
				          </td>
				          <td style="vertical-align: top;">
				            <span style="font-weight: bold;"><font size="4">Set 1</font><br /></span>
				            <ul>
				              <li>item 1
				              </li>
				              <li>item 2
				              </li>
				
				              <li>item 3
				              </li>
				              <li>item 4
				              </li>
				              <li>item 5<img width="100" height="100" align="right" alt="pointing_left.jpg" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/mr_dokeos/pointing_left.jpg" />
				              </li>
				            </ul>
				          </td>
				        </tr>
				
				      </tbody>
				    </table><br />
				    <br />
				    <br />
				    <br />
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Diagram explained" image="Diagram_explained">
		<Description>Image on the left, comment on the right</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">A diagram explained</font><br />
				    <br />
				
				    <table width="720" cellspacing="1" cellpadding="5" border="0" align="left" summary="">
				      <tbody>
				        <tr>
				          <td style="vertical-align: top;" colspan="2">
				            <span style="font-style: italic;"><span style="font-weight: bold;">Tip</span> : to replace the image by yours, select it and click on the Delete key of your keyboard. Then open the image
				            gallery and upload your own diagram then select it to insert in the page. Image should not be bigger than 720x540pixels and must be a GIF, JPG or PNG format to comply with web standards.
				            To replace the text, just select with your mouse, then replace.</span><br />
				          </td>
				        </tr>
				
				        <tr>
				          <td>
				            <span style="font-weight: bold;"><img width="165" height="287" align="bottom" alt="anim_twostroke.gif" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/diagrams/animated/anim_twostroke.gif" /><br /></span>
				          </td>
				          <td style="vertical-align: top;">
				            Two-stroke cycle engines operate in two strokes, instead of the four strokes of the more common Otto cycle.<br />
				            <ol>
				              <li>
				
				                <span style="font-weight: bold;">Power/exhaust</span> : This stroke occurs immediately after the ignition of the charge. The piston is forced down. After a certain point, the top of
				                the piston passes the exhaust port, and most of the pressurized exhaust gases escape. As the piston continues down, it compresses the air/fuel/oil mixture in the crankcase. Once the
				                top of the piston passes the transfer port, the compressed charge enters the cylinder from the crankcase and any remaining exhaust is forced out.<br />
				              </li>
				              <li>
				                <span style="font-weight: bold;">Compression/intake</span> : The air/fuel/oil mixture has entered the cylinder, and the piston begins to move up. This compresses the charge in the
				                cylinder and draws a vacuum in the crankcase, pulling in more air, fuel, and oil from the carburetor. The compressed charge is ignited by the spark plug, and the cycle begins again.
				              </li>
				            </ol>In engines like the one described above, where some of the exhaust and intake charge are in the cylinder simultaneously the gasses are kept separate by careful timing and aiming of
				            the transfer ports such that the fresh gas has minimal contact with the exiting exhaust which it is pushing ahead of itself.
				          </td>
				
				        </tr>
				      </tbody>
				    </table><br />
				    <br />
				    <br />
				    <br />
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Image alone" image="Picture">
		<Description>Self-explaining diagram</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">Picture page</font><br />
				    <br />
				
				    <table width="720" cellspacing="1" cellpadding="1" border="0" align="left" summary="">
				      <tbody>
				        <tr>
				          <td>
				            <span style="font-style: italic;"><span style="font-weight: bold;">Tip</span> : in some pages, you want just a picture. Take it as self-explaining as possible and consider the default
				            sizing for a page in Dokeos is 720x540 to fit within a learning path under the LMS bars and beside the learning path table of contents. Consider using the same image as a page and as a
				            test support in the hotspots test type (see Dokeos tests tool).<br />
				            <br />
				            <img width="600" height="469" align="bottom" alt="piano.jpg" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/diagrams/piano.jpg" /><br />
				
				            <br />
				            <br /></span><span style="font-weight: bold;"><br /></span><span style="font-weight: bold;"><span style="font-weight: bold;"><br />
				            <br />
				            <br /></span></span>
				          </td>
				        </tr>
				      </tbody>
				    </table><br />
				    <br />
				
				    <br />
				    <br />
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Flash animation" image="Flash_animation_page">
		<Description>Animation + introduction text</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">Flash animation page</font><br />
				    <br />
				    <table width="720" cellspacing="1" cellpadding="1" border="0" align="left" summary="">
				
				      <tbody>
				        <tr>
				          <td>
				            <span style="font-style: italic;"><span style="font-weight: bold;">Tip</span> : to insert a Flash animation (whether simulation, desktop captivate movie etc.), select the Flash icon in
				            the editor, upload it from your PC, type the exact width and height of the animation (or leave default and adjust later by dragging the border of the animation zone. Then validate.<br />
				            <br /></span> The NMR signal observed following an initial excitation pulse (<span style="color: rgb(128, 0, 128); font-weight: bold;">purple in diagram</span>) decays with time due to
				            both spin-spin relaxation and any inhomogeneous effects which cause different spins to precess at different rates e.g. a distribution of chemical shifts or magnetic field gradients.
				            Relaxation leads to an irreversible loss of magnetisation (decoherence), but the inhomogeneous dephasing can be reversed by applying a 180° or inversion pulse (<span style=
				            "color: rgb(51, 153, 102); font-weight: bold;">green in diagram</span>) that inverts the magnetisation vectors.<br />
				
				            <br />
				          </td>
				        </tr>
				        <tr>
				          <td style="vertical-align: top;">
				            <span style="font-style: italic;"><embed width="722" height="332" menu="true" loop="true" play="true" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/flash/SpinEchoSequence.swf"
				            pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" /></span><br />
				          </td>
				        </tr>
				      </tbody>
				
				    </table><br />
				    <br />
				    <br />
				    <br />
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Video page" image="Video">
		<Description>On demand video + text</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">On demand video</font><br />
				    <br />
				    <table width="720" cellspacing="1" cellpadding="10" border="0" align="left" summary="">
				
				      <tbody>
				        <tr>
				          <td style="vertical-align: top;" colspan="2">
				            <span style="font-weight: bold; font-style: italic;">Tip</span> <span style="font-style: italic;">: to replace the video, click on its border then clikc on the Delete touch of your
				            keyboard &gt; open the Video menu of the editor and upload your own video in MPG, MOV or WMV format. Once in the page, drag its border to fit the video dimensions. For instance
				            320x240.</span><br />
				            <br />
				          </td>
				
				        </tr>
				        <tr>
				          <td valign="top">
				          	<?php
				          	if(strpos($_SERVER['HTTP_USER_AGENT'],'Gecko')!==false)
				          	{
				          	?>
				            	<img src="<?php echo api_get_path(REL_PATH) ?>main/inc/lib/fckeditor/editor/css/images/flv.gif?flv=<?php echo api_get_path(WEB_CODE_PATH) ?>default_course_document/video/example.flv&endflv" />
				          	<?php
				          	}
				          	else
				          	{
				          	?>
				          	<object type="application/x-shockwave-flash" data="<?php echo api_get_path(REL_PATH) ?>main/inc/lib/flv_player/player_flv_mini.swf" height="240" width="320">
					          		<param name="movie" value="<?php echo api_get_path(REL_PATH) ?>main/inc/lib/flv_player/player_flv_mini.swf" />
					          		<param name="FlashVars" value="flv=<?php echo api_get_path(REL_PATH) ?>main/default_course_document/video/example.flv&autoplay=1&width=320&amp;height=240" />
					          </object>
					          <style type="text/css">body{}</style>
				          	
				          	<?php
				          	}
				          	?>
				          </td>
				          <td style="vertical-align: top;">
				            <span class="tablehead"><font size="4"><span style="font-weight: bold;">Excerpt from Marc Shuttleworth's keynote</span></font><br />
				            <br />
				
				            Main topics of the conference are :<br /></span>
				            <ul>
				              <li>
				                <span class="tablehead">Free software creates <span style="font-weight: bold;">empowerment</span> for countries, business and individuals</span>
				              </li>
				              <li>
				
				                <span class="tablehead">This modifies not only the projects bu also the <span style="font-weight: bold;">processes</span> through a series of challenges</span>
				              </li>
				              <li>
				                <span class="tablehead">demand increases for open source qualified programmers and this is an opportunity for <span style="font-weight: bold;">developing countries</span></span>
				              </li>
				              <li>
				
				                <span class="tablehead">but it requires <span style="font-weight: bold;">adapted training</span> for youg people in the developing countries</span>
				              </li>
				              <li>
				                <span class="tablehead">OSS accelerates other domains of litteracy and <span style="font-weight: bold;">community building</span> in 3d world countries and invites businesses to
				                collaborate with each other</span>
				              </li>
				
				            </ul>
				          </td>
				        </tr>
				      </tbody>
				    </table><br />
				    <br />
				    <br />
				    <br />
				    <style type="text/css">body{}</style><!-- to fix a strange bug appearing with firefox when editing this template -->
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="table page" image="Table">
		<Description>Spreadsheet-like page</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">A table<br><br></font>
				    
				    <span style="font-style: italic; font-size: 15px; "><span style="font-weight: bold">Tip</span> : to edit this table, click on the small arrows and cross-in-a-circle icons on the borders of the cells.<br />This will add /remove lines and columns. You can also right-click in the table to display the table and cell edit options.</span><br /><br />
				    
				    
				    <table width="720" cellspacing="0" cellpadding="2" border="1" align="left" summary="" style="font-size: 12px">
				      <tbody>
				        <tr>
				          <td valign="top" bgcolor="#E3E3E3" style="font-weight: bold;">
				            N°<br />
				          </td>
				
				          <td valign="top" bgcolor="#E3E3E3" style="vertical-align: top; font-weight: bold;">
				            Data 1
				          </td>
				          <td valign="top" bgcolor="#E3E3E3" style="vertical-align: top; font-weight: bold;">
				            Data 2
				          </td>
				          <td valign="top" bgcolor="#E3E3E3" style="vertical-align: top; font-weight: bold;">
				            Data 3
				          </td>
				          <td valign="top" bgcolor="#E3E3E3" style="vertical-align: top; font-weight: bold;">
				
				            Data 4<br />
				          </td>
				          <td valign="top" bgcolor="#E3E3E3" style="vertical-align: top; font-weight: bold;">
				            Data 5
				          </td>
				        </tr>
				        <tr>
				          <td style="vertical-align: top;">
				            01
				          </td>
				
				          <td style="vertical-align: top;">
				            100
				          </td>
				          <td style="vertical-align: top;">
				            0.1
				          </td>
				          <td style="vertical-align: top;">
				            1
				          </td>
				          <td style="vertical-align: top;">
				
				            10
				          </td>
				          <td style="vertical-align: top;">
				            1000
				          </td>
				        </tr>
				        <tr>
				          <td style="vertical-align: top;">
				            02
				          </td>
				
				          <td style="vertical-align: top;">
				            200
				          </td>
				          <td style="vertical-align: top;">
				            02
				          </td>
				          <td style="vertical-align: top;">
				            2
				          </td>
				          <td style="vertical-align: top;">
				
				            20
				          </td>
				          <td style="vertical-align: top;">
				            2000
				          </td>
				        </tr>
				        <tr>
				          <td style="vertical-align: top;">
				            03
				          </td>
				
				          <td style="vertical-align: top;">
				            300
				          </td>
				          <td style="vertical-align: top;">
				            03
				          </td>
				          <td style="vertical-align: top;">
				            3
				          </td>
				          <td style="vertical-align: top;">
				
				            30
				          </td>
				          <td style="vertical-align: top;">
				            3000
				          </td>
				        </tr>
				        <tr>
				          <td style="vertical-align: top;">
				            04
				          </td>
				
				          <td style="vertical-align: top;">
				            400
				          </td>
				          <td style="vertical-align: top;">
				            0.4
				          </td>
				          <td style="vertical-align: top;">
				            4
				          </td>
				          <td style="vertical-align: top;">
				
				            40
				          </td>
				          <td style="vertical-align: top;">
				            4000
				          </td>
				        </tr>
				        <tr>
				          <td style="vertical-align: top;">
				            05
				          </td>
				
				          <td style="vertical-align: top;">
				            500
				          </td>
				          <td style="vertical-align: top;">
				            0.5
				          </td>
				          <td style="vertical-align: top;">
				            5
				          </td>
				          <td style="vertical-align: top;">
				
				            50
				          </td>
				          <td style="vertical-align: top;">
				            5000
				          </td>
				        </tr>
				      </tbody>
				    </table><br />
				    <br />
				    <br />
				
				    <br />
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Course preface" image="Course_preface">
		<Description>First page of a learning path</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">Course preface</font><br />
				    <br />
				
				    <table width="720" cellspacing="1" cellpadding="1" border="0" align="left" summary="">
				      <tbody>
				        <tr>
				          <td>
				            <img width="128" height="128" align="right" alt="female.jpg" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/female.jpg" /><span style=
				            "font-style: italic;"><span style="font-weight: bold;">Tip</span> : describe the course to the learner entering it. You are in an editable template so feel free to remove, add and replace
				            any part of the content. To replace the image to the right with your picture, select and delete it, then open the images gallery, upload your picture and select Align : right in Advanced
				            options.</span><br />
				            <br />
				            <span style="font-weight: bold;">Pre-requisites</span> : Course A, Diploma 1<br />
				
				            <br />
				            <span style="font-weight: bold;">Objectives</span> : Be able to do X, Succeed in examination Y<br />
				            <br />
				            <span style="font-weight: bold;">Format</span> : Self-paced, facilitated. Takes about 5 hours.<br />
				            <br />
				            <span style="font-weight: bold;">Fee</span> : XXX <br />
				
				            <br />
				            <span style="font-weight: bold;">Offered by</span> : Your organisation name<br />
				            <br />
				            <span style="font-weight: bold;">Facilitated by</span> : Your name<br />
				            <br />
				            <br />
				
				            <br />
				          </td>
				        </tr>
				      </tbody>
				    </table><br />
				    <br />
				    <br />
				    <br />
				    
			]]>
		</Html>
	</Template>
	
	
	
	<Template title="Assignment description" image="Assignment_description">
		<Description>Explain goals, roles, agenda</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">Assignment description<br />

				    <br /></font>
				    <table width="720" cellspacing="1" cellpadding="1" border="0" align="left" summary="">
				      <tr>
				        <td>
				          <span style="font-style: italic;"><span style="font-weight: bold;">Tip</span> : before linking individuals or groups to the assignments page, you may want to describe the learning activity
				          in detail. The page below is an example of this.</span> <span style="font-weight: bold;"><br />
				          <br />
				          <font size="4">Goals</font></span><br />
				
				          <br />
				          Describe here the goals of the assignment : for instance what kind of report you expect from the group at the end. <span style="font-weight: bold;"><br />
				          <br />
				          <font size="4">Group organisation</font><br />
				          <br /></span> The group will be organised so as to optimise collaboration. Roles will be assigned like this :<br />
				          <br />
				          <table width="100%" cellspacing="0" cellpadding="5" border="1" align="" summary="">
				
				            <tbody>
				              <tr>
				                <td valign="top" style="font-weight: bold;">
				                  Documentation manager
				                </td>
				                <td valign="top">
				                  <img width="128" height="128" align="right" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/book.jpg" alt="book.jpg" />Visits the web and the library and provides
				                  the other members of the group with the necessary documentation.&nbsp;
				                </td>
				              </tr>
				
				              <tr>
				                <td valign="top" style="font-weight: bold;">
				                  Moderator
				                </td>
				                <td>
				                  <img width="128" height="128" align="right" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/interaction.jpg" alt="interaction.jpg" />Interacts in the forum
				                  with all the members of the group. This includes commeenting all the book chapter summaries every week.<br />
				                </td>
				              </tr>
				              <tr>
				
				                <td valign="top">
				                  <span style="font-weight: bold;">Tutor</span>
				                </td>
				                <td>
				                  <img width="128" height="128" align="right" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/group.jpg" alt="group.jpg" />The group tutor is the only one
				                  allowed to contact the trainer so as to limit interaction and optimise the trainer's time.<br />
				                </td>
				              </tr>
				            </tbody>
				
				          </table><br />
				          <font size="4" style="font-weight: bold;"><br />
				          Agenda</font><br />
				          <br />
				          <span style="font-weight: bold;">Week 1</span> : describe group organisation and roles in the group forum. Select a topic for the presentation in the list of topics.<br />
				          <br />
				          <span style="font-weight: bold;">Week 2</span> : each member of the group provides in the group forum a summary of his book chapter.<br />
				
				          <br />
				          <span style="font-weight: bold;">Week 3</span> : the work of the group is uploaded in the Assignments tool for evaluation.<br />
				          <br />
				          <br />
				          <span style="font-weight: bold;"><font size="4">Format</font><br />
				          <br /></span>The document will be uploaded in the Assigment tool as a Word or an Openoffice document, 10 pages max. Use standard fonts like Arial or Times. The text should include a table
				          of contents and indicate clearly at the end the name of the authors, their email address and telephone number.<br />
				
				        </td>
				      </tr>
				    </table><br />
				    <br />
				    <br />
				    <br />
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Resources" image="Resources">
		<Description>Books, links, tools</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">Resources</font><br />
				    <br />
				
				    <table width="720" cellspacing="1" cellpadding="1" border="0" align="left" summary="">
				      <tbody>
				        <tr>
				          <td>
				            <span style="font-style: italic;"><img width="128" height="128" align="right" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/bookcase.jpg" alt=
				            "bookcase.jpg" /><span style="font-weight: bold;">Tip</span> : this page is mainly a links page. To add a link, select a word, open the link icon in the WYSIWYG editor and type the target
				            of the link. To open a link in a new page (so as to avoid leaving the LMS) select the Target tab in the link menu and set target to New Window (_blank). To link to a page included in your
				            own documents, open this document, select No Frame and copy its URL in the URL bar on top of your browser : this is the target of your link.</span><br />
				            <br />
				            <font size="4" style="font-weight: bold;">Web</font><br />
				
				            <ul>
				              <li>
				                <a target="_blank" href="http://en.wikipedia.org/wiki/Cell_%28biology%29">Cell (biology)</a> article in Wikipedia
				              </li>
				              <li>
				                <a target="_blank" href="http://www.jcb.org/">The Journal of Cell Biology</a>, The Rockefeller University Press
				              </li>
				              <li>
				
				                <a target="_blank" href="http://www.biology.arizona.edu/cell_bio/cell_bio.html">The biology Project &gt; Cell biology</a>
				              </li>
				            </ul>
				            <hr style="width: 100%; height: 2px;" />
				            <font size="4" style="font-weight: bold;"><br />
				            Articles and Books</font><br />
				            <ul>
				
				              <li>Alberts B, Johnson A, Lewis J. et al. <span style="font-style: italic;">Molecular Biology of the Cell</span>, 4e. Garland Science. 2002
				              </li>
				              <li>L.M., Mashburn-Warren; Whiteley, M. (2006). "Special delivery: vesicle trafficking in prokaryotes.", <span style="font-style: italic;">Mol Microbiol</span> <span style=
				              "font-weight: bold;">61</span> (4): 839-46
				              </li>
				              <li>
				                <cite style="font-style: normal;">Sterrer W (2002). "On the origin of sex as vaccination". <em>Journal of Theoretical Biology</em> <strong>216</strong>: 387-396</cite>
				
				              </li>
				            </ul>
				            <hr style="width: 100%; height: 2px;" />
				            <font size="4"><span style="font-weight: bold;"><br />
				            Tools</span></font><br />
				            <ul>
				              <li>
				                <a target="_blank" href="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash&amp;promoid=BIOW">Adobe Flash Player</a>
				
				              </li>
				              <li>
				                <a target="_blank" href="http://www.mozilla.com/en-US/">Firefox browser</a>
				              </li>
				              <li>
				                <a target="_blank" href="http://www.openoffice.org/">Openoffice</a>
				              </li>
				            </ul><br />
				
				            <br />
				          </td>
				        </tr>
				      </tbody>
				    </table><br />
				    <br />
				    <br />
				    <br />
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Frequently asked questions" image="Frequently_asked_questions">
		<Description>List of questions and answers </Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">Frequently asked questions</font><br />
				    <br />
				
				    <table width="720" cellspacing="1" cellpadding="1" border="0" align="left" summary="">
				      <tbody>
				        <tr>
				          <td>
				            <img width="128" height="128" align="right" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/search.jpg" alt="search.jpg" /><span style=
				            "font-weight: bold; font-style: italic;">Tip</span> <span style="font-style: italic;">: replace questions and answers by yours. To edit this file, click on the yellow pencil icon beside
				            the document title.</span><br />
				            <br />
				            <span style="font-weight: bold;">Introduction</span><br />
				
				            <br />
				            These questions are compiled from our customer support log and are updated weekly. If you have experienced a problem and have found a solution for it, please enter the solution in the
				            forum so that we can include it here for others users.<br />
				            <span style="font-weight: bold;"><br />
				            <br />
				            Q : What is a learning path?<br />
				            <br /></span>A learning path is a course module providing a mix of multimedia, tests and activities, a standardised navigation menu on the left, a progress bar and a link to your detailed
				            progress in the module. The reporting on your progress is saved in the database to help your coach help you. <span style="font-weight: bold;"><br />
				            <br />
				
				            Q : What are the course technical prerequisites?<br />
				            <br /></span>The course is web-based. You need a recent computer (3 years old max), a browser (Firefox or Internet Explorer), some activities like hotspot questions may require Flash
				            Player 9 and you need a quick internet connection (ADSL or +). <span style="font-weight: bold;"><br />
				            <br />
				            <br />
				            Q : How do I resize my course window?<br />
				            <br /></span>Select the border of the window with your mouse and drag it, keeping your mouse button down. <span style="font-weight: bold;"><br />
				            <br />
				
				            <br />
				            <br />
				            <br />
				            <br /></span><br />
				          </td>
				        </tr>
				      </tbody>
				    </table><br />
				    <br />
				
				    <br />
				    <br />
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Certificate of completion" image="Certificate_of_completion">
		<Description>To appear at the end of a learning path</Description>
		<Html>
			<![CDATA[
					<?php echo $css ?>
				    
				    <font size="5" style="font-weight: bold; color: rgb(192, 192, 192);">Certificate of completion<br><br></font>
				    
				    <span style="font-style: italic; font-size: 15px; "><span style="font-weight: bold">Tip</span> : edit this certificate and put it at the end of your learning path.<br />If you create pre-requisites in your learning path, the certificate will be visible only to ones who deserve it.</span><br /><br />
				    
				    <table width="720" cellspacing="0" cellpadding="20" border="5" align="left" summary="">
				      <tbody>
				        <tr>
				          <td>
				            <div style="text-align: center;">
				              <font size="5" style="font-weight: bold;">Certificate of completion</font><br />
				
				              <br />
				              Name of course<br />
				              <br />
				              Learner first name : [<span style="font-style: italic;">leave this empty</span>]<br />
				              Learner second name : [<span style="font-style: italic;">leave this empty</span>]<br />
				
				              Date : [<span style="font-style: italic;">leave this empty</span>]<br />
				              <br />
				              <span style="font-weight: bold;">Name of the trainer</span><br />
				              <br />
				              <img width="128" height="128" align="right" alt="write.jpg" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/write.jpg" />Name of the organisation [<span style=
				              "font-style: italic;">replace pen icon with organisation logo</span>]<br />
				
				              <br />
				              <span style="font-style: italic;">Print this page and have it filled by your qualified supervisor</span><br />
				            </div>
				          </td>
				        </tr>
				      </tbody>
				    </table><br />
				    <br />
				
				    <br />
				    <br />
				    
			]]>
		</Html>
	</Template>	
	
	
</Templates>