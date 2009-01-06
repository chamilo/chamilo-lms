<?php
/*
 * Script to draw the results from a query
 * @package: dokeos.learnpath
 * @author: Diego Escalante Urrelo <diegoe@gmail.com>
 */
require api_get_path(LIBRARY_PATH).'search/search_widget.php';
require api_get_path(LIBRARY_PATH).'search/DokeosQuery.php';
require_once(api_get_path(LIBRARY_PATH).'search/IndexableChunk.class.php');
require_once api_get_path(LIBRARY_PATH).'/specific_fields_manager.lib.php';

$htmlHeadXtra[] = '
    <style type="text/css">
    .doc_table {
        width: 30%;
        text-align: left;
    }
    .doc_img {
        border: 1px solid black;
        padding: 1px solid white;
        background: white;
    }
    .doc_img,
    .doc_img img {
        width: 120px;
    }
    .doc_text,
    .doc_title {
        padding-left: 10px;
        vertical-align: top;
    }
    .doc_title {
        font-size: large;
        font-weight: bold;
        height: 2em;
    }

    .lightbox_link{
      padding-top: 10px;
          padding-bottom: 10px;
      text-align:center;
      font-size:26px;
    }

   .data_table{text-align:center;}

    .table_pager_1{z-index:10;height:20px;padding:5px}
     .cls{clear:both}
    </style>';


search_widget_prepare(&$htmlHeadXtra);

event_access_tool(TOOL_SEARCH);
$interbreadcrumb[]= array ("url"=>"./index.php", "name"=> get_lang('LectureLibrary'));//get_lang(ucfirst(TOOL_SEARCH))
Display::display_header(null,'Path');

if (api_get_setting('search_enabled') !== 'true') {
    Display::display_error_message(get_lang('SearchFeatureNotEnabledComment'));
    Display::display_footer();
}
else
{
    search_widget_show(empty($search_action)?null:'index.php');
}

$tag_array = array();
$specific_fields = get_specific_field_list();
foreach ($specific_fields as $specific_field) {
	if (isset($_REQUEST[ 'sf_'. $specific_field['code'] ])) {
		$values = $_REQUEST[ 'sf_'. $specific_field['code'] ];
		foreach ($values as $term) {
			if (!empty($term)) {
				$prefix = $specific_field['code'];
				$tag_array[] = dokeos_get_boolean_query($prefix . $term);
			}
		}
	}
}

$query = $_REQUEST['query'];
$query = stripslashes(htmlspecialchars_decode($query,ENT_QUOTES));

$op = 'or';
if (!empty($_REQUEST['operator']) && $_REQUEST['operator'] == 'and') {
    $op = $_REQUEST['operator'];	
}

$fixed_queries = array();
$course_filter = NULL;
if ( ($cid=api_get_course_id()) != -1 ) {
    // results only from actual course
    $course_filter = dokeos_get_boolean_query(XAPIAN_PREFIX_COURSEID . $cid);
}

if (count($tag_array)) {
	$fixed_queries = dokeos_join_queries($tag_array,null,$op);
	if ($course_filter != NULL) {
		$fixed_queries = dokeos_join_queries($fixed_queries, $course_filter, 'and');
	}
} else {
	if (!empty($query)) {
		$fixed_queries = array($course_filter);
	}
}

list($count, $results) = dokeos_query_query(mb_convert_encoding($query,'UTF-8',$charset), 0, 1000, $fixed_queries);

$blocks = array();

$url = api_get_path(WEB_CODE_PATH)."/newscorm/lp_controller.php";

$search_url = sprintf('%s?action=search&query=%s',
                $url, $_REQUEST['query']);

$link_format = $url.'?cidReq=%s&action=view&lp_id=%s&item_id=%s';

$learnings_id_list = array();
$mode = ($_GET['mode']!='default') ? 'gallery' : 'default';
if ($count > 0) {
    foreach ($results as $result) {
        $tags = '';
        foreach ($result->terms as $term) {
            $tags .= $term['name'] . ", ";
        }
        //remove trailing comma
        $tags = substr($tags,0,-2);

        if (!empty($result['url'])) {
            $a_prefix = '<a href="'.$result['url'].'">';
            $a_sufix = '</a>'; 
        }
        else {
            $a_prefix = '';
            $a_sufix = ''; 
        }

        if ($mode == 'gallery') {
            $title = $a_prefix.str_replace('_',' ',$result['title']). $a_sufix; //TODO: get author.(empty($row['author'])?'':''.$row['author']);
        } else {
            $title = '<div style="text-align:left;">'. $a_prefix . $result['title']. $a_sufix .(!empty($result['author'])?$result['author']:'').'<div>';
        }

        // Fill the result array
        if (empty($result['thumbnail'])) { // or !file_exists('../../courses/'.api_get_course_path($course_id)."/document/".$img_path)
            $result['thumbnail'] = '../img/no_document_thumb.jpg';
        }

        if ($mode == 'gallery') {
          $blocks[] = array(
              //'<a name="'.htmlentities('<div class="lightbox_link"><a href="'.$result['url'].'" target="_top">'.get_lang('GoToLearningPath').'</a></div>').'" rel="lightbox" href="'.$result['image'].'"><img src="'.$result['thumbnail'].'"></a>',
              $a_prefix .'<img src="'.$result['thumbnail'].'" />'. $a_sufix .'<br />'.$title.'<br />'.$result['author'],
              //$title,
          );
        } else {
          $blocks[] = array(
              $title,
          );
        }
    }
}


if (count($blocks) < 1) {
//    Display::display_normal_message(get_lang('SearchFeatureSearchExplanation'), FALSE);
}
else
{
    function to_link($i) {
        return sprintf('<a href="%s">%s</a>', $i, get_lang('ViewLearningPath'));
    }

    $s = new SortableTableFromArray($blocks);
    $s->display_mode = $mode;//default
    $s->display_mode_params = 3;
    $s->per_page = 9;
    $additional_parameters = array(
      'mode' => $mode,
      'action' => 'search',
      'query' => $_REQUEST['query'],
      );
    $get_params = '';
    foreach ($specific_fields as $specific_field) {
	    if (isset($_REQUEST[ 'sf_'. $specific_field['code'] ])) {
		    $values = $_REQUEST[ 'sf_'. $specific_field['code'] ];
		    $additional_parameters[ 'sf_'. $specific_field['code'] ] = $values;
		    foreach ( $values as $value ) {
			    $get_params .= '&sf_' . $specific_field['code'] .'[]='. $value;
		    }
		    $get_params .= '&';
	    }
    }
    $s->additional_parameters = $additional_parameters;

    if ($mode == 'default') {
      $s->set_header(0, get_lang('Lectures'));
    }

    $search_url = api_get_path(WEB_CODE_PATH)."search/index.php";

    echo '<div style="width:940px;border:1px solid #979797;position:relative;background-image:url(\'../img/search_background_bar.jpg\');background-repeat: repeat-x">';
    echo '<div style="width:100px;padding:4px;position:absolute;top:0px;z-index:9"><a ' .
        'href="'.$search_url.'?mode=gallery&action=search&query='.$_REQUEST['query'].$get_params.'"><img src="../img/'.(($mode=='gallery')?'ButtonGallOn':'ButtonGallOff').'.png"/></a><a ' .
        'href="'.$search_url.'?mode=default&action=search&query='.$_REQUEST['query'].$get_params.'"><img src="../img/'.(($mode=='default')?'ButtonListOn':'ButtonListOff').'.png"/></a>
  </div>';

    $s->display();
    echo '</div>';
}
Display::display_footer();
?>
