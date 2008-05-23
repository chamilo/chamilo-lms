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
$img_dir = api_get_path(WEB_IMG_PATH);
$default_course_dir = api_get_path(WEB_CODE_PATH).'default_course_document/';
//<Templates imagesBasePath="fck_template/images/">
?>
<Templates imagesBasePath="">	
<?php
	//Get all personnal templates in the database 	
	$table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);	
	$sql = 'SELECT id, title, description, ref_doc FROM '.$table_template.' WHERE course_code="'.api_get_course_id().'" AND user_id="'.api_get_user_id().'"';	
	$result_template = api_sql_query($sql,__FILE__,__LINE__);
	 
	$course = api_get_course_info();
	$table_document = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);	
	while($a_template = Database::fetch_array($result_template))
	{
		$document_id = $a_template['ref_doc'];				
		$sql_document_path = 'SELECT path FROM '.$table_document.' WHERE id="'.$document_id.'"';		
		$result_document_path = api_sql_query($sql_document_path,__FILE__,__LINE__);
		$document_path = Database::result($result_document_path,0,0);
		/*		
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
		*/
		//echo '<Template title="'.htmlentities($a_template['title']).'" image="'.api_get_path(WEB_CODE_PATH).'upload/template_thumbnails/'.$a_template['id'].'.jpg">';
		echo '<Template title="'.mb_convert_encoding($a_template['title'],'UTF-8',$charset).'" >';
			echo '<Description>'.mb_convert_encoding($a_template['description'],'UTF-8',$charset).'</Description>';
			echo '<Html>';		 	
			echo htmlentities(file_get_contents(api_get_path(SYS_COURSE_PATH).$course['path'].'/document'.$document_path));			
			echo '</Html>';
		echo '</Template>';			
	}	
?>    
<Template title="<?php echo get_lang('TemplateTitleCourseTitle'); ?>" >
    <Description><?php echo get_lang('TemplateTitleCourseTitleDescription'); ?></Description>
    <Html>
        <![CDATA[
           	<head>
            	<?php echo $css ?>
            	<style type="text/css">
            	.gris_title
            	{
            		color: silver;
            	}            	
            	h1
            	{
            		text-align: right;
            	}
				</style>
  
            </head>
            <body>
			<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
			<tbody>
			<tr>
			
			<td style="vertical-align: middle; width: 50%;" colspan="1" rowspan="1">
				<h1>TITULUS 1<br>
				<span class="gris_title">TITULUS 2</span><br>
				</h1>
			</td>
			
			<td style="width: 50%;">
				<img style="width: 100px; height: 100px;" alt="dokeos logo" src="<?php echo $default_course_dir.'images/logo_dokeos.png';?>"></td>
			</tr>
			</tbody>
			</table>
			<p><br>
			<br>
			</p>
			</body>
        ]]>
    </Html>
</Template>
     
	<Template title="<?php echo get_lang('TemplateTitleCheckList'); ?>" >
	<Description><?php echo get_lang('TemplateTitleCheckListDescription'); ?></Description>
	<Html>
	    <![CDATA[
	            <head>
	               <?php echo $css ?>	              
	            </head>
	            <body>
				<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
				<tbody>
				<tr>
				<td style="vertical-align: top; width: 66%;">						
				<h3>Lorem ipsum dolor sit amet</h3>
				<ul>
					<li>consectetur adipisicing elit</li>
					<li>sed do eiusmod tempor incididunt</li>
					<li>ut labore et dolore magna aliqua</li>
				</ul>
				
				<h3>Ut enim ad minim veniam</h3>							
				<ul>
					<li>quis nostrud exercitation ullamco</li>
					<li>laboris nisi ut aliquip ex ea commodo consequat</li>
					<li>Excepteur sint occaecat cupidatat non proident</li>
				</ul>
				
				<h3>Sed ut perspiciatis unde omnis</h3>				
				<ul>
					<li>iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam</li>
					<li>eaque ipsa quae ab illo inventore veritatis</li>
					<li>et quasi architecto beatae vitae dicta sunt explicabo.&nbsp;</li>
				</ul>
				
				</td>
				<td style="background: transparent url(<?php echo $img_dir.'postit.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; width: 33%; text-align: center; vertical-align: bottom;">
				<h3>Ut enim ad minima</h3>
				Veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur.<br>
				<h3>
				<img style="width: 180px; height: 144px;" alt="trainer" src="<?php echo $default_course_dir.'images/trainer/trainer_smile.png';?>"><br></h3>
				</td>
				</tr>
				</tbody>
				</table>
				<p><br>
				<br>
				</p>
				</body>
	    ]]>
	</Html>
	</Template>
    <Template title="<?php echo get_lang('TemplateTitleTeacher'); ?>" >
	<Description><?php echo get_lang('TemplateTitleTeacherDescription'); ?></Description>
	<Html>
		<![CDATA[
				<head>
                   <?php echo $css ?>
                   <style type="text/css">	            
	            	.text
	            	{	            	
	            		font-weight: normal;
	            	}
					</style>
                </head>                    
                <body>
					<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
					<tbody>
					<tr>
					<td></td>
					<td style="height: 33%;"></td>
					<td></td>
					</tr>
					<tr>
					<td style="width: 25%;"></td>
					<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right; font-weight: bold;" colspan="1" rowspan="1">
					<span class="text">
					<br>
					Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque.</span>
					</td>
					<td style="width: 25%; font-weight: bold;">
					<img style="width: 180px; height: 241px;" alt="trainer" src="<?php echo $default_course_dir.'images/trainer/trainer_case.png';?>"></td>
					</tr>
					</tbody>
					</table>
					<p><br>
					<br>
					</p>
				</body>				    
		]]>
	</Html>
</Template>
     
	<Template title="<?php echo get_lang('TemplateTitleLeftList'); ?>" >
	<Description><?php echo get_lang('TemplateTitleListLeftListDescription'); ?></Description>
	<Html>
	<![CDATA[
			<head>
	           <?php echo $css ?>
	       </head>		    
		    <body>
				<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
				<tbody>
				<tr>
				<td style="width: 66%;"></td>
				<td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="4">&nbsp;<img style="width: 180px; height: 248px;" alt="trainer" src="<?php echo $default_course_dir.'images/trainer/trainer_reads.png';?>"><br>
				</td>
				</tr>
				<tr align="right">
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">Lorem
				ipsum dolor sit amet.
				</td>
				</tr>
				<tr align="right">
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
				Vivamus
				a quam.&nbsp;<br>
				</td>
				</tr>
				<tr align="right">
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
				Proin
				a est stibulum ante ipsum.</td>
				</tr>
				</tbody>
				</table>
			<p><br>
			<br>
			</p>
			</body> 
		 		    
	]]>
	</Html>
	</Template>
	
	<Template title="<?php echo get_lang('TemplateTitleLeftRightList'); ?>" >
	<Description><?php echo get_lang('TemplateTitleLeftRightListDescription'); ?></Description>
	<Html>
	<![CDATA[
			<head>
	           <?php echo $css ?>
		    </head>
			<body>
				<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; height: 400px; width: 720px;" border="0" cellpadding="15" cellspacing="6">
				<tbody>
				<tr>
				<td></td>
				<td style="vertical-align: top;" colspan="1" rowspan="4">&nbsp;<img style="width: 180px; height: 294px;" alt="Trainer" src="<?php echo $default_course_dir.'images/trainer/trainer_join_hands.png';?>"><br>
				</td>
				<td></td>
				</tr>
				<tr>
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">Lorem
				ipsum dolor sit amet.
				</td>
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
				Convallis
				ut.&nbsp;Cras dui magna.</td>
				</tr>
				<tr>
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
				Vivamus
				a quam.&nbsp;<br>
				</td>
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
				Etiam
				lacinia stibulum ante.<br>
				</td>
				</tr>
				<tr>
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
				Proin
				a est stibulum ante ipsum.</td>
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
				Consectetuer
				adipiscing elit. <br>
				</td>
				</tr>
				</tbody>
				</table>
			<p><br>
			<br>
			</p>
			</body> 		    
	]]>
	</Html>
	</Template>
	
	<Template title="<?php echo get_lang('TemplateTitleRightList'); ?>" >
	<Description><?php echo get_lang('TemplateTitleRightListDescription'); ?></Description>
	<Html>
	<![CDATA[
			<head>
	           <?php echo $css ?>
		    </head>
		    <body style="direction: ltr;">
				<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
				<tbody>
				<tr>
				<td style="vertical-align: bottom; width: 50%;" colspan="1" rowspan="4"><img style="width: 300px; height: 199px;" alt="trainer" src="<?php echo $default_course_dir;?>images/trainer/trainer_points_right.png"><br>
				</td>
				<td style="width: 50%;"></td>
				</tr>
				<tr>
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
				Convallis
				ut.&nbsp;Cras dui magna.</td>
				</tr>
				<tr>
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
				Etiam
				lacinia.<br>
				</td>
				</tr>
				<tr>
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
				Consectetuer
				adipiscing elit. <br>
				</td>
				</tr>
				</tbody>
				</table>
			<p><br>
			<br>
			</p>
			</body>  
		 		    
	]]>
	</Html>
	</Template>
    
	<Template title="<?php echo get_lang('TemplateTitleComparison'); ?>" >
	<Description><?php echo get_lang('TemplateTitleComparisonDescription'); ?></Description>
	<Html>
		<![CDATA[
			<head>
            <?php echo $css ?>        
            </head>
            
            <body>
            	<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">				
				<tr>
					<td style="height: 10%; width: 33%;"></td> 
					<td style="vertical-align: top; width: 33%;" colspan="1" rowspan="2">&nbsp;<img style="width: 180px; height: 271px;" alt="trainer" src="<?php echo $default_course_dir.'images/trainer/trainer_standing.png';?>"><br>
					</td>
					<td style="height: 10%; width: 33%;"></td>
				</tr>
			<tr>
			<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
			Lorem ipsum dolor sit amet.
			</td>
			<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 33%;">
			Convallis
			ut.&nbsp;Cras dui magna.</td>
			</tr>			
			</body>
		]]>
	</Html>
</Template>
	
	
	<Template title="<?php echo get_lang('TemplateTitleDiagram'); ?>" >
		<Description><?php echo get_lang('TemplateTitleDiagramDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
	                   <?php echo $css ?>
				    </head>
				    
					<body>
					<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
					<tbody>
					<tr>
					<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; height: 33%; width: 33%;">
					<br>
					Etiam
					lacinia stibulum ante.
					Convallis
					ut.&nbsp;Cras dui magna.</td>
					<td colspan="1" rowspan="3">
						<img style="width: 350px; height: 267px;" alt="Alaska chart" src="<?php echo $default_course_dir.'images/diagrams/alaska_chart.png';?>"></td>
					</tr>
					<tr>
					<td colspan="1" rowspan="1">
					<img style="width: 300px; height: 199px;" alt="trainer" src="<?php echo $default_course_dir.'images/trainer/trainer_points_right.png';?>"></td>
					</tr>
					<tr>
					</tr>
					</tbody>
					</table>
					<p><br>
					<br>
					</p>
					</body>				    
			]]>
		</Html>
	</Template>
	
	
	<Template title="<?php echo get_lang('TemplateTitleDesc'); ?>">
		<Description><?php echo get_lang('TemplateTitleDescDescription'); ?></Description>
		<Html>
			<![CDATA[
					<head>
	                   <?php echo $css ?>
				    </head>
					<body>
						<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
						<tbody>
						<tr>
						<td style="width: 50%; vertical-align: top;">
							<img style="width: 48px; height: 49px; float: left;" alt="01" src="<?php echo $default_course_dir.'images/small/01.png';?>" hspace="5"><br>Lorem ipsum dolor sit amet<br><br><br>
							<img style="width: 48px; height: 49px; float: left;" alt="02" src="<?php echo $default_course_dir.'images/small/02.png';?>" hspace="5">
							<br>Ut enim ad minim veniam<br><br><br>
							<img style="width: 48px; height: 49px; float: left;" alt="03" src="<?php echo $default_course_dir.'images/small/03.png';?>" hspace="5">Duis aute irure dolor in reprehenderit<br><br><br>
							<img style="width: 48px; height: 49px; float: left;" alt="04" src="<?php echo $default_course_dir.'images/small/04.png';?>" hspace="5">Neque porro quisquam est</td>
							
						<td style="vertical-align: top; width: 50%; text-align: right;" colspan="1" rowspan="1">
							<img style="width: 300px; height: 291px;" alt="Gearbox" src="<?php echo $default_course_dir.'images/diagrams/gearbox.jpg';?>"><br></td>
						</tr><tr></tr>
						</tbody>
						</table>
						<p><br>
						<br>
						</p>
					</body>			    
			]]>
		</Html>
	</Template>
	
	
	<Template title="<?php echo get_lang('TemplateTitleObjectives'); ?>">
		<Description><?php echo get_lang('TemplateTitleObjectivesDescription'); ?></Description>
		<Html>
			<![CDATA[
				<head>
	               <?php echo $css ?>                    
			    </head>	
			    
			    <body>
					<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
					<tbody>
					<tr>
					<td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="2">
					<img style="width: 180px; height: 271px;" alt="trainer" src="<?php echo $default_course_dir.'/images/trainer/trainer_chair.png';?>"><br>
					</td>
					<td style="height: 10%; width: 66%;"></td>
					</tr>
					<tr>
					<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 66%;">
					<h3>Lorem ipsum dolor sit amet</h3>
					<ul>
					<li>consectetur adipisicing elit</li>
					<li>sed do eiusmod tempor incididunt</li>
					<li>ut labore et dolore magna aliqua</li>
					</ul>
					<h3>Ut enim ad minim veniam</h3>
					<ul>
					<li>quis nostrud exercitation ullamco</li>
					<li>laboris nisi ut aliquip ex ea commodo consequat</li>
					<li>Excepteur sint occaecat cupidatat non proident</li>
					</ul>
					</td>
					</tr>
					</tbody>
					</table>
				<p><br>
				<br>
				</p>
				</body>				    
			]]>
		</Html>
	</Template>
	
	<Template title="<?php echo get_lang('TemplateTitleCycle'); ?>">
		<Description><?php echo get_lang('TemplateTitleCycleDescription'); ?></Description>
		<Html>
			<![CDATA[
				<head>
	               <?php echo $css ?>
	               <style>
	               .title
	               {
	               	color: white; font-weight: bold;
	               }
	               </style>                    
			    </head>
			    	
			    	    
			    <body>
				<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="8" cellspacing="6">
				<tbody>
				<tr>
					<td style="text-align: center; vertical-align: bottom; height: 10%;" colspan="3" rowspan="1">
						<img style="width: 250px; height: 76px;" alt="arrow" src="<?php echo $default_course_dir.'images/diagrams/top_arrow.png';?>">
					</td>				
				</tr>			
				<tr>
					<td style="height: 5%; width: 45%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
						<span class="title">Lorem ipsum</span>
					</td>
						
					<td style="height: 5%; width: 10%;"></td>					
					<td style="height: 5%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
						<span class="title">Sed ut perspiciatis</span>
					</td>
				</tr>
					<tr>
						<td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
							<ul>
								<li>dolor sit amet</li>
								<li>consectetur adipisicing elit</li>
								<li>sed do eiusmod tempor&nbsp;</li>
								<li>adipisci velit, sed quia non numquam</li>
								<li>eius modi tempora incidunt ut labore et dolore magnam</li>
							</ul>
				</td>			
				<td style="width: 10%;"></td>
				<td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
					<ul>
					<li>ut enim ad minim veniam</li>
					<li>quis nostrud exercitation</li><li>ullamco laboris nisi ut</li>
					<li> Quis autem vel eum iure reprehenderit qui in ea</li>
					<li>voluptate velit esse quam nihil molestiae consequatur,</li>
					</ul>
					</td>
					</tr>
					<tr align="center">
					<td style="height: 10%; vertical-align: top;" colspan="3" rowspan="1">
					<img style="width: 250px; height: 76px;" alt="arrow" src="<?php echo $default_course_dir.'images/diagrams/bottom_arrow.png';?>">&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;
				</td>
				</tr>			
				</tbody>
				</table>
				<p><br>
				<br>
				</p>
				</body>	    
			   		    
			]]>
		</Html>
	</Template>

	<Template title="<?php echo get_lang('TemplateTitleLearnerWonder'); ?>">
	<Description><?php echo get_lang('TemplateTitleLearnerWonderDescription'); ?></Description>
	<Html>
		<![CDATA[
			<head>
               <?php echo $css ?>                    
		    </head>
		    
		    <body>
				<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
				<tbody>
				<tr>
				<td style="width: 33%;" colspan="1" rowspan="4">
					<img style="width: 120px; height: 348px;" alt="learner wonders" src="<?php echo $default_course_dir.'images/silhouette.png';?>"><br>
				</td>
				<td style="width: 66%;"></td>
				</tr>
				<tr align="center">
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
				Convallis
				ut.&nbsp;Cras dui magna.</td>
				</tr>
				<tr align="center">
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
				Etiam
				lacinia stibulum ante.<br>
				</td>
				</tr>
				<tr align="center">
				<td style="background: transparent url(<?php echo $img_dir.'faded_grey.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
				Consectetuer
				adipiscing elit. <br>
				</td>
				</tr>
				</tbody>
				</table>
			<p><br>
			<br>
			</p>
			</body>


		    
		   		    
		]]>
	</Html>
</Template>



	<Template title="<?php echo get_lang('TemplateTitleTimeline'); ?>">
	<Description><?php echo get_lang('TemplateTitleTimelineDescription'); ?></Description>
	<Html>
		<![CDATA[
			<head>
               <?php echo $css ?> 
				<style>
				.title
				{				
					font-weight: bold; text-align: center; 	
				}			
				</style>                
		    </head>	
		    
		    <body>
				<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="8" cellspacing="5">
				<tbody>
				<tr class="title">				
					<td style="vertical-align: top; height: 3%; background-color: rgb(224, 224, 224);">Lorem ipsum</td>
					<td style="height: 3%;"></td>
					<td style="vertical-align: top; height: 3%; background-color: rgb(237, 237, 237);">Perspiciatis</td>
					<td style="height: 3%;"></td>
					<td style="vertical-align: top; height: 3%; background-color: rgb(245, 245, 245);">Nemo enim</td>
				</tr>
				
				<tr>
					<td style="vertical-align: top; width: 30%; background-color: rgb(224, 224, 224);">
						<ul>
						<li>dolor sit amet</li>
						<li>consectetur</li>
						<li>adipisicing elit</li>
					</ul>
					<br>
					</td>
					<td>
						<img style="width: 32px; height: 32px;" alt="arrow" src="<?php echo $default_course_dir.'/images/small/arrow.png';?>">
					</td>
					
					<td style="vertical-align: top; width: 30%; background-color: rgb(237, 237, 237);">
						<ul>
							<li>ut labore</li>
							<li>et dolore</li>
							<li>magni dolores</li>
						</ul>
					</td>
					<td>
						<img style="width: 32px; height: 32px;" alt="arrow" src="<?php echo $default_course_dir.'/images/small/arrow.png';?>">
					</td>
					
					<td style="vertical-align: top; background-color: rgb(245, 245, 245); width: 30%;">
						<ul>
							<li>neque porro</li>
							<li>quisquam est</li>
							<li>qui dolorem&nbsp;&nbsp;</li>
						</ul>
						<br><br>
					</td>
				</tr>
				</tbody>
				</table>
			<p><br>
			<br>
			</p>
			</body>

		]]>
	</Html>
</Template>



	<Template title="<?php echo get_lang('TemplateTitleStopAndThink'); ?>">
	<Description><?php echo get_lang('TemplateTitleStopAndThinkDescription'); ?></Description>
	<Html>
		<![CDATA[
			<head>
               <?php echo $css ?>                    
		    </head>
		    <body>
				<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
				<tbody>
				<tr>
				<td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="2">
					<img style="width: 180px; height: 169px;" alt="trainer" src="<?php echo $default_course_dir.'/images/trainer/trainer_staring.png';?>">
				<br>
				</td>
				<td style="height: 10%; width: 66%;"></td>
				</tr>
				<tr>
				<td style="background: transparent url(<?php echo $img_dir.'postit.png'; ?>) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; width: 66%; vertical-align: middle; text-align: center;">
					<h3>Attentio sectetur adipisicing elit</h3>
					<ul>
						<li>sed do eiusmod tempor incididunt</li>
						<li>ut labore et dolore magna aliqua</li>
						<li>quis nostrud exercitation ullamco</li>
					</ul><br></td>
				</tr>
				</tbody>
				</table>
			<p><br>
			<br>
			</p>
			</body>
		]]>
	</Html>
</Template>




<Template title="<?php echo get_lang('TemplateTitleTable'); ?>" >
	<Description><?php echo get_lang('TemplateTitleTableDescription'); ?></Description>
	<Html>
		<![CDATA[
				<head>
                   <?php echo $css ?>
                   <style type="text/css">
				.title
				{
					font-weight: bold; text-align: center;
				}
				
				.items
				{
					text-align: right;
				}	
  				

					</style>
  
			    </head>
			    <body>
			    <br />
			   <h2>A table</h2>
				<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px;" border="1" cellpadding="5" cellspacing="0">
				<tbody>
				<tr class="title">
					<td>Country</td>
					<td>2005</td>
					<td>2006</td>
					<td>2007</td>
					<td>2008</td>
				</tr>
				<tr class="items">
					<td>Bermuda</td>
					<td>10,40</td>
					<td>8,95</td>
					<td>9,19</td>
					<td>9,76</td>
				</tr>
				<tr class="items">
				<td>Canada</td>
					<td>18,39</td>
					<td>17,52</td>
					<td>16,57</td>
					<td>16,60</td>
				</tr>
				<tr class="items">
				<td>Greenland</td>
					<td>0,10</td>
					<td>0,10</td>
					<td>0,05</td>
					<td>0,05</td>
				</tr>
				<tr class="items">
				<td>Mexico</td>
					<td>3,38</td>
					<td >3,63</td>
					<td>3,63</td>
					<td>3,54</td>
				</tr>
				</tbody>
				</table>
				<br>
				</body>
			    
		]]>
	</Html>
</Template>



<Template title="<?php echo get_lang('TemplateTitleAudio'); ?>" >
	<Description><?php echo get_lang('TemplateTitleAudioDescription'); ?></Description>
	<Html>
		<![CDATA[
			<head>
               <?php echo $css ?>                    
		    </head>
                   <body>
					<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
					<tbody>
					<tr>
					<td>
					
					<div align="center">
					<span style="text-align: center;">
                        <object width="90" height="25" align="" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" id="test">
                            <param name="movie" value="<?php echo api_get_path(WEB_CODE_PATH) ?>inc/lib/mp3player/player_mp3.swf?autostart=true&mp3file=<?php echo $default_course_dir;?>audio/ListeningComprehension.mp3" />
                            <param name="quality" value="high" />
                            <param name="bgcolor" value="#FFFFFF" /> 
                            <embed width="90" height="25" align="" src="<?php echo api_get_path(WEB_CODE_PATH) ?>inc/lib/mp3player/player_mp3.swf?autostart=true&mp3file=<?php echo $default_course_dir;?>audio/ListeningComprehension.mp3" quality="high" bgcolor="#FFFFFF" name="Streaming" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>
                          </object>
                    </span></div>     
					
					<br>
					</td>
					<td colspan="1" rowspan="3"><br>
						<img style="width: 300px; height: 341px; float: right;" alt="image" src="<?php echo $default_course_dir.'images/diagrams/head_olfactory_nerve.png';?>"><br></td>
					</tr>
					<tr>
					<td colspan="1" rowspan="1">
						<img style="width: 180px; height: 271px;" alt="trainer" src="<?php echo $default_course_dir.'images/trainer/trainer_glasses.png';?>"><br></td>
					</tr>
					<tr>
					</tr>
					</tbody>
					</table>
					<p><br>
					<br>
					</p>
					</body>					
		]]>
	</Html>
</Template>

<Template title="<?php echo get_lang('TemplateTitleVideo'); ?>">
	<Description><?php echo get_lang('TemplateTitleVideoDescription'); ?></Description>
	<Html>
		<![CDATA[
			<head>
            	<?php echo $css ?>
			</head>
			
			<body>
			<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
			<tbody>
			<tr>
			<td style="width: 50%; vertical-align: top;">
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
			<td style="background: transparent url(../main/img/faded_grey.png) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 50%;">
			<h3><br>
			</h3>
			<h3>Lorem ipsum dolor sit amet</h3>
				<ul>
				<li>consectetur adipisicing elit</li>
				<li>sed do eiusmod tempor incididunt</li>
				<li>ut labore et dolore magna aliqua</li>
				</ul>
			<h3>Ut enim ad minim veniam</h3>
				<ul>
				<li>quis nostrud exercitation ullamco</li>
				<li>laboris nisi ut aliquip ex ea commodo consequat</li>
				<li>Excepteur sint occaecat cupidatat non proident</li>
				</ul>
			</td>
			</tr>
			</tbody>
			</table>
			<p><br>
			<br>
			</p>
			 <style type="text/css">body{}</style><!-- to fix a strange bug appearing with firefox when editing this template -->
			</body>
			   
		]]>
	</Html>
</Template>
<Template title="<?php echo get_lang('TemplateTitleFlash'); ?>">
	<Description><?php echo get_lang('TemplateTitleFlashDescription'); ?></Description>
	<Html>
		<![CDATA[
			<head>
               <?php echo $css ?>                    
		    </head>				    
		    <body>
		    <center>
				<table style="background: transparent url(<?php echo $img_dir.'faded_blue_horizontal.png'; ?>) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 100%; height: 400px;" border="0" cellpadding="15" cellspacing="6">
				<tbody>
					<tr>
					<td align="center">
					<embed width="700" height="300" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="<?php echo $default_course_dir;?>flash/SpinEchoSequence.swf" play="true" loop="true" menu="true"></embed></span><br /> 				          													
					</td>
					</tr>
				</tbody>
				</table>
				<p><br>
				<br>
				</p>
			</center>
			</body>
			    
		]]>
	</Html>
</Template>	
</Templates>