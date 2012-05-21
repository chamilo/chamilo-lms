<?php
/* For licensing terms, see /license.txt */
/**
 * This is a learning path creation and player tool in Chamilo - previously
 * learnpath_handler.php
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update
 * @author Julio Montoya  - Improving the list of templates
 * @package chamilo.learnpath
 */
/**
 * INIT SECTION
 */

$this_section = SECTION_COURSES;

api_protect_course_script();

include 'learnpath_functions.inc.php';
include 'resourcelinker.inc.php';

$language_file = 'learnpath';
$htmlHeadXtra[] = '
<script type="text/javascript">

var temp    = false;
var load_default_template = '. ((isset($_POST['submit']) || empty($_SERVER['QUERY_STRING'])) ? 'false' : 'true' ) .';

function FCKeditor_OnComplete( editorInstance ) {
    editorInstance.Events.AttachEvent( \'OnSelectionChange\', check_for_title) ;
    document.getElementById(\'frmModel\').innerHTML = "<iframe id=\'frame_template\' name=\'my_frame_template\' height=890px width=220px; frameborder=0 src=\''.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/fckdialogframe.html \'>";
    loaded = true;
}

var hide_bar = function() {    
    $("#main_content .span3").hide();
    $("#doc_form").removeClass("span8"); 
    $("#doc_form").addClass("span11");  
    $("#hide_bar_template").css({"background-image" : \'url("../img/hide2.png")\'})
}

function check_for_title() {    
    
    if (temp) {  
    
        // This functions shows that you can interact directly with the editor area
        // DOM. In this way you have the freedom to do anything you want with it.

        // Get the editor instance that we want to interact with.
        var oEditor = FCKeditorAPI.GetInstance(\'content_lp\') ;

        // Get the Editor Area DOM (Document object).
        var oDOM = oEditor.EditorDocument ;

        var iLength ;
        var contentText ;
        var contentTextArray;
        var bestandsnaamNieuw = "";
        var bestandsnaamOud = "";

        // The are two diffent ways to get the text (without HTML markups).
        // It is browser specific.

        if( document.all )		// If Internet Explorer.
        {
            contentText = oDOM.body.innerText ;
        }
        else					// If Gecko.
        {
            var r = oDOM.createRange() ;
            r.selectNodeContents( oDOM.body ) ;
            contentText = r.toString() ;
        }

        var index=contentText.indexOf("/*<![CDATA");
        contentText=contentText.substr(0,index);

        // Compose title if there is none
        contentTextArray = contentText.split(\' \') ;
        var x=0;
        for(x=0; (x<5 && x<contentTextArray.length); x++) {
            if(x < 4)
            {
                bestandsnaamNieuw += contentTextArray[x] + \' \';
            }
            else
            {
                bestandsnaamNieuw += contentTextArray[x];
            }
        }
    }
    temp=true;

        
        
}

function InnerDialogLoaded() {
    if (document.all) {
        // if is iexplorer
        var B=new window.frames.content_lp___Frame.FCKToolbarButton(\'Templates\',window.content_lp___Frame.FCKLang.Templates);
    } else {
        var B=new window.frames[0].FCKToolbarButton(\'Templates\',window.frames[0].FCKLang.Templates);
    }

    return 	B.ClickFrame();
};'."\n".

$_SESSION['oLP']->get_js_dropdown_array() .

'function load_cbo(id){' ."\n" .
  'if (!id) {return false;}'.
  'var cbo = document.getElementById(\'previous\');' .
  'for(var i = cbo.length - 1; i > 0; i--) {' .
    'cbo.options[i] = null;' .
  '}' ."\n" .
  'var k=0;' .
  'for(var i = 1; i <= child_name[id].length; i++){' ."\n" .
  '  cbo.options[i] = new Option(child_name[id][i-1], child_value[id][i-1]);' ."\n" .
  '  k=i;' ."\n" .
  '}' ."\n" .
  //'if( typeof cbo != "undefined" ) {'."\n" .
  'cbo.options[k].selected = true;'."\n" .
   //'}'."\n" .
'}

$(function() {
    if ($(\'#previous\')) {
        if(\'parent is\'+$(\'#idParent\').val()) {
            load_cbo($(\'#idParent\').val());
        }
    }
    //Loads LP item tabs
    
    $("#resource_tab").tabs();
    $(\'.lp_resource_element\').click(function() {
        window.location.href = $(\'a\', this).attr(\'href\');
    });        
});
</script>';

/* Constants and variables */

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$isStudentView  = (int) $_REQUEST['isStudentView'];
$learnpath_id   = (int) $_REQUEST['lp_id'];
$submit			= $_POST['submit_button'];


$type = isset($_GET['type']) ? $_GET['type'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

// Using the resource linker as a tool for adding resources to the learning path.
if ($action == 'add' && $type == 'learnpathitem') {
     $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ((!$is_allowed_to_edit) || ($isStudentView)) {
    error_log('New LP - User not authorized in lp_add_item.php');
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
    exit;
}
/* SHOWING THE ADMIN TOOLS */

if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
            'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
            'name' => get_lang('ToolGradebook')
        );
}


$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list', 'name' => get_lang('LearningPaths'));
$interbreadcrumb[] = array('url' => api_get_self()."?action=build&lp_id=$learnpath_id", 'name' => $_SESSION['oLP']->get_name());

switch ($type) {
    case 'chapter':
        $interbreadcrumb[]= array ('url' => '#', 'name' => get_lang('NewChapter'));
        break;
    default:
        $interbreadcrumb[]= array ('url' => '#', 'name' => get_lang('NewStep'));
        break;
}

// Theme calls.
$show_learn_path = true;
$lp_theme_css = $_SESSION['oLP']->get_theme();

Display::display_header(null, 'Path');

$suredel = trim(get_lang('AreYouSureToDelete'));
//@todo move this somewhere else css/fix.css
?>
<style>
    #feedback { font-size: 1.4em; }
    #resExercise .ui-selecting { background: #FECA40; }
    #resExercise .ui-selected { background: #F39814; color: white; }
    #resExercise { list-style-type: none; margin: 0; padding: 0; width: 60%; }
    #resExercise li { margin: 3px; padding: 0.4em; font-size: 1.4em; height: 18px; }
</style>
    
<script type='text/javascript'>
function stripslashes(str) {
    str=str.replace(/\\'/g,'\'');
    str=str.replace(/\\"/g,'"');
    str=str.replace(/\\\\/g,'\\');
    str=str.replace(/\\0/g,'\0');
    return str;
}
function confirmation(name) {
    name=stripslashes(name);
    if (confirm("<?php echo $suredel; ?> " + name + " ?")) {
        return true;
    } else {
        return false;
    }
}

$(document).ready(function() {    
    $("#hide_bar_template").toggle(
        function() { 
            $("#main_content .span3").hide(); 
            $(this).css({'background-image' : 'url("../img/hide2.png")'})
            $("#doc_form").removeClass("span8"); 
            $("#doc_form").addClass("span11");   
        },
        function() { 
            $("#main_content .span3").show();
            $("#doc_form").removeClass("span11"); 
            $("#doc_form").addClass("span8"); 
            $(this).css('background-image', 'url("../img/hide0.png")'); 
        }            
    );    
});
</script>
<?php

/* DISPLAY SECTION */

echo $_SESSION['oLP']->build_action_menu();

echo '<div class="row-fluid" style="overflow:hidden">';
echo '<div class="span3">';

// Show the template list.
if ($type == 'document' && !isset($_GET['file'])) {
    $count_items = count($_SESSION['oLP']->ordered_items);
    $style = ($count_items > 12) ? ' style="height:250px;width:230px;overflow-x : auto; overflow-y : scroll;" ' : ' class="lp_tree" ';
    echo '<div  '.$style.'>';
    // Build the tree with the menu items in it.
    echo $_SESSION['oLP']->build_tree();
    echo '</div>';
    // Show the template list.
    echo '<p style="border-bottom:1px solid #ddd; margin:0; padding:2px;"></p>';
    echo '<br />';
    echo '<div id="frmModel" style="display:block; height:890px;width:100px; position:relative;"></div>';
} else {
    echo '<div class="lp_tree">';
    // Build the tree with the menu items in it.
    echo $_SESSION['oLP']->build_tree();
    echo '</div>';
}
echo '</div>';

//hide bar div
if ($action == 'add_item' && $type == 'document' && !isset($_GET['file'])) {
    echo '<div id="hide_bar_template" class="span1"></div>';
}

echo '<div id="doc_form" class="span8">';

    if (isset($new_item_id) && is_numeric($new_item_id)) {        
        switch ($type) {
            case 'chapter':
                echo $_SESSION['oLP']->display_manipulate($new_item_id, $_POST['type']);
                Display::display_confirmation_message(get_lang('NewChapterCreated'));
                break;
            case TOOL_LINK:
                echo $_SESSION['oLP']->display_manipulate($new_item_id, $type);
                Display::display_confirmation_message(get_lang('NewLinksCreated'));
                break;
            case TOOL_STUDENTPUBLICATION:
                echo $_SESSION['oLP']->display_manipulate($new_item_id, $type);
                Display::display_confirmation_message(get_lang('NewStudentPublicationCreated'));
                break;
            case 'module':
                echo $_SESSION['oLP']->display_manipulate($new_item_id, $type);
                Display::display_confirmation_message(get_lang('NewModuleCreated'));
                break;
            case TOOL_QUIZ:
                echo $_SESSION['oLP']->display_manipulate($new_item_id, $type);
                Display::display_confirmation_message(get_lang('NewExerciseCreated'));
                break;
            case TOOL_DOCUMENT:
                Display::display_confirmation_message(get_lang('NewDocumentCreated'));
                echo $_SESSION['oLP']->display_item($new_item_id, true);
                break;
            case TOOL_FORUM:
                echo $_SESSION['oLP']->display_manipulate($new_item_id, $type);
                Display::display_confirmation_message(get_lang('NewForumCreated'));
                break;
            case 'thread':
                echo $_SESSION['oLP']->display_manipulate($new_item_id, $type);
                Display::display_confirmation_message(get_lang('NewThreadCreated'));
                break;
        }
    } else {
        switch ($type) {
            case 'chapter':
                echo $_SESSION['oLP']->display_item_form($type, get_lang('EnterDataNewChapter'));
                break;
            case 'module':
                echo $_SESSION['oLP']->display_item_form($type, get_lang('EnterDataNewModule'));
                break;
            case 'document':
                if (isset($_GET['file']) && is_numeric($_GET['file'])) {
                    echo $_SESSION['oLP']->display_document_form('add', 0, $_GET['file']);
                } else {
                    echo $_SESSION['oLP']->display_document_form('add', 0);
                }
                break;
            case 'hotpotatoes':
                echo $_SESSION['oLP']->display_hotpotatoes_form('add', 0, $_GET['file']);
                break;
            case 'quiz':
                echo Display::display_warning_message(get_lang('ExerciseCantBeEditedAfterAddingToTheLP'));
                echo $_SESSION['oLP']->display_quiz_form('add', 0, $_GET['file']);
                break;
            case 'forum':
                echo $_SESSION['oLP']->display_forum_form('add', 0, $_GET['forum_id']);
                break;
            case 'thread':
                echo $_SESSION['oLP']->display_thread_form('add', 0, $_GET['thread_id']);
                break;
            case 'link':
                echo $_SESSION['oLP']->display_link_form('add', 0, $_GET['file']);
                break;
            case 'student_publication':
                echo $_SESSION['oLP']->display_student_publication_form('add', 0, $_GET['file']);
                break;
            case 'step':
                $_SESSION['oLP']->display_resources();
                break;
        }
    }
echo '</div>';
echo '</div>';

/* FOOTER */
Display::display_footer();
