<?php header('Content-Type: text/xml; charset=utf-8');
$language_file = 'document';
require_once('../../global.inc.php');
echo '<?xml version="1.0" encoding="utf-8" ?>';
$IMConfig['base_url'] = $_configuration['root_web'].'main/img/gallery/';
function loadCSS($css_name)
{
	$template_css = ' <style type="text/css">'.file_get_contents(api_get_path(SYS_PATH).'main/css/'.$css_name.'/default.css').'</style>';
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
	$result_template = api_sql_query($sql,__FILE__,__LINE__);
	
	while($a_template = Database::fetch_array($result_template))
	{		
		$document_id = $a_template['ref_doc'];		
		$course = api_get_course_info();
		$table_document = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);		
		$sql_document_path = 'SELECT path FROM '.$table_document.' WHERE id="'.$document_id.'"';		
		$result_document_path = api_sql_query($sql_document_path,__FILE__,__LINE__);
		$document_path = Database::result($result_document_path,0,0);
		
		$width = 100;
		$height = 90;
		
		$im = @ImageCreate($width, $height);
		$bg_color = ImageColorAllocate($im, 255, 255, 255);
		$ttfont   = api_get_path(LIBRARY_PATH).'fckeditor/FreeSans.ttf';
		$text_color = ImageColorAllocate($im, 0, 0, 0);
		
		$a_text=explode(' ',$a_template['title']);
		$y=25;
		foreach ($a_text as $a_part_of_title) 
		{
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
	
	function tip($type)
	{
	 $tip='<br /><table width=\'100%\' cellpadding="5px" padding="5px"><tbody><tr><td>'
            .'<div class=\'tip\'>'
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
	  
	//Show a Title dialog when the mouse is over
	function titleHelp($type)
	{
	 	switch ($type):
	 		default:
	      		$titleH="Click here & delete this to add your media";
		endswitch;
		echo $titleH;
	}
	?>   
    
        <Template title="<?php echo get_lang('TemplateTitleFirstPage'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Cover.png';?>" cat="presentation" style="elegant">
            <Description><?php echo get_lang('TemplateTitleFirstPageDescription'); ?></Description>
            <Html>
                <![CDATA[
                        <head>
                           <?php echo $css ?>
                           <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                        <style type="text/css">
						 /*Personnalize here your own cover
						  * More information at http://www.dokeos.com
						  */
						 #cover{
						  padding:1em 0px;
						 }
						</style>
                        </head>
                        <body>
                       <div id="course-content">
                       <div id="cover">
                        <h2 align="center">Your name</h2>               
                            <h1 align="center">Titulus</h1>
                            <div align="center"><img name="Logo" title="<?php titleHelp('logo'); ?>" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/help/media_help.png';?>" width="200px" height="200px" alt="Logo" /></div>
                           <h2 align="center">Organisation</h2>
                        </div>
                        </body>
                ]]>
            </Html>
        </Template>
        
        
        <Template title="<?php echo get_lang('TemplateTitleDedicatory'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Dedicace.png';?>" cat="presentation" style="elegant">
            <Description><?php echo get_lang('TemplateTitleDedicatoryDescription'); ?></Description>
            <Html>
                <![CDATA[
                        <head>
                           <?php echo $css ?>
                        <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                        </head>
                        <body>
                            <div id="course-content">
                                <table width="100%" cellspacing="10" cellpadding="10" border="0" id="dedicace">
                                    <tbody>
                                        <tr height="50">
                                            <td width="90%">&nbsp;</td>
                                            <td width="10%" rowspan="3">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td>
                                            <p align="right" class="dedicace" style="font-style: italic;">e.g. to L&eacute;on Werth, Mr Dokeos ...</p>
                                            <p style="font-style: italic;">                     </p>
                                            <p class="dedicace" style="text-align: right;"><span style="font-style: italic;">Click here...</span><br />                     		                                        </p
                                            </td>
                                        </tr>
                                        <tr height="30%">
                                            <td style="text-align: right;">&nbsp;<img width="250" height="250" align="bottom" alt="trainer_glasses.png" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/mr_dokeos/collaborative_big.png'?>" /></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </body>
                ]]>
            </Html>
        </Template>
        
        
        <Template title="<?php echo get_lang('TemplateTitlePreface'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Course_preface.png';?>" cat="presentation" style="elegant">
            <Description><?php echo get_lang('TemplateTitlePrefaceDescription'); ?></Description>
            <Html>
                <![CDATA[
                        <head>
                           <?php echo $css ?>
                        <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                        </head>
                        <body>
                       <div id="course-content">
                        <h1>Preface</h1>
                                                
                          <table width="100%" cellspacing="10" cellpadding="10" border="0" summary="" id="dedicace">
                              <tbody>
                                  <tr>
                                      <td width="75%" valign="top">                                 
                                              <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque. Sed quis arcu sed dolor laoreet dictum. Sed sed arcu laoreet nibh scelerisque tempor. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nam vitae purus id tortor adipiscing tincidunt. Nam pharetra, lorem vel ullamcorper tempus, turpis enim lacinia quam, id sollicitudin orci nunc et lectus. Pellentesque ut eros. Pellentesque lacus dui, ornare at, feugiat sollicitudin, porttitor quis, justo. Nulla nec augue. Curabitur mattis facilisis est.</p>
                                              <p style="text-align: right;font-style: italic;">e.g : Trainer Dokeos,</p>                     
                                      </td>
                                      <td width="25%" style="vertical-align: bottom;"><div align="center"><img align="bottom" height="250" width="auto" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/trainer/trainer_glasses.png'?>" alt="trainer_glasses.png" /></div></td>
                                </tr>
                              </tbody>
                          </table>
                        </div>
                        </body>
                ]]>
            </Html>
        </Template>
        
        
        <Template title="<?php echo get_lang('TemplateTitleIntroduction'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/intro.png';?>" cat="presentation" style="elegant">
            <Description><?php echo get_lang('TemplateTitleIntroductionDescription'); ?></Description>
            <Html>
                <![CDATA[
                        <head>
                           <?php echo $css ?>
                           <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                        </head>
                        <body>
                            <div id="course-content">
                            <h1>Introduction</h1>
                           <table width="100%" border="0" cellspacing="10" cellpadding="10">
                                  <tr>
                                    <td width="128"><img src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/presentation.jpg';?>" align="bottom" /></td>
                                    <td width="592" valign="top">
                                    <h2>Titulus</h2>
                                    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque. Sed quis arcu sed dolor laoreet dictum. Sed sed arcu laoreet nibh scelerisque tempor. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nam vitae purus id tortor adipiscing tincidunt. Nam pharetra, lorem vel ullamcorper tempus, turpis enim lacinia quam, id sollicitudin orci nunc et lectus. Pellentesque ut eros. Pellentesque lacus dui, ornare at, feugiat sollicitudin, porttitor quis, justo. Nulla nec augue. Curabitur mattis facilisis est. </p></td>
                                  </tr>
                                  <tr>
                                    <td colspan="2">
                                    <p>In hac habitasse platea dictumst. Suspendisse vulputate felis ac urna. Fusce pharetra ligula. Cras dui magna, elementum vitae, adipiscing eget, sodales commodo, nisl. Maecenas risus lectus, molestie et, lobortis sit amet, pulvinar eget, elit. In nisl sapien, rhoncus a, imperdiet in, cursus at, sem. In sit amet neque.</p>
                                    <ul>
                                      <li>Elementum vitae</li>
                                    </ul></td>
                                  </tr>
                                </table>
                            <br />
                            </div>
                        </body>
                ]]>
            </Html>
        </Template>
        
        <Template title="<?php echo get_lang('TemplateTitlePlan'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/bloom.png';?>" cat="presentation" style="elegant">
            <Description><?php echo get_lang('TemplateTitlePlanDescription'); ?></Description>
            <Html>
                <![CDATA[
                        <head>
                           <?php echo $css ?>
                           <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                        </head>
                        <body>
                           <div id="course-content">
                                <h1>Plan</h1>
                                <table width="100%" cellspacing="10" cellpadding="10" border="0" align="" summary="">
                                    <tbody>
                                        <tr>
                                            <td width="250" valign="middle">
                                            <div align="center"><img width="122" height="118" align="bottom" alt="servicesgather.png" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/servicesgather.png';?>" /><br /></div>
                                            </td>
                                            <td width="auto" valign="top" align="left" id="boom">
                                            <h2>Chap I<br /> </h2>
                                            <ul>
                                                <li>Cursus 1</li>
                                            </ul>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                          
                            </div>
                        </body>
                ]]>
            </Html>
        </Template>
    
    
    <Template title="<?php echo get_lang('TemplateTitleMrDokeos'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Teacher_explaining.png';?>" cat="content" style="elegant">
		<Description><?php echo get_lang('TemplateTitleMrDokeosDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    <style type="text/css">
					 #dialogFix{
					 background:url(<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/dialogs/dok_explain.jpg) no-repeat;
					 padding:20px 10px 30px 165px;
					 width:500px;
					 height:300px;
					 }
					</style>
                    </head>
				    <body>
                    <div id="course-content">
				    <h1>Titulus 1</h1>
                    <table width="510px" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td valign="top" id="dialogFix"><p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit...</p></td>
                        <td><img src="<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/dialogs/height_500px.png" width="10" height="250" /></td>
                      </tr>                      
                      <tr>
                        <td><div align="center"><img src="<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/dialogs/width_500px.png" width="500" height="10" /></div></td>
                        <td>&nbsp;</td>
                      </tr>
                    </table>
                                        
                    </div>
				    </body>
				    
			]]>
		</Html>
	</Template>
	
    
        <Template title="<?php echo get_lang('TemplateTitleTeacher'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Teacher_explaining.png';?>" cat="content" style="elegant">
		<Description><?php echo get_lang('TemplateTitleTeacherDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    <style type="text/css">
					 #dialogElastic{
					   -moz-border-radius:1em;
					   border:3px #E5E5E5 solid;
					   padding: 1em;
					 }
					 
/* Comment peut-être supprimé */
					 
					 #thinking{
					   background:url(<?php echo api_get_path(WEB_CODE_PATH)?>/default_course_document/images/dialogs/thinking.png) top right no-repeat;
					   width:100px;
					   height:100px;
					 }
					</style>
                    </head>
				    <body>
                    <div id="course-content">
				    <h1>Titulus 1</h1>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td  valign="top" id="thinking"><img src="<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/trainer/trainer_thinking.gif" width="184" height="300" align="top" /></td>
                        <td valign="top"><div id="dialogElastic"><p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Phasellus eleifend libero eget tortor. Mauris ac massa id orci viverra interdum.</p></div></td>
                      </tr>
                    </table>
                                        
                    </div>
				    </body>
				    
			]]>
		</Html>
	</Template>
    
     <Template title="<?php echo get_lang('TemplateTitleProduction'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/bloom.png';?>" cat="content" style="elegant">
            <Description><?php echo get_lang('TemplateTitleProductionDescription'); ?></Description>
            <Html>
                <![CDATA[
                        
                        <html xmlns="http://www.w3.org/1999/xhtml">
                          <head>
                              <?php echo $css ?>
                              <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                                                
                          </head>
                          <body>
                          <div id="course-content">
                                <h1>Production</h1>
                                <table width="100%" cellspacing="10" cellpadding="10" border="0" align="" summary="">
                                    <tbody>
                                        <tr>
                                            <td width="250" valign="middle">
                                            <div align="center"><img align="bottom" alt="computer.jpg" src="<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/computer.jpg" /><br /></div>
                                            </td>
                                            <td width="auto" valign="top" align="left" id="boom">
                                            <h2>Titulus</h2>
                                            <ul>
                                                <li>Elementum vitae</li>
                                            </ul>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                </div>
                        </body>
                        </html>
                        </body>
                        
                ]]>
            </Html>
        </Template>
    
         <Template title="<?php echo get_lang('TemplateTitleAnalyze'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/bloom.png';?>" cat="content" style="elegant">
            <Description><?php echo get_lang('TemplateTitleAnalyzeDescription'); ?></Description>
            <Html>
                <![CDATA[
                        
                        <html xmlns="http://www.w3.org/1999/xhtml">
                          <head>
                              <?php echo $css ?>
                                         <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>                         
                          </head>
                          <body>
                          <div id="course-content">
                                <h1>Analyze</h1>
                                <table width="100%" cellspacing="10" cellpadding="10" border="0" align="" summary="">
                                    <tbody>
                                        <tr>
                                            <td width="250" valign="middle">
                                            <div align="center"><img align="bottom" alt="computer.jpg" src="<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/search.jpg" /><br /></div>
                                            </td>
                                            <td width="auto" valign="top" align="left" id="boom">
                                            <h2>Titulus</h2>
                                            <ul>
                                                <li>Elementum vitae</li>
                                            </ul>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                </div>
                        </body>
                        </html>
                        </body>
                        
                ]]>
            </Html>
        </Template>
        

        
          <Template title="<?php echo get_lang('TemplateTitleSynthetize'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/bloom.png';?>" cat="content" style="elegant">
            <Description><?php echo get_lang('TemplateTitleSynthetizeDescription'); ?></Description>
            <Html>
                <![CDATA[
                        
                        <html xmlns="http://www.w3.org/1999/xhtml">
                          <head>
                              <?php echo $css ?>
                                         <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>                         
                          </head>
                          <body>
                          <div id="course-content">
                                <h1>Synthetize</h1>
                                <table width="100%" cellspacing="10" cellpadding="10" border="0" align="" summary="">
                                    <tbody>
                                        <tr>
                                            <td width="250" valign="middle">
                                            <div align="center"><img align="bottom" alt="search.jpg" src="<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/puzzle.jpg" /><br /></div>
                                            </td>
                                            <td width="auto" valign="top" align="left" id="boom">
                                            <h2>Titulus</h2>
                                            <ul>
                                                <li>Elementum vitae</li>
                                            </ul>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                </div>
                        </body>
                        </html>
                        </body>
                        
                ]]>
            </Html>
        </Template>
    
    <Template title="<?php echo get_lang('TemplateTitleText'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Text.png';?>" cat="content" style="elegant">
            <Description><?php echo get_lang('TemplateTitleTextDescription'); ?></Description>
            <Html>
                <![CDATA[
                        <head>
                           <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                        </head>
                        <body>
                        <div id="course-content">
                        <h1>Titulus 1</h1>
                        <h2>Titulus 2</h2>
                        <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque.</p>
                        <h3>Titulus 3</h3>
                        <p>Sed quis arcu sed dolor laoreet dictum. Sed sed arcu laoreet nibh scelerisque tempor. </p>
                        
                        </div>
                   	    </body>
                        
                ]]>
            </Html>
        </Template>
        
        <Template title="<?php echo get_lang('TemplateTitleLeftImage'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/img_left_encadred.png';?>" cat="content" style="elegant">
		<Description><?php echo get_lang('TemplateTitleLeftImageDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                    <div id="course-content">
				    <h1>Titulus 1</h1>
                     <table width="100%" cellspacing="10" cellpadding="10" border="0" summary="" id="teacherExplaining">
				      <tbody>
				        <tr>
				          <td valign="bottom">
				            <span style="font-weight: bold;"><img align="bottom" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/help/media_help.png';?>" alt=
				            "media_help.png" title="<?php titleHelp(); ?>" /><br /></span>
				          </td>
				          <td style="vertical-align: top; padding:1em;">
				            <h2>Titilus</h2>
							<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit...<p/>
				          </td>
				        </tr>
                        <tr>
                         <td colspan="2">
                          <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit...<p/>
                         </td>
                        </tr>
				      </tbody>
				
				    </table><br />
                    
                    </div>
				    </body>
				    
			]]>
		</Html>
	</Template>
    
    
        <Template title="<?php echo get_lang('TemplateTitleTextCentered'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/text_img_centered.png';?>" cat="content" style="elegant">
		<Description><?php echo get_lang('TemplateTitleTextCenteredDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				   </head>
                    <body>
                        <div id="course-content">
                        <h1>Titulus 1</h1>
                        <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Phasellus eleifend libero eget tortor. Mauris ac massa id orci viverra interdum. Pellentesque quis lacus. Fusce lacinia lacinia ipsum. Vivamus a quam. Aliquam lobortis vestibulum elit. Vivamus iaculis, ante ut iaculis placerat, est pede posuere sem, at feugiat lorem magna in enim. Etiam lacinia, justo a tincidunt pretium, massa justo aliquam purus, ac suscipit metus orci sed enim. Vestibulum a lacus. Maecenas lacus. Donec pede nibh, pellentesque vitae, rutrum porttitor, posuere at, mi. Duis ut nulla a quam laoreet rhoncus. Suspendisse potenti. In porttitor accumsan pede. Donec massa. Proin a est. In enim lectus, convallis ut, pretium malesuada, ultrices rutrum, felis. </p>
                        <div style="text-align: center;"><img align="bottom" src="<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/diagrams/pythagore.jpg" title="<?php titleHelp(); ?>" />&nbsp;                                                 <br /></div>
                        <div style="text-align: center;">Legendus</div>
                        <br />
                      <br />
                    <p>&nbsp;</p>
                    
                    </div>
                    </body>
                                                    
             ]]>
         </Html>
    </Template>
    
	
	<Template title="<?php echo get_lang('TemplateTitleComparison'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/comparison.png';?>" cat="content" style="elegant">
		<Description><?php echo get_lang('TemplateTitleComparisonDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    <style type="text/css">
					/*You can choose your color here
					  More info at http://www.dokeos.com*/
					
					#argument_1{
					  -moz-border-radius:1em;
					  border:1px solid black;
					  border:1px 2px 2px 1px;
					  background:#FEB1B3;
					  padding:1em;
					}
					
					#argument_2{
					  -moz-border-radius:1em;
					  border:1px solid black;
					  border:1px 2px 2px 1px;
					  background:#D4E0FF;
					  padding:1em;
					}
					</style>
                    
                    </head>
                    <body>
                    <div id="course-content">
                    <h1>Titulus 1</h1>
                    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque. Sed quis arcu sed dolor laoreet dictum. Sed sed arcu laoreet nibh scelerisque tempor. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nam vitae purus id tortor adipiscing tincidunt.</p>
                    <table width="100%" cellspacing="10" cellpadding="10" border="0" align="left" summary="">
                        <tbody>
                            <tr>
                                <td width="50%" id="argument_1" valign="top" align="left" style="padding: 1em;">
                                <h3>Objectum 1</h3>
                                <ul>
                                    <li>Elementum vitae I</li>
                                </ul>
                                </td>
                                <td width="50%" id="argument_2" valign="top" align="left" style="padding: 1em;">
                                <h3>Objectum 2</h3>
                                <ul>
                                    <li>Elementum vitae II</li>
                                </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <br />
                    <p>&nbsp;</p>
                    </div>
                </body>
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="<?php echo get_lang('TemplateTitleDiagram'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Diagram_explained.png';?>" cat="content" style="elegant">
		<Description><?php echo get_lang('TemplateTitleDiagramDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
                    <body>
                    <div id="course-content">
                    <h1>Titulus</h1>
                    <table width="100%" cellspacing="10" cellpadding="10" border="0" align="" summary="">
                        <tbody>
                            <tr>
                                <td width="40%"><div align="center"><img align="middle" height="auto" width="350" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/diagrams/alaska_chart.png'?>" alt="alaska_chart.png"/></div></td>
                                <td width="auto" align="left" valign="top"><h2>Titulus </h2>
                                <ul>
                                    <li>Elementum vitae</li>
                                </ul>
                              </td>
                            </tr>
                        </tbody>
                    </table>
                    <br />
                    <p>&nbsp;</p>
                    </div>
                </body>
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="<?php echo get_lang('TemplateTitleImage'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Picture.png';?>" cat="content" style="elegant">
		<Description><?php echo get_lang('TemplateTitleImageDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h1>Titulus 1</h1> 
				    <table width="100%" cellspacing="10" cellpadding="10" border="0" summary="">
				      <tbody>
				        <tr>
				          <td valign="middle">
				            <img width="auto" height="350" align="middle" alt="piano.jpg" src="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/images/diagrams/piano.jpg'?>" />
				          </td>
				        </tr>
				      </tbody>
				    </table>
				    <br />
                    <p>&nbsp;</p>
					</div>
				    </body>
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="<?php echo get_lang('TemplateTitleFlash'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Flash_animation_page.png';?>" cat="content" style="elegant">
		<Description><?php echo get_lang('TemplateTitleFlashDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                    <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
                   <h1>Titulus 1</h1>
                    <table width="100%" cellspacing="10" cellpadding="10" border="0" summary="">
                        <tbody>
                            <tr>
                                <td style="vertical-align: middle; text-align: center;"> <span style="font-style: italic;"><embed width="450" height="450" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="<?php echo api_get_path(REL_PATH); ?>main/default_course_document/flash/SpinEchoSequence.swf" play="true" loop="true" menu="true"></embed></span><br /> 				          </td>
                            </tr>
                        </tbody>
                    </table>
                    <br />
                    <p>&nbsp;</p>
					</div>
				    </body>
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="<?php echo get_lang('TemplateTitleAudio'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Audio_page.png';?>" cat="content" style="elegant">
		<Description><?php echo get_lang('TemplateTitleAudioDescription'); ?></Description>
		<Html>
			<![CDATA[
				    
				    <html xmlns="http://www.w3.org/1999/xhtml">
					  <head>
					    <title>Audio</title>
                          <?php echo $css ?>
                           <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
                                                    
					  </head>
					  <body>
                      <div id="course-content">
					    <h1>Titulus 1</h1>
                        <table width="100%" cellspacing="10" cellpadding="10" border="0" align="left" summary="">
                                    <tbody>
                                        <tr>
                                            <td valign="top"> 					            <span style="font-weight: bold;">
                                            <div style="text-align: center;"><img align="bottom" alt="Listening image" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/trainer/trainer_staring.png" /></div>
                                            <div style="text-align: center;"></div></span> 					          </td>
                                            <td rowspan="2" class="listeningaudio" style="vertical-align: top;"> 					            
                                            <h2>Titulus 2</h2>
                                            <p>In hac habitasse platea dictumst. Suspendisse vulputate felis ac urna. Fusce pharetra ligula. Cras dui magna, elementum vitae, adipiscing eget, sodales commodo, nisl. Maecenas risus lectus, molestie et, lobortis sit amet, pulvinar eget, elit. In nisl sapien, rhoncus a, imperdiet in, cursus at, sem. In sit amet neque.</p>
                                    <ul>
                                      <li>Elementum vitae</li>
                                    </ul>		           
                                           </td>
                                        </tr>
                                            <tr>
                                              <td valign="top">&nbsp;<div align="center"><span style="text-align: center;">
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
                    </div>
					</body>
					</html>
				    </body>
				    
			]]>
		</Html>
	</Template>
    
            <Template title="<?php echo get_lang('TemplateTitleSchema'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Audio_page.png';?>" cat="content" style="elegant">
            <Description><?php echo get_lang('TemplateTitleSchemaDescription'); ?></Description>
            <Html>
                <![CDATA[
                        
                        <html xmlns="http://www.w3.org/1999/xhtml">
                          <head>
                            <title>Audio</title>
                              <?php echo $css ?>
                                         <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>                         
                          </head>
                          <body>
                          <div id="course-content">
                            <h1>Titulus</h1>
                            <table width="100%" cellspacing="10" cellpadding="10" border="0" align="left" summary="">
                                        <tbody>
                                            <tr>
                                                <td valign="bottom">&nbsp;<div align="center"><span style="text-align: center;">
                                                    <object width="90" height="25" align="" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" id="test">
                                                        <param name="movie" value="<?php echo api_get_path(REL_PATH) ?>main/inc/lib/mp3player/player_mp3.swf?mp3file=/dokeosSVN/courses/FLV/document/audio/ListeningComprehension.mp3" />
                                                        <param name="quality" value="high" />
                                                        <param name="bgcolor" value="#FFFFFF" /> 
                                                        <embed width="90" height="25" align="" src="<?php echo api_get_path(REL_PATH) ?>main/inc/lib/mp3player/player_mp3.swf?mp3file=<?php echo api_get_path(REL_COURSE_PATH) ?>FLV/document/audio/ListeningComprehension.mp3" quality="high" bgcolor="#FFFFFF" name="Streaming" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>
                                                      </object>
                                              </span></div></td>
                                                <td rowspan="2" style="vertical-align: top;"> 					            
                                                     <div style="text-align: center;"><img align="bottom" alt="Listening image" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/diagrams/matching_electric_1.png" />          
                                               </td>
                                            </tr>
                                                <tr>
                                                  <td valign="bottom">
                                                  <span style="font-weight: bold;">
                                                <div style="text-align: center;"><img align="bottom" alt="Listening image" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/trainer/trainer_points_right.png" /></div>
                                               </td>
                                                </tr>
                                        </tbody>
                                    </table>
                        <br />
                        </div>
                        </body>
                        </html>
                        </body>
                        
                ]]>
            </Html>
        </Template>
	
	
	<Template title="<?php echo get_lang('TemplateTitleVideo'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Video.png';?>" cat="content" style="elegant">
		<Description><?php echo get_lang('TemplateTitleVideoDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h1>Video</h1>
				    <table width="100%" cellspacing="10" cellpadding="10" border="0" align="left" summary="">
				
				      <tbody>
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
                                            <p>In hac habitasse platea dictumst. Suspendisse vulputate felis ac urna. Fusce pharetra ligula. Cras dui magna, elementum vitae, adipiscing eget, sodales commodo, nisl. Maecenas risus lectus, molestie et, lobortis sit amet, pulvinar eget, elit. In nisl sapien, rhoncus a, imperdiet in, cursus at, sem. In sit amet neque.</p>
                                    <ul>
                                      <li>Elementum vitae</li>
                                    </ul>	
				          </td>
				        </tr>
                        <tr>
                          <td colspan="2">
                          <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Phasellus eleifend libero eget tortor.</p>
                          </td>
                        <tr>
				      </tbody>
				    </table>
                    <br />
                    <p>&nbsp;</p>
				    <style type="text/css">body{}</style><!-- to fix a strange bug appearing with firefox when editing this template -->
				    
				    </body>
			]]>
		</Html>
	</Template>
    
    
    <Template title="<?php echo get_lang('TemplateTitleVideoFullscreen'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Video_fullscreen.png';?>" cat="content" style="elegant">
		<Description><?php echo get_lang('TemplateTitleVideoFullscreenDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h1>Titulus</h1>
				    <table width="100%" cellspacing="10" cellpadding="10" border="0" align="left" summary="">			
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
                            <p>Lorem ipsum</p>
                          </td>
                          </tr>
				      </tbody>
				    </table>
                    <br />
                    <br />
				    <style type="text/css">body{}</style><!-- to fix a strange bug appearing with firefox when editing this template -->
				    
				    </body>
			]]>
		</Html>
	</Template>
	
	
	<Template title="<?php echo get_lang('TemplateTitleTable'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Table.png';?>" cat="content" style="elegant">
		<Description><?php echo get_lang('TemplateTitleTableDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h1>Titulus</h1>
				    
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
					</div>
				    </body>
				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="<?php echo get_lang('TemplateTitleAssigment'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Assignment_description.png';?>"  cat="ressources" style="elegant">
		<Description><?php echo get_lang('TemplateTitleAssigmentDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h1>Assignment description</h1>
				   <p> Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Phasellus eleifend libero eget tortor. Mauris ac massa id orci viverra interdum. Pellentesque quis lacus. Fusce lacinia lacinia ipsum. Vivamus a quam. Aliquam lobortis vestibulum elit. Vivamus iaculis, ante ut iaculis placerat, est pede posuere sem, at feugiat lorem magna in enim. Etiam lacinia, justo a tincidunt pretium, massa justo aliquam purus, ac suscipit metus orci sed enim.</p>
				          <h2>Goals</h2>
                          <ul>
                             <li>Cognoscere</li>
                             <li>Vitam comprendere</li>
                          
                          </ul>
                          <br/>
				          <table width="100%" cellspacing="10" cellpadding="10" border="1" align="" summary="">
				
				            <tbody>
				              <tr>
				                <td valign="top" style="font-weight: bold;">
				                  Documentation manager
				                </td>
				                <td valign="top">
				                  <img width="128" height="128" align="right" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/book.jpg" alt="book.jpg" /><p> Aristote </p>
				                </td>
				              </tr>
				
				              <tr>
				                <td valign="top" style="font-weight: bold;">
				                  Moderator
				                </td>
				                <td>
				                  <img width="128" height="128" align="right" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/interaction.jpg" alt="interaction.jpg" /><p>Socrate</p>
				                </td>
				              </tr>
				              <tr>
				
				                <td valign="top">
				                  <span style="font-weight: bold;">Tutor</span>
				                </td>
				                <td>
				                  <img width="128" height="128" align="right" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/group.jpg" alt="group.jpg" /><p>Centaurus</p>
				                </td>
				              </tr>
				            </tbody>
				
				          </table><br />
                          <h3>Agenda</h3>
                             <ul>
                                <li><span style="font-weight: bold;">Week 1</span> : Alea jacta est.</li>
                              
                            </ul>
				          <br />
				        </td>
				      </tr>
				    </table><br />
				    <br />
                    
					</div>
				    </body>
			]]>
		</Html>
	</Template>
	
	
	<Template title="<?php echo get_lang('TemplateTitleResources'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Resources.png';?>"  cat="ressources" style="elegant">
		<Description><?php echo get_lang('TemplateTitleResourcesDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				    <h1>Resources</h1>
				    
				    <table width="100%" cellspacing="10" cellpadding="10" border="0" align="left" summary="">
				      <tbody>
				        <tr>
				          <td>
				            
				            <font size="4" style="font-weight: bold;">Web</font><br />
				
				            <ul>
				              <li>
				                <a target="_blank" href="http://en.wikipedia.org/wiki/Cell_%28biology%29">Elementum vitae</a>, Visited : 01/00/00
				              </li>
				            </ul>
				            <hr style="width: 100%; height: 2px;" />
				            <font size="4" style="font-weight: bold;"><br />
				            Articles and Books</font><br />
				            <ul>
				
				              <li>Alberts B, Johnson A, Lewis J. et al. <span style="font-style: italic;">Molecular Biology of the Cell</span>, 4e. Garland Science. 2002
				              </li>
				            </ul>
				            <hr style="width: 100%; height: 2px;" />
				            <font size="4"><span style="font-weight: bold;"><br />
				            Tools</span></font><br />
				            <ul>
				              <li>
				                <a target="_blank" href="http://www.mozilla.com/en-US/">Firefox browser</a>
				              </li>
				              <li>
				                <a target="_blank" href="http://www.openoffice.org/">Open Office</a>
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
	
	<Template title="<?php echo get_lang('TemplateTitleBibliography'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Resources.png';?>"  cat="ressources" style="elegant">
		<Description><?php echo get_lang('TemplateTitleBibliographyDescription'); ?></Description>
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
                        <li>e.g : De Praetere, Th. The Demonstration by refutation of the Principle of Non-Contradiction in Aristotle's Metaphysics, book Gamma, Logique et Analyse, 143-144, 1995, pp. 343-365.</li>
                     </ul>
                     <h2>Periodic</h2>
                     <ul>
                        <li>e.g : L.M., Mashburn-Warren; Whiteley, M. (2006). "Special delivery: vesicle trafficking in prokaryotes.", <span style="font-style: italic;">Mol Microbiol</span> <span style="font-weight: bold;">61</span> (4): 839-46</li>
                     </ul>                     
					</div>
				    </body>
			]]>
		</Html>
	</Template>
    
    
	<Template title="<?php echo get_lang('TemplateTitleFAQ'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Frequently_asked_questions.png';?>" cat="ressources" style="elegant">
		<Description><?php echo get_lang('TemplateTitleFAQDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				   <h1>Frequently asked questions</h1>
				   
				
				    <table width="100%" cellspacing="10" cellpadding="10" border="0" align="left" summary="">
				      <tbody>
				        <tr>
				          <td>
				            <img width="128" height="128" align="right" src="<?php echo $_configuration['root_web']; ?>main/default_course_document/images/search.jpg" alt="search.jpg" />
				            <h2>Introduction</h2>
							<p>
				            Lorem ipsum dolor sit amet, consectetuer adipiscing elit...
                            </p>
				            <h3>Q : Quid ?</h3>
				            <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit...</p>
	
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
	
	<Template title="<?php echo get_lang('TemplateTitleGlossary'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Glossary.png';?>" cat="ressources" style="elegant">
		<Description><?php echo get_lang('TemplateTitleGlossaryDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				    </head>
				    <body>
                   <div id="course-content">
				   <h1>Glossary</h1>
 				   <?php 
				   $letter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
				   echo "<div id='glossary' text-align='center' style='text-align: center;'><font size='4'>";
					echo '<a href="#'.$letter[0].'">'.$letter[0].'</a>';
					for($i=1 ; $i<26 ; $i++)
					{
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
    
    
    <Template title="<?php echo get_lang('TemplateTitleEvaluation'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Audio_page.png';?>" cat="evaluation" style="elegant">
            <Description><?php echo get_lang('TemplateTitleEvaluationDescription'); ?></Description>
            <Html>
                <![CDATA[
                        
                        <html xmlns="http://www.w3.org/1999/xhtml">
                          <head>
                              <?php echo $css ?>
                                         <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>                         
                          </head>
                          <body>
                          <div id="course-content">
                                <h1>Evaluation</h1>
                                <table width="100%" cellspacing="10" cellpadding="10" border="0" align="" summary="" id="boom">
                                    <tbody>
                                        <tr>
                                            <td width="250" valign="middle">
                                            <div align="center"><img width="128" height="128" align="bottom" alt="redlight.jpg" src="<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/redlight.jpg" /><br /></div>
                                            </td>
                                            <td width="auto" valign="top" align="left" id="boom">
                                            <h2>Titulus 2</h2>
                                            <ul>
                                                <li>Elementum vitae</li>
                                            </ul>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                </div>
                        </body>
                        </html>
                        </body>
                        
                ]]>
            </Html>
        </Template>
    
    
	<Template title="<?php echo get_lang('TemplateTitleCertificate'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/Certificate_of_completion.png';?>" cat="evaluation" style="elegant">
		<Description><?php echo get_lang('TemplateTitleCertificateDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
                       <?php echo $css ?>
                                     <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'default_course_document/themes/elegant.css';?>"/>
				   
                    <style type="text/css">
					 /* HERE YOU CAN PERSONNALIZE YOUR OWN CERTIFICATE
					  * For more information or certificate template : go to http://www.dokeos.com
					  */
					  #certificate{
						/*background:url('Your image here') no-repeat;*/
					}  
										</style>
                    </head>
				    <body>
                   <div id="course-content">
				    <table width="100%" id="certificate" cellspacing="0" cellpadding="10" border="0" align="left" summary="">
				      <tbody>
                        <tr>
                          <td colspan="2" padding="0">
                           <h1 padding="0">Certificate</h1>
                          </td>
                        </tr>
				        <tr>
                          <td width="140px" padding="20"><img width="139" height="auto" align="middle" src="<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/logo.jpg" /></td>
				          <td  valign="top">
                    <h2>[Name of course]</h2> 				             First name : <br /><br />Second name : <br /><br />				 				              Date : <br /> 				              <br /> 				              <span style="font-weight: bold;">Attribued by :<br /></div><br /><br /><br /><br /><br /><br /><br /></span>
                    <div style="text-align: right;">
                    <img width="139" height="auto" align="middle" src="<?php echo api_get_path(WEB_CODE_PATH)?>default_course_document/images/signature.jpg" /></div>
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
	
</Templates>