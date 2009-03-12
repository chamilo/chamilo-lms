<?php 
// setting the character set to UTF-8
header('Content-Type: text/xml; charset=utf-8');

// name of the language file that needs to be included
$language_file = 'document';

// including the global dokeos file
require_once('../../global.inc.php');

// outputting the opening tag of the xml file
echo '<?xml version="1.0" encoding="utf-8" ?>';

// is this needed? 
$IMConfig['base_url'] = $_configuration['root_web'].'main/img/gallery/';

// load a stylesheet
$css = loadCSS(api_get_setting('stylesheets'));

// setting some paths
$img_dir = api_get_path(REL_CODE_PATH).'img/';
$default_course_dir = api_get_path(REL_CODE_PATH).'default_course_document/';

// start the templates node
echo '<Templates imagesBasePath="">';

// load empty template
load_empty_template();

// load the templates that were defined by the platform admin
load_platform_templates();

// load the personal templates
load_personal_templates(api_get_user_id());

// load the hardcoded templates
load_hardcoded_templates();

// end the templates node
echo '</Templates>';
exit;


/**
 * load a given css style (default.css)
 *
 * @param string $css_name the folder name of the style
 * @return html code for adding a css style <style ...
 */
function loadCSS($css_name)
{
	$template_css = ' <style type="text/css">'.str_replace('../../img/',api_get_path(REL_CODE_PATH).'img/',file_get_contents(api_get_path(SYS_PATH).'main/css/'.$css_name.'/default.css')).'</style>';
	$template_css = str_replace('images/',api_get_path(REL_CODE_PATH).'css/'.$css_name.'/images/',$template_css);
	return $template_css;
}

/**
 * Transforms a language variable into XML-usable code
 *
 * @param unknown_type $var
 * @return unknown
 */
function s($var)
{
	global $charset;
    $search = array('&','<','>');
    $replace = array ('&amp;','&amp;lt;','&amp;gt;');
    return str_replace($search,$replace,mb_convert_encoding(get_lang($var),'UTF-8',$charset));
}

/**
 * Transforms a language variable into XML-usable code
 *
 * @param unknown_type $var
 * @return unknown
 */
function s2($var)
{
	global $charset;
    $search = array('&','<','>');
    $replace = array ('&amp;','&amp;lt;','&amp;gt;');
    return str_replace($search,$replace,mb_convert_encoding($var,'UTF-8',$charset));
}

/**
 * Load the platform templates as defined by the platform administrator in "Platform administration > Dokeos configuration settings > templates"
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version March 2009
 * @since Dokeos 1.8.6
 */
function load_platform_templates() {
	// Database table definition
	$table_template = Database::get_main_table('system_template');	
	
	$sql = "SELECT * FROM $table_template";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = Database::fetch_array($result)) {
        if (!empty($row['image'])) {
            $image = api_get_path(WEB_PATH).'home/default_platform_document/'.$row['image'];
        } else {
            $image = api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/noimage.gif';
        }
		echo '	<Template title="'.$row['title'].'" image="'.$image.'">
					<Description>'.$row['Description'].'</Description>
					<Html>
						<![CDATA[
							    '.$row['content'].'
						]]>
					</Html>
				</Template>';			
	}
}

/**
 * Load all the personal templates of the user when 
 *
 * @param integer $user_id the id of the user
 * @return xml node
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version March 2009
 * @since Dokeos 1.8.6 The code already existed but not in a function and a lot less performant. 
 */
function load_personal_templates($user_id=0) {
	global $_course; 

	// the templates that the user has defined are only available inside the course itself
	if (empty($_course))
	{
		return false;
	}
	
	// For which user are we getting the templates? 
	if ($user_id==0)
	{
		$user_id = api_get_user_id();
	}

	// Database table definition
	$table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);	
	$table_document = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);	
	
	
	// The sql statement for getting all the user defined templates
	$sql = "SELECT template.id, template.title, template.description, template.image, template.ref_doc, document.path 
			FROM ".$table_template." template, ".$table_document." document 
			WHERE user_id='".Database::escape_string($user_id)."'
			AND course_code='".Database::escape_string(api_get_course_id())."'
			AND document.id = template.ref_doc"; 
	$result_template = api_sql_query($sql,__FILE__,__LINE__);
	while ($row = Database::fetch_array($result_template))
	{
		$row['content'] = file_get_contents(api_get_path('SYS_COURSE_PATH').$_course['path'].'/document'.$row['path']);
		//$row['content'] = api_get_path('SYS_COURSE_PATH').$_course['path'].'/document'.$row['path'];
		
		if (!empty($row['image']))
		{
			$image = api_get_path(WEB_CODE_PATH).'upload/template_thumbnails/'.$row['image'];
		}
		else 
		{
			$image = api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/noimage.gif';
		}
		
		
		echo '	<Template title="'.s2($row['title']).'" image="'.$image.'">
					<Description>'.s2($row['Description']).'</Description>
					<Html>
						<![CDATA[
							    '.$row['content'].'
						]]>
					</Html>
				</Template>';		
	}	
}
function load_hardcoded_templates()
{
	global $css; 
	global $img_dir; 
	global $default_course_dir;
?>

        	
	<Template title="<?php echo s('TemplateTitleCourseTitle'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/coursetitle.gif'; ?>">
    <Description><?php echo s('TemplateTitleCourseTitleDescription'); ?></Description>
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
     
	<Template title="<?php echo s('TemplateTitleCheckList'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/checklist.gif'; ?>">
	<Description><?php echo s('TemplateTitleCheckListDescription'); ?></Description>
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
    <Template title="<?php echo s('TemplateTitleTeacher'); ?>"  image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/yourinstructor.gif'; ?>">
	<Description><?php echo s('TemplateTitleTeacherDescription'); ?></Description>
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
     
	<Template title="<?php echo s('TemplateTitleLeftList'); ?>"   image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/leftlist.gif'; ?>">
	<Description><?php echo s('TemplateTitleListLeftListDescription'); ?></Description>
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
	
	<Template title="<?php echo s('TemplateTitleLeftRightList'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/leftrightlist.gif'; ?>" >
	<Description><?php echo s('TemplateTitleLeftRightListDescription'); ?></Description>
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
	
	<Template title="<?php echo s('TemplateTitleRightList'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/rightlist.gif'; ?>" >
	<Description><?php echo s('TemplateTitleRightListDescription'); ?></Description>
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
    
	<Template title="<?php echo s('TemplateTitleComparison'); ?>"  image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/compare.gif'; ?>">
	<Description><?php echo s('TemplateTitleComparisonDescription'); ?></Description>
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
	
	
	<Template title="<?php echo s('TemplateTitleDiagram'); ?>"  image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/diagram.gif'; ?>">
		<Description><?php echo s('TemplateTitleDiagramDescription'); ?></Description>
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
	
	
	<Template title="<?php echo s('TemplateTitleDesc'); ?>"  image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/description.gif'; ?>">
		<Description><?php echo s('TemplateTitleDescDescription'); ?></Description>
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
	
	
	<Template title="<?php echo s('TemplateTitleObjectives'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/courseobjectives.gif'; ?>">
		<Description><?php echo s('TemplateTitleObjectivesDescription'); ?></Description>
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
	
	<Template title="<?php echo s('TemplateTitleCycle'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/cyclechart.gif'; ?>">
		<Description><?php echo s('TemplateTitleCycleDescription'); ?></Description>
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

	<Template title="<?php echo s('TemplateTitleLearnerWonder'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/learnerwonder.gif'; ?>">
	<Description><?php echo s('TemplateTitleLearnerWonderDescription'); ?></Description>
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



	<Template title="<?php echo s('TemplateTitleTimeline'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/phasetimeline.gif'; ?>">
	<Description><?php echo s('TemplateTitleTimelineDescription'); ?></Description>
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



	<Template title="<?php echo s('TemplateTitleStopAndThink'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/stopthink.gif'; ?>">
	<Description><?php echo s('TemplateTitleStopAndThinkDescription'); ?></Description>
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




<Template title="<?php echo s('TemplateTitleTable'); ?>"  image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/table.gif'; ?>">
	<Description><?php echo s('TemplateTitleTableDescription'); ?></Description>
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



<Template title="<?php echo s('TemplateTitleAudio'); ?>"  image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/audiocomment.gif'; ?>">
	<Description><?php echo s('TemplateTitleAudioDescription'); ?></Description>
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
						<embed  type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" width="300" height="20" bgcolor="#FFFFFF" src="<?php echo api_get_path(REL_CODE_PATH); ?>inc/lib/mediaplayer/player.swf" allowfullscreen="false" allowscriptaccess="always" flashvars="file=<?php echo $default_course_dir; ?>audio/ListeningComprehension.mp3&amp;autostart=true"></embed>
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

<Template title="<?php echo s('TemplateTitleVideo'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/video.gif'; ?>">
	<Description><?php echo s('TemplateTitleVideoDescription'); ?></Description>
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
			                
        <div style="border-style: none; overflow: hidden; height: 200px; width: 300px; background-color: rgb(220, 220, 220); background-image: url(<?php echo api_get_path(REL_PATH) ?>main/inc/lib/fckeditor/editor/plugins/flvPlayer/flvPlayer.gif); background-repeat: no-repeat; background-position: center center;"><script src="<?php echo api_get_path(REL_PATH) ?>main/inc/lib/fckeditor/editor/plugins/flvPlayer/swfobject.js" type="text/javascript"></script>
        <div id="player810625"><a href="http://www.macromedia.com/go/getflashplayer">Get the Flash Player</a> to see this player.
        <div id="player810625-config" style="overflow: hidden; display: none; visibility: hidden; width: 0px; height: 0px;">url=<?php echo api_get_path(REL_PATH) ?>main/<?php echo api_get_path(REL_CODE_PATH) ?>default_course_document/video/flv/example.flv width=400 height=200 loop=false play=false downloadable=false fullscreen=true displayNavigation=true displayDigits=true align=left dispPlaylist=none playlistThumbs=false</div>

        </div>
        <script type="text/javascript">
	var s1 = new SWFObject("<?php echo api_get_path(REL_PATH) ?>main/inc/lib/fckeditor/editor/plugins/flvPlayer/mediaplayer.swf","single","400","200","7");
	s1.addVariable("width","400");
	s1.addVariable("height","200");
	s1.addVariable("autostart","false");
	s1.addVariable("file","<?php echo api_get_path(REL_CODE_PATH) ?>default_course_document/video/flv/example.flv");
s1.addVariable("repeat","false");
	s1.addVariable("image","");
	s1.addVariable("showdownload","false");
	s1.addVariable("link","<?php echo api_get_path(REL_CODE_PATH) ?>default_course_document/video/flv/example.flv");
	s1.addParam("allowfullscreen","true");
	s1.addVariable("showdigits","true");
	s1.addVariable("shownavigation","true");
	s1.addVariable("logo","");
	s1.write("player810625");
</script></div>
			        
			        
			        
			        
			        
			        
			
			 
			          	
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
<Template title="<?php echo s('TemplateTitleFlash'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/flash.gif'; ?>">
	<Description><?php echo s('TemplateTitleFlashDescription'); ?></Description>
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
<?php
}
function load_empty_template()
{
	?>
<Template title="<?php echo s('Empty'); ?>" image="<?php echo api_get_path(WEB_CODE_PATH).'inc/lib/fckeditor/editor/dialog/fck_template/images/empty.gif'; ?>">
    <Description></Description>
    <Html>
        <![CDATA[
        	<head>
            	<?php echo $css ?>
            </head>
        ]]>    
    </Html>
</Template>
<?php
}
?>