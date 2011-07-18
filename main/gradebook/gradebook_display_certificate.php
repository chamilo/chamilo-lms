<?php
/* For licensing terms, see /license.txt */

$language_file = 'gradebook';

require_once '../inc/global.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_data_generator.class.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

//extra javascript functions for in html head:
$htmlHeadXtra[] =
"<script language='javascript' type='text/javascript'>
function confirmation() {
	if (confirm(\" ".trim(get_lang('AreYouSureToDelete'))." ?\"))
		{return true;}
	else
		{return false;} 
}
</script>";
api_block_anonymous_users();

if (!api_is_allowed_to_edit()) {
	api_not_allowed(true);
}
$interbreadcrumb[] = array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?',	'name' => get_lang('Gradebook'));
$interbreadcrumb[] = array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.Security::remove_XSS($_GET['cat_id']),'name' => get_lang('Details'));
$interbreadcrumb[] = array ('url' => 'gradebook_display_certificate.php?cat_id='.Security::remove_XSS($_GET['cat_id']),'name' => get_lang('GradebookListOfStudentsCertificates'));
$this_section = SECTION_COURSES;
Display::display_header('');

if (isset($_GET['user_id']) && $_GET['user_id']==strval(intval($_GET['user_id'])) && isset($_GET['cat_id']) && $_GET['cat_id']==strval(intval($_GET['cat_id']))) {
	if($_GET['action'] == 'delete') {
		$info=delete_certificate($_GET['cat_id'],$_GET['user_id']);
		if ($info===true) {
			Display::display_confirmation_message(get_lang('CertificateRemoved'));
		} else  {
			Display::display_error_message(get_lang('CertificateNotRemoved'));		
		}
	}	
}
echo Display::tag('h3', get_lang('GradebookListOfStudentsCertificates'));
?>

<table  class="data_table" border="0" width="100%" >
	<?php
	$cat_id=isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : null;

	//@todo replace all this code with something like get_total_weight()
    $cats = Category :: load ($cat_id, null, null, null, null, null, false);
    
    if (!empty($cats)) {
        
        //with this fix the teacher only can view 1 gradebook
        //$stud_id= (api_is_allowed_to_create_course() ? null : api_get_user_id());
        if (api_is_platform_admin()) {
            $stud_id= (api_is_allowed_to_create_course() ? null : api_get_user_id());
        } else {
            $stud_id= api_get_user_id();
        }
        
        $total_weight = $cats[0]->get_weight();
        
        $allcat  = $cats[0]->get_subcategories($stud_id, api_get_course_id(), api_get_session_id());
        $alleval = $cats[0]->get_evaluations($stud_id);
        $alllink = $cats[0]->get_links($stud_id);
        
        $datagen = new GradebookDataGenerator ($allcat,$alleval, $alllink);
        
        $total_resource_weight = 0;
        if (!empty($datagen)) {    
            $data_array = $datagen->get_data(GradebookDataGenerator :: GDG_SORT_NAME,0,null,true);
            if (!empty($data_array)) {
                $newarray = array();
                foreach ($data_array as $data) {
                    $newarray[] = array_slice($data, 1);
                }
                
                foreach($newarray as $item) {
                    $total_resource_weight = $total_resource_weight + $item['2'];
                }            
            }
        }        
        if ($total_resource_weight != $total_weight) {
            Display::display_warning_message(get_lang('SumOfActivitiesWeightMustBeEqualToTotalWeight'));
        }
    }
    
	$certificate_list = get_list_users_certificates($cat_id);
	
	
	if (count($certificate_list)==0) {
		echo Display::display_warning_message(get_lang('NoResultsAvailable'));
	} else {
		foreach ($certificate_list as $index=>$value) {
	?>
	<tr>
		<td width="100%" class="actions"><?php echo get_lang('Student').' : '.api_get_person_name($value['firstname'], $value['lastname']) ?>
		</td>		
	</tr>
	<tr>
	<td>
	<table class="data_table" width="100%" >
		<?php
		$list_certificate = get_list_gradebook_certificates_by_user_id ($value['user_id'],$cat_id);
		foreach ($list_certificate as $index_certificate=>$value_certificate) {
			?>
			<tr >
			<td width="50%"><?php echo get_lang('Score').' : '.$value_certificate['score_certificate'] ?></td>
			<td width="30%"><?php echo get_lang('Date').' : '.api_convert_and_format_date($value_certificate['created_at']) ?></td>			
			<td width="20%">
			<?php			
                $url = "index.php?export_certificate=yes&cat_id=".$cat_id."&user=".$value['user_id'];
    			$certificates = Display::url(Display::return_icon('certificate.png', get_lang('Certificates'), array(), 22), $url, array('target'=>'_blank'));
    			echo $certificates;
            ?>
			 <a  onclick="return confirmation();" href="gradebook_display_certificate.php?action=delete&<?php echo 'user_id='.$value_certificate['user_id'].'&amp;cat_id='.$value_certificate['cat_id'] ?>"><?php echo Display::return_icon('delete.png',get_lang('Delete')); ?></a>
			 </td>
			</tr>
			<?php
		}
		?>
		</table>
	</td>
	</tr>
    <?php
		}
	}
    ?>
</table>
<?php
Display::display_footer();