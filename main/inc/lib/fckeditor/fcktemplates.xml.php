<?php header('Content-Type: text/xml; charset=utf-8');

require_once('../../global.inc.php');
echo '<?xml version="1.0" encoding="utf-8" ?>';
$IMConfig['base_url'] = $_configuration['root_web'].'main/img/gallery/';
function loadCSS($css_name)
{
	$template_css = '<style type="text/css">'.file_get_contents(api_get_path(SYS_PATH).'main/css/'.$css_name.'/default.css').'</style>';
	$template_css=str_replace('images/',api_get_path(WEB_PATH).'main/css/'.$css_name.'/images/',$template_css);
	return $template_css;
}
$css = loadCSS(api_get_setting('stylesheets'));
//<Templates imagesBasePath="fck_template/images/">
?>
<Templates imagesBasePath="">
	
	<?php

	//Get all personnal templates in the database
	
	$table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);
	
	$sql = 'SELECT id, title, description, ref_doc FROM '.$table_template.' WHERE course_code="'.api_get_course_id().'" AND user_id="'.api_get_user_id().'"';
	
	$result_template = api_sql_query($sql);
	
	while($a_template = Database::fetch_array($result_template))
	{		
		$document_id = $a_template['ref_doc'];		
		$course = api_get_course_info();
		$table_document = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);
		
		$sql_document_path = 'SELECT path FROM '.$table_document.' WHERE id="'.$document_id.'"';
		
		$result_document_path = api_sql_query($sql_document_path);
		$document_path = Database::result($result_document_path,0,0);
		
		$width = 100;
		$height = 90;
		
		$im = @ImageCreate($width, $height);
		$bg_color = ImageColorAllocate($im, 255, 255, 255);
		$ttfont   = api_get_path(LIBRARY_PATH).'fckeditor/FreeSans.ttf';
		$text_color = ImageColorAllocate($im, 0, 0, 0);
		
		$a_text=explode(' ',$a_template['title']);
		$y=25;
		foreach ($a_text as $a_part_of_title) {
			imagettftext($im, 10, 0, 10, $y, $text_color, $ttfont, $a_part_of_title);
			$y+=20;
		}
		
		imagejpeg($im, api_get_path(SYS_CODE_PATH).'upload/template_thumbnails/'.$a_template['id'].'.jpg');
		
		echo '<Template title="'.htmlentities($a_template['title']).'" image="'.api_get_path(WEB_CODE_PATH).'upload/template_thumbnails/'.$a_template['id'].'.jpg">';
			echo '<Description>'.htmlentities($a_template['description']).'</Description>';
			echo '<Html>';
			
			echo htmlentities(file_get_contents(api_get_path(SYS_COURSE_PATH).$course['path'].'/document'.$document_path));
			
			echo '</Html>';
		echo '</Template>';
			
	}
	
	//Maybe helpfull to translate the tips ? I can delete this if don't
	function tip($type){
	 $tip='<br /><table width=\'100%\' cellpadding="5px" padding="5px"><tbody><tr><td>'
            .'<div class="tip">'
            .'<span style="font-weight: bold;">Tip :</span>'
			.'<ul>';					
				 switch ($type):
				 case "flash":
					 $tip.='<li>To add your flash animation, delete this and go to the flash Icon</li>';
					 break;
				 case "video":
					 $tip.='<li>To add a video, delete this and go to the video button Icon</li>';
					 break;
				 case "table":
					 $tip.='<li>To add row, click on the narrow button on the left or right when you click on a table</li>'
					        .'<li>To add column, click on the narrow button on the top or bottom when you click on a table</li>';
					 break;
				 case "image":
					 $tip.='<li>To add an image, delete this and go to the picture button Icon</li>';
					 break;
				 case "audio":
					 $tip.='<li>Audio tips</li>';
					 break;
				 case "cover":
					 $tip.='<li>Welcome to the Dokeos document creator</li>';
					 break;
				 case "glossary":
					 $tip.='<li>Here glossary tips</li>';
					 break;
			     case "preface":
				     $tip.='<li>Describe the course to the learner entering it.</li>';
				 default:
				    $tip.='<li>Here you can enter all the default tips for all</li>'
					      .'<li>To delete the tip, click on the delete button on the left</li>';
				 endswitch;
	 $tip.='</ul>';
	             
	 $tip.='For more information &amp; tutorials go to : <a target="_blank" href="http://www.dokeos.com/en/tutorials.php">http://www.dokeos.com/en/tutorials.php</a>'                          
            .'</div>'
			.'</td></tr></tbody></table>';
	 echo $tip;
	  }
	
	?>
    <!--PRESENTATION TEMPLATES-->
        <Template title="First page" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/cover.png';?>" cat="presentation" style="elegant">
            <Description>It's the cover page of your course</Description>
            <Html>
                <![CDATA[
                        <head>
                           <?php echo $css ?>
                           <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                        </head>
                        <body>
                       <div id="course-content">
                        <h2 align="center" margin-top="60%">Your name</h2>
                        <br/>
                        <br/>                    
                            <h1 align="center" margin-top="60%">Click here to enter the title</h1>
                            <h2 align="center" margin-top="60%">Your subtitle here</h2>
                             <br/>
                            <div align="center"><img name="Logo" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/logo.png';?>" width="30%" height="30%" alt="Logo" /></div>
                          <br/>
                          <br/>
                          <br/>
                          <br/>
                           <h2 align="center" margin-top="60%">Your organisation</h2>
                           <?php tip('help'); ?>
                        </body>
                ]]>
            </Html>
        </Template>
        
        
        <Template title="Dedicace" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/dedicace.png';?>" cat="presentation" style="elegant">
            <Description>Make your own dedicace</Description>
            <Html>
                <![CDATA[
                        <head>
                           <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                        </head>
                        <body>
                            <div id="course-content">
                            <table id="dedicace" width="100%" height="500" cellspacing="5" cellpadding="5" border="0">
                                <tbody>
                                    <tr height="45%">
                                        <td width="90%">&nbsp;</td>
                                        <td width="10%" rowspan="3">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td>
                                        <p align="right" class="dedicace" style="font-style: italic;">Enter your dedicace here, e.g. to L&eacute;on Werth, Mr Dokeos ...</p>
                                        <p style="font-style: italic;">                     </p>
                                        <p class="dedicace" style="text-align: right;"><span style="font-style: italic;">Triple-click here to enter your comment...</span><br />                     		                                        </p>                                        </td>
                                    </tr>
                                    <tr height="30%">
                                        <td>&nbsp;</td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                        </body>
                ]]>
            </Html>
        </Template>
        
        
        <Template title="Course preface" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Course_preface.png';?>" cat="presentation" style="elegant">
            <Description>First page of a learning path</Description>
            <Html>
                <![CDATA[
                        <head>
                           <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                        </head>
                        <body>
                       <div id="course-content">
                        <h2>Course preface</h2>
                                                
                          <table width="100%" cellspacing="5" cellpadding="5" border="0" summary="" id="dedicace">
                              <tbody>
                                  <tr>
                                      <td width="75%">&nbsp; <br />                                 
                                    <span style="font-weight: bold;">Pre-requisites</span> : Course A, Diploma 1<br />                                                      <br />                                 <span style="font-weight: bold;">Objectives</span> : Be able to do X, Succeed in examination Y<br />                                 <br />                                 <span style="font-weight: bold;">Format</span> : Self-paced, facilitated. Takes about 5 hours.<br />                                 <br />                                 <span style="font-weight: bold;">Fee</span> : XXX <br />                                                      <br />                                 <span style="font-weight: bold;">Offered by</span> : Your organisation name<br />                                 <br />                                 <span style="font-weight: bold;">Facilitated by</span> : Your name<br />                                 <br />                                 <br />                                                      <br />                               </td>
                                      <td width="25%" style="vertical-align: bottom;"><div align="center"><img align="bottom" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/trainer/trainer_glasses.png'?>" alt="trainer_glasses.png" style="width: 138px; height: 208px;" /></div></td>
                                </tr>
                              </tbody>
                          </table>

                        <?php tip('preface'); ?>
                        </div>
                        </body>
                ]]>
            </Html>
        </Template>
        
        
        <!--INTRODUCTION TEMPLATES-->
        <Template title="Introduction" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/intro.png';?>" cat="presentation" style="elegant">
            <Description>It's the cover page of your course</Description>
            <Html>
                <![CDATA[
                        <head>
                           <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                        </head>
                        <body>
                            <div id="course-content">
                            <h1>Introduction</h1>
                            <p><img width="128" hspace="5" height="128" align="left" alt="presentation.jpg" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/presentation.jpg';?>" />Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque. Sed quis arcu sed dolor laoreet dictum. Sed sed arcu laoreet nibh scelerisque tempor. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nam vitae purus id tortor adipiscing tincidunt. Nam pharetra, lorem vel ullamcorper tempus, turpis enim lacinia quam, id sollicitudin orci nunc et lectus. Pellentesque ut eros. Pellentesque lacus dui, ornare at, feugiat sollicitudin, porttitor quis, justo. Nulla nec augue. Curabitur mattis facilisis est. Vivamus at orci consequat turpis hendrerit lobortis. Suspendisse pharetra placerat quam. Maecenas commodo venenatis felis. Nam ornare molestie neque. Sed porta, est quis eleifend laoreet, neque metus mattis neque, vitae bibendum sem ante non libero.Quisque ante est, sodales nec, dignissim non, tristique sed, nisi. Nam commodo neque sit amet augue ultrices auctor. Mauris ornare. Donec ullamcorper dolor in tellus. Nunc dapibus, enim id accumsan vehicula, arcu orci rutrum tortor, a facilisis dui nunc vitae mi. Nulla facilisi. Mauris ac pede non magna rutrum viverra. Nulla in ligula. Mauris tincidunt. Aenean eu orci vel nulla ultricies bibendum. Sed adipiscing augue sit amet elit. Fuctus, mauris est lacinia lectus, non blandit eros orci sed tellus. Curabitur arcu ligula, bibendum vitae, iaculis eu, ultrices sit amet, nunc.</p>
                            <h2>Enter your title 2 here</h2>
                            <p>In hac habitasse platea dictumst. Suspendisse vulputate felis ac urna. Fusce pharetra ligula. Cras dui magna, elementum vitae, adipiscing eget, sodales commodo, nisl. Maecenas risus lectus, molestie et, lobortis sit amet, pulvinar eget, elit. In nisl sapien, rhoncus a, imperdiet in, cursus at, sem. In sit amet neque. <br /></p>
                            <ul>
                                <li>Explain step here</li>
                            </ul>
                            <br />
                            </div>
                           <?php tip('help'); ?>
                        </body>
                ]]>
            </Html>
        </Template>
    
        <Template title="Text page" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Text.png';?>" cat="explanation" style="elegant">
            <Description>Theory, content section, chapter...</Description>
            <Html>
                <![CDATA[
                        <head>
                           <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                        </head>
                        <body>
                        <div id="course-content">
                        <h1>Click here to add your title</h1>
                        <h2>Header 2</h2>
                        <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque. Sed quis arcu sed dolor laoreet dictum. Sed sed arcu laoreet nibh scelerisque tempor. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nam vitae purus id tortor adipiscing tincidunt. Nam pharetra, lorem vel ullamcorper tempus, turpis enim lacinia quam, id sollicitudin orci nunc et lectus. Pellentesque ut eros. Pellentesque lacus dui, ornare at, feugiat sollicitudin, porttitor quis, justo. Nulla nec augue. Curabitur mattis facilisis est. Vivamus at orci consequat turpis hendrerit lobortis. Suspendisse pharetra placerat quam. Maecenas commodo venenatis felis. Nam ornare molestie neque. Sed porta, est quis eleifend laoreet, neque metus mattis neque, vitae bibendum sem ante non libero.</p>
                        <h3>Header 3</h3>
                        <p>Quisque ante est, sodales nec, dignissim non, tristique sed, nisi. Nam commodo neque sit amet augue ultrices auctor. Mauris ornare. Donec ullamcorper dolor in tellus. Nunc dapibus, enim id accumsan vehicula, arcu orci rutrum tortor, a facilisis dui nunc vitae mi. Nulla facilisi. Mauris ac pede non magna rutrum viverra. Nulla in ligula. Mauris tincidunt. Aenean eu orci vel nulla ultricies bibendum. Sed adipiscing augue sit amet elit. Pellentesque lorem lectus, dictum vitae, suscipit sit amet, sagittis eu, turpis. Suspendisse et mi ut tellus luctus sodales. Etiam eleifend lorem ac sem. Donec nec velit sed velit aliquet venenatis. Donec vel lectus. Donec mollis tellus. Sed scelerisque, sapien vitae consectetuer luctus, mauris est lacinia lectus, non blandit eros orci sed tellus. Curabitur arcu ligula, bibendum vitae, iaculis eu, ultrices sit amet, nunc.</p>
                        
                        <?php tip('help'); ?>
                        </div>
                   	    </body>
                        
                ]]>
            </Html>
        </Template>
	
	
	<Template title="Teacher explaining" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Teacher_explaining.png';?>" cat="explanation" style="elegant">
		<Description>Mr Dokeos points to your content <em>!</em></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                    <div id="course-content">
				    <h2>Teacher explaining</h2>
                     <table width="100%" cellspacing="5" cellpadding="1em" border="0" summary="" id="teacherExplaining">
				      <tbody>
				        <tr>
				          <td valign="top">
				            <span style="font-weight: bold;"><img width="250" height="250" align="bottom" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/mr_dokeos/anim_teaching.jpg';?>" alt=
				            "anim_teaching.jpg" /><br /></span>
				          </td>
				          <td style="vertical-align: top; padding:1em;">
				            <h3>Your title here</h3>
							<p>
				            The cell is the structural and functional unit of all known living organisms. It is the simplest unit of an organism that is classified as living, and is sometimes called the building
				            block of life.<p/>
				            <p>
				            Some organisms, such as bacteria, are unicellular (consist of a single cell). Other organisms, such as humans, are multicellular. (Humans have an estimated 100 trillion or 1014 cells; a
				            typical cell size is 10 µm; a typical cell mass is 1 nanogram.)<p/>
				            <br />
				            <br />
				          </td>
				        </tr>
				      </tbody>
				
				    </table><br />
					<?php tip('help');?>
                    
                    </div>
				    </body>
				    
			]]>
		</Html>
	</Template>
	
    
    
    <Template title="Left image with text" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/img_left_encadred.png';?>" cat="explanation" style="elegant">
		<Description>It's a picture on the left picture with encadred text</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				   </head>
                    <body>
                        <div id="course-content">
                        <h1>Click here to add your title 1</h1>
                        <p style="text-align: center;"><img vspace="10" hspace="10" align="left" src="<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/mr_dokeos/anim_teaching.jpg" alt="anim_teaching.jpg" style="width: 209px; height: 209px;" /></p>
                        <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Phasellus eleifend libero eget tortor. Mauris ac massa id orci viverra interdum. Pellentesque quis lacus. Fusce lacinia lacinia ipsum. Vivamus a quam. Aliquam lobortis vestibulum elit. Vivamus iaculis, ante ut iaculis placerat, est pede posuere sem, at feugiat lorem magna in enim. Etiam lacinia, justo a tincidunt pretium, massa justo aliquam purus, ac suscipit metus orci sed enim. Vestibulum a lacus. Maecenas lacus. Donec pede nibh, pellentesque vitae, rutrum porttitor, posuere at, mi. Duis ut nulla a quam laoreet rhoncus. Suspendisse potenti. In porttitor accumsan pede. Donec massa. Proin a est. In enim lectus, convallis ut, pretium malesuada, ultrices rutrum, felis. </p>
                        <p>Nullam lorem nisi, pulvinar vitae, dapibus id, bibendum quis, tortor. Quisque eu sem. Pellentesque dictum facilisis eros. Donec sagittis rutrum sem. Nullam sed mi. Integer ac eros vel mi tincidunt gravida. Nulla facilisi. Etiam justo. Praesent tristique elit ut pede auctor eleifend. Mauris libero. Vestibulum porta. Donec orci.</p>
                        <p>Etiam fermentum est at pede. Nulla malesuada porttitor sapien. Donec ullamcorper feugiat nisi. Donec mattis felis consequat ante. Sed nec enim eu tortor tincidunt auctor. Vestibulum gravida enim id diam. Nam id nunc et ante aliquam egestas. Donec auctor dictum dui. Nulla facilisi. Fusce nibh. In suscipit elit sed quam. Cras eu nulla. Curabitur euismod cursus ligula. Phasellus iaculis elementum augue. Suspendisse porta diam. Sed tortor arcu, euismod eu, lacinia nec, imperdiet vel, nunc. Vivamus pede ligula, congue euismod, lobortis at, fringilla in, sem. Sed eu lectus non tellus faucibus adipiscing. Proin adipiscing nulla eu ante.</p>
                        <br />
 						<?php tip('help');?>
                        </div>
                    </body>
                                                    
             ]]>
         </Html>
    </Template>
    
    <Template title="Text and image centered" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/text_img_centered.png';?>" cat="explanation" style="elegant">
		<Description>It's a text with an image centered and legend.</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				   </head>
                    <body>
                        <div id="course-content">
                        <h1>Click here to add your title 1</h1>
                        <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Phasellus eleifend libero eget tortor. Mauris ac massa id orci viverra interdum. Pellentesque quis lacus. Fusce lacinia lacinia ipsum. Vivamus a quam. Aliquam lobortis vestibulum elit. Vivamus iaculis, ante ut iaculis placerat, est pede posuere sem, at feugiat lorem magna in enim. Etiam lacinia, justo a tincidunt pretium, massa justo aliquam purus, ac suscipit metus orci sed enim. Vestibulum a lacus. Maecenas lacus. Donec pede nibh, pellentesque vitae, rutrum porttitor, posuere at, mi. Duis ut nulla a quam laoreet rhoncus. Suspendisse potenti. In porttitor accumsan pede. Donec massa. Proin a est. In enim lectus, convallis ut, pretium malesuada, ultrices rutrum, felis. </p>
                        <p>Nullam lorem nisi, pulvinar vitae, dapibus id, bibendum quis, tortor. Quisque eu sem. Pellentesque dictum facilisis eros. Donec sagittis rutrum sem. Nullam sed mi. Integer ac eros vel mi tincidunt gravida. Nulla facilisi. Etiam justo. Praesent tristique elit ut pede auctor eleifend. Mauris libero. Vestibulum porta. Donec orci.</p>
                        <div style="text-align: center;"><img width="720" height="326" align="bottom" src="<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/diagrams/brain.png" alt="Brain" />&nbsp;                                                 <br /></div>
                        <div style="text-align: center;">Your legend here</div>
                        <br />
                      <br />
                    <p>&nbsp;</p>
                     <?php tip('help'); ?>
                    
                    </div>
                    </body>
                                                    
             ]]>
         </Html>
    </Template>
    
	
	<Template title="Comparison" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/comparison.png';?>" cat="explanation" style="elegant">
		<Description>2 columns text page</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
                    <body>
                    <div id="course-content">
                    <h2>Comparison</h2>
                    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque. Sed quis arcu sed dolor laoreet dictum. Sed sed arcu laoreet nibh scelerisque tempor. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nam vitae purus id tortor adipiscing tincidunt. Nam pharetra, lorem vel ullamcorper tempus, turpis enim lacinia quam, id sollicitudin orci nunc et lectus. Pellentesque ut eros. Pellentesque lacus dui, ornare at, feugiat sollicitudin, porttitor quis, justo. Nulla nec augue. Curabitur mattis facilisis est. Vivamus at orci consequat turpis hendrerit lobortis. Suspendisse pharetra placerat quam. Maecenas commodo venenatis felis. Nam ornare molestie neque. Sed porta, est quis eleifend laoreet, neque metus mattis neque, vitae bibendum sem ante non libero.                    </p>
                    <table width="100%" cellspacing="5" cellpadding="5" border="0" align="left" summary="">
                        <tbody>
                            <tr>
                                <td width="50%" valign="top" align="left" style="padding: 1em;">
                                <h3>Set 1</h3>
                                <ul>
                                    <li>Enter your argument here</li>
                                </ul>
                                </td>
                                <td width="50%" valign="top" align="left" style="padding: 1em;">
                                <h3>Set 2</h3>
                                <ul>
                                    <li>Enter your argument here</li>
                                </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <br />
                    <p>&nbsp;</p>
                    <?php tip('help'); ?>
                    </div>
                </body>
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Diagram explained" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Diagram_explained.png';?>" cat="explanation" style="elegant">
		<Description>Image on the left, comment on the right</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
                    <body>
                    <div id="course-content">
                    <h1>Click here to add your title 1</h1>
                    <table width="100%" cellspacing="5" cellpadding="5" border="0" align="" summary="">
                        <tbody>
                            <tr>
                                <td width="40%"><div align="center"><img align="bottom" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/diagrams/alaska_chart.png'?>" alt="alaska_chart.png" style="width: 424px; height: 381px;" /></div></td>
                                <td width="60%" align="left" valign="top"><h2>Explanations : </h2>
                                <ul>
                                    <li>Enter your explanation here</li>
                                </ul>
                              </td>
                            </tr>
                        </tbody>
                    </table>
                    <br />
                    <p>&nbsp;</p>
                    <?php tip('help'); ?>
                    </div>
                </body>
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Image alone" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Picture.png';?>" cat="multimedia" style="elegant">
		<Description>Self-explaining diagram</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h1>Picture page</h1> 
				    <table width="100%" cellspacing="5" cellpadding="5" border="0" summary="">
				      <tbody>
				        <tr>
				          <td>
				            <img width="100%" height="100%" align="bottom" alt="piano.jpg" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/diagrams/piano.jpg'?>" />
				          </td>
				        </tr>
				      </tbody>
				    </table>
				    <br />
                    <p>&nbsp;</p>
                    <?php tip('help'); ?>
					</div>
				    </body>
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Flash animation" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Flash_animation_page.png';?>">
		<Description>Animation + introduction text</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
                   <h1>Flash animation page</h1>
                    <table width="100%" cellspacing="5" cellpadding="5" border="0" summary="">
                        <tbody>
                            <tr>
                                <td style="vertical-align: middle; text-align: center;"> <span style="font-style: italic;"><embed width="450" height="450" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="<?php echo api_get_path(REL_PATH); ?>main/default_course_document/flash/SpinEchoSequence.swf" play="true" loop="true" menu="true"></embed></span><br /> 				          </td>
                            </tr>
                        </tbody>
                    </table>
                    <br />
                    <p>&nbsp;</p>
                    <?php tip('flash');?>
					</div>
				    </body>
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Audio page" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Audio_page.png';?>">
		<Description>Audio + image + text : listening comprehension etc.</Description>
		<Html>
			<![CDATA[
				    
				    <html xmlns="http://www.w3.org/1999/xhtml">
					  <head>
					    <title>Audio</title>
                          <?php echo $css ?>
                                                    
					  </head>
					  <body>
					    <h2>Listening comprehension</h2>
                        <table width="100%" cellspacing="5" cellpadding="5" border="0" align="left" summary="">
                                    <tbody>
                                        <tr>
                                            <td valign="top"> 					            <span style="font-weight: bold;">
                                            <div style="text-align: center;"><img width="250" height="235" align="bottom" alt="Listening image" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/trainer/trainer_staring.png" /></div>
                                            <div style="text-align: center;"></div></span> 					          </td>
                                            <td rowspan="2" class="listeningaudio" style="vertical-align: top;"> 					            
                                            <h2>Listening comprehension</h2>
                                            <p>Listen carefully and repeat the text as many times as required.<p/>
                                            <p>Try answering the following questions :<p/>
                                              <ul>
                                                  <li>What is the conference about? 					              </li>
                                                  <li>Who produces solid waste? 					              </li>
                                                  <li>List 3 examples of solid waste? 					              </li>
                                              </ul>
                                            <p>Then go the test and evaluate your comprehension.</p>			           
                                           </td>
                                        </tr>
                                            <tr>
                                              <td valign="top"><div align="center"><span style="text-align: center;">
                                                    <object width="90" height="25" align="" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" id="test">
                                                    <param name="movie" value="<?php echo api_get_path(REL_PATH) ?>main/inc/lib/mp3player/player_mp3.swf?mp3file=/dokeosSVN/courses/FLV/document/audio/ListeningComprehension.mp3" />
                                                    <param name="quality" value="high" />
                                                    <param name="bgcolor" value="#FFFFFF" /> 
                                                    <embed width="90" height="25" align="" src="<?php echo api_get_path(REL_PATH) ?>main/inc/lib/mp3player/player_mp3.swf?mp3file=<?php echo api_get_path(REL_COURSE_PATH) ?>FLV/document/audio/ListeningComprehension.mp3" quality="high" bgcolor="#FFFFFF" name="Streaming" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>
                                                  </object>
                                              </span></div></td>
                                            </tr>
                                    </tbody>
                                </table>
					<br />
                    <p>&nbsp;</p>
                     <?php tip('audio'); ?>
					</body>
					</html>
				    </body>
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Video page" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Video.png';?>">
		<Description>On demand video + text</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h2>On demand video</h2><br />
				    <table width="100%" cellspacing="5" cellpadding="10" border="0" align="left" summary="">
				
				      <tbody>
				        <tr>
				          <td style="vertical-align: top;" colspan="2">
				            <h2>Header 2</h2>
                            <p>Your text here</p>
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
				            <h3>e.g. : Excerpt from Marc Shuttleworth's keynote</span></h3>
				
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
                        <tr>
                          <td colspan="2">
                          <p>You can drag and drop the video here</p>
                          </td>
                        <tr>
				      </tbody>
				    </table>
                    <br />
                    <p>&nbsp;</p>
                    <?php tip('video'); ?>
				    <style type="text/css">body{}</style><!-- to fix a strange bug appearing with firefox when editing this template -->
				    
				    </body>
			]]>
		</Html>
	</Template>
    
    
    <Template title="Video page fullscreen" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/video_fullscreen.png';?>">
		<Description>On demand video in fullscreen</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h2>On demand video</h2>
				    <table width="100%" cellspacing="5" cellpadding="10" border="0" align="left" summary="">			
				      <tbody>
				        <tr>
				          <td valign="top" align="center">
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
				          	<object type="application/x-shockwave-flash" data="<?php echo api_get_path(REL_PATH) ?>main/inc/lib/flv_player/player_flv_mini.swf" height="480" width="640">
					          		<param name="movie" value="<?php echo api_get_path(REL_PATH) ?>main/inc/lib/flv_player/player_flv_mini.swf" />
					          		<param name="FlashVars" value="flv=<?php echo api_get_path(REL_PATH) ?>main/default_course_document/video/example.flv&autoplay=1&width=640&amp;height=480" />
					          </object>
					                              <style type="text/css">body{}</style>
				          	
				          	<?php
				          	}
				          	?>
				          </td>
                          </tr>
                          <tr>
                          <td align="center">
                            <p>Your comment here</p>
                          </td>
                          </tr>
				      </tbody>
				    </table>
                    <br />
                    <br />
                    <p><?php tip('video'); ?></p>
				    <style type="text/css">body{}</style><!-- to fix a strange bug appearing with firefox when editing this template -->
				    
				    </body>
			]]>
		</Html>
	</Template>
	
	
	<Template title="Table page" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Table.png';?>">
		<Description>Spreadsheet-like page</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h2>A table</h2>
				    
				    <span style="font-style: italic; font-size: 15px; "><span style="font-weight: bold">Tip</span> : to edit this table, click on the small arrows and cross-in-a-circle icons on the borders of the cells.<br />This will add /remove lines and columns. You can also right-click in the table to display the table and cell edit options.</span><br /><br />
				    
				    
				    <table width="100%" cellspacing="0" cellpadding="2" border="1" summary="" style="font-size: 12px">
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
                    <?php tip('table'); ?>
					</div>
				    </body>
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="Assignment description" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Assignment_description.png';?>">
		<Description>Explain goals, roles, agenda</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h2>Assignment description</h2>
				    <?php tip('help'); ?>
				          <h4>Goals</h4>
				
				          <br />
				          Describe here the goals of the assignment : for instance what kind of report you expect from the group at the end. <span style="font-weight: bold;"><br />
				          <br />
				          <font size="4">Group organisation</font><br />
				          <br /></span> The group will be organised so as to optimise collaboration. Roles will be assigned like this :<br />
				          <br />
				          <table width="100%" cellspacing="5" cellpadding="5" border="1" align="" summary="">
				
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
                          <h3>Agenda</h3>
                             <ul>
                                <li><span style="font-weight: bold;">Week 1</span> : describe group organisation and roles in the group forum. Select a topic for the presentation in the list of topics.</li>
                                <li><span style="font-weight: bold;">Week 2</span> : each member of the group provides in the group forum a summary of his book chapter.</li>
                                <li><span style="font-weight: bold;">Week 3</span> : the work of the group is uploaded in the Assignments tool for evaluation.</li>
                            </ul>
				          <br />
				          <span style="font-weight: bold;"><font size="4">Format</font><br />
				          <br /></span>The document will be uploaded in the Assigment tool as a Word or an Openoffice document, 10 pages max. Use standard fonts like Arial or Times. The text should include a table
				          of contents and indicate clearly at the end the name of the authors, their email address and telephone number.<br />
				
				        </td>
				      </tr>
				    </table><br />
				    <br />
                    
					</div>
				    </body>
			]]>
		</Html>
	</Template>
	
	
	<Template title="Resources" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Resources.png';?>">
		<Description>Books, links, tools</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h2>Resources</h2>
				    
				    <table width="100%" cellspacing="5" cellpadding="5" border="0" align="left" summary="">
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
					</div>
				    </body>
			]]>
		</Html>
	</Template>
	
	<Template title="Bibliography" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Resources.png';?>">
		<Description>Books, links, tools</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h1>Bibliography</h2>
				    <h2>Books</h2>
                     <ul>
                        <li>Enter your reference here e.g : L.M., Mashburn-Warren; Whiteley, M. (2006). "Special delivery: vesicle trafficking in prokaryotes.", <span style="font-style: italic;">Mol Microbiol</span> <span style="font-weight: bold;">61</span> (4): 839-46</li>
                     </ul>
                     <h2>Periodic</h2>
                     <ul>
                        <li>Enter your reference here e.g : L.M., Mashburn-Warren; Whiteley, M. (2006). "Special delivery: vesicle trafficking in prokaryotes.", <span style="font-style: italic;">Mol Microbiol</span> <span style="font-weight: bold;">61</span> (4): 839-46</li>
                     </ul> 
				    <?php tip('help'); ?>                     
					</div>
				    </body>
			]]>
		</Html>
	</Template>
    
    
	<Template title="Frequently asked questions" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Frequently_asked_questions.png';?>">
		<Description>List of questions and answers </Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				   <h1>Frequently asked questions</h1>
				   <?php tip('help');?>
				
				    <table width="100%" cellspacing="5" cellpadding="5" border="0" align="left" summary="">
				      <tbody>
				        <tr>
				          <td>
				            <img width="128" height="128" align="right" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/search.jpg" alt="search.jpg" />
				            <h2>Introduction</h2>
							<p>
				            These questions are compiled from our customer support log and are updated weekly. If you have experienced a problem and have found a solution for it, please enter the solution in the
				            forum so that we can include it here for others users.
                            </p>
				            <h3>Q : What is a learning path?</h3>
				            <p>A learning path is a course module providing a mix of multimedia, tests and activities, a standardised navigation menu on the left, a progress bar and a link to your detailed
				            progress in the module. The reporting on your progress is saved in the database to help your coach help you.</p>
				
				            <h3>Q : What are the course technical prerequisites?</h3>
				            <p>The course is web-based. You need a recent computer (3 years old max), a browser (Firefox or Internet Explorer), some activities like hotspot questions may require Flash Player 9 and you need a quick internet connection (ADSL or +). </p>
				            <h3>Q : How do I resize my course window?</h3>
				            <p>Select the border of the window with your mouse and drag it, keeping your mouse button down. </p>
	
				          </td>
				        </tr>
				      </tbody>
				    </table>
				    <br />
					</div>
				    </body>
			]]>
		</Html>
	</Template>
	
	<Template title="Glossary" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/glossary.png';?>">
		<Description>List of term of the section</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				   <h1>Glossary</h1>
				   <?php tip('glossary');?>
 				   <?php 
				   $letter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
				   echo "<div text-align='center' style='text-align: center;'><font size='4'>";
					echo '<a href="#'.$letter[0].'">'.$letter[0].'</a>';
					for($i=1 ; $i<26 ; $i++){
					  echo '-<a href="#'.$letter[$i].'">'.$letter[$i].'</a>';
					
					}
				   echo "</font></div>";
					
					
					echo '<h2>'.$letter[0].'<a name=\''.$letter[0].'\' id=\''.$letter[0].'\'></a></h2>'
						 .'<dl>'							
						 .'<dt>An exemple of term</dt>'
						 .'<dd>Copy-paste this to make a definition</dd>'								
						 .'</dl>';
						 
					for($i=1 ; $i<26 ; $i++){
						 echo '<h2>'.$letter[$i].'<a name=\''.$letter[$i].'\' id=\''.$letter[$i].'\'></a></h2>'
						 .'<dl>'							
						 .'<dt>&nbsp;</dt>'
						 .'<dd>&nbsp;</dd>'								
						 .'</dl>';
					} 
				
				   ?>
				    <br />
					</div>
				    </body>
			]]>
		</Html>
	</Template>
    
    
	<Template title="Certificate of completion" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Certificate_of_completion.png';?>">
		<Description>To appear at the end of a learning path</Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <table width="720px" id="certificate" cellspacing="0" cellpadding="20" border="5" align="left" summary="">
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
					</div>
				    </body>
				    
			]]>
		</Html>
	</Template>
    
    <!--Proposition de template supplémentaire-->
    <Template title="Schema with explain" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Audio_page.png';?>" cat="communication" style="elegant">
		<Description>A schema explain by a trainer</Description>
		<Html>
			<![CDATA[
				    
				    <html xmlns="http://www.w3.org/1999/xhtml">
					  <head>
					    <title>Audio</title>
                          <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>                         
					  </head>
					  <body>
					    <h2>Explication</h2>
                        <table width="100%" cellspacing="5" cellpadding="5" border="0" align="left" summary="">
                                    <tbody>
                                        <tr>
                                            <td valign="bottom">
                                            <div align="center"><span style="text-align: center;">
                                                    <object width="90" height="25" align="" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" id="test">
                                                    <param name="movie" value="<?php echo api_get_path(REL_PATH) ?>main/inc/lib/mp3player/player_mp3.swf?mp3file=/dokeosSVN/courses/FLV/document/audio/ListeningComprehension.mp3" />
                                                    <param name="quality" value="high" />
                                                    <param name="bgcolor" value="#FFFFFF" /> 
                                                    <embed width="90" height="25" align="" src="<?php echo api_get_path(REL_PATH) ?>main/inc/lib/mp3player/player_mp3.swf?mp3file=<?php echo api_get_path(REL_COURSE_PATH) ?>FLV/document/audio/ListeningComprehension.mp3" quality="high" bgcolor="#FFFFFF" name="Streaming" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>
                                                  </object>
                                              </span></div></span> 					          </td>
                                            <td rowspan="2" style="vertical-align: top;"> 					            
                                            	 <div style="text-align: center;"><img width="auto" height="auto" align="bottom" alt="Listening image" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/diagrams/tetralogy.png" />          
                                           </td>
                                        </tr>
                                            <tr>
                                              <td valign="bottom">
                                              <span style="font-weight: bold;">
                                            <div style="text-align: center;"><img width="250" height="235" align="bottom" alt="Listening image" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/trainer/trainer_points_right.png" /></div>
                                           </td>
                                            </tr>
                                    </tbody>
                                </table>
					<br />
                    <p>&nbsp;</p>
                     <?php tip('audio'); ?>
					</body>
					</html>
				    </body>				    
			]]>
		</Html>
	</Template>	
</Templates>