<?php //$id: $
/**
 * This script is the main client script. It calls the search server to get an XML document that
 * represents the list of results to the term searched.
 * It parses the XML document, checks user permissions and displays a set of results in a nice
 * format.
 * @package dokeos.search
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
/**
 * Variables
 */
require_once('../../../main/inc/global.inc.php');
require ('filter_user.lib.php');
require ('client.conf.php');
api_block_anonymous_users();
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="search.css" />';

$start_time = time();
$xml_file = $server_url.'?'.$_SERVER['QUERY_STRING'];
//if(!$doc = xmldocfile($xml_file)){
$results = simplexml_load_file($xml_file);
if($results === false)
{
	$res = array();
}
else
{
	//$doc->load($xml_file);
	$subTotals = array();
	$lasttag = '';
	$myindex = 0;
	$level = 0;
	//$root = $doc->root();
	//$root = $doc->documentElement;
	$my_query = $results->query;
	$my_search_info = $results->search_info;
	$my_search_term = $results->search_term;
	$my_num_found = $results->num_found;
	$my_search_time = $results->search_time;
	$elementCount = 1;
}
/**
 * This function is just a display helper.
 * @param	integer	Result ID
 * @param	string	Result title
 * @param	string	Result URL
 * @param	string	Short excerpt of the result document
 * @param
 */
function result_output($id,$title,$url='',$excerpt='',$date='',$rating=''){
	if(empty($id) OR empty($title)){return false;}
	$title = urldecode($title);
	$title = preg_replace('/\?cidReq=.*$/','',$title);
        $excerpt = preg_replace('/<hl>\s*(<hl>)?/','<div class="highlight">',$excerpt);
        $excerpt = preg_replace('/<\/hl>\s*(<\/hl>)?/','</div> ',$excerpt);
        $excerpt = stripslashes($excerpt);
	$string = "<div class='result'>\n" .
			"<div class='title'>$id. <a href='$url'>$title</a> - $date - $rating</div>\n" .
			"<div class='description'>$excerpt</div>\n" .
			"</div>\n";
	//$string = "$id. <a href='$url'>$title</a> - $date<br/><i>$excerpt</i><br/><br/>";
	return $string;
}

include('../../../main/inc/header.inc.php');
?>

<form method="get" action="<?php echo $search_url; ?>"><input
type="hidden" name="ps" value="1000"/><input
type="hidden" name="o" value="0"/><input
type="hidden" name="m" value="any"/><input
type="hidden" name="wm" value="sub"/><input
type="hidden" name="wf" value="2221"/><input
type="hidden" name="s" value="RDP"/><input
type="hidden" name="sy" value="1"/><input
type="text" name="q" value="<?php echo urldecode($my_query);?>" size="10" style="margin: 4px 6px; border: 1px solid #B6BB8C; color:#4D4F3A; height: 15px;padding:0px;"><input
type="submit" name="submit" value="<?php echo $lang_search_button; ?>" style="margin: 4px 6px; border: 1px solid #B6BB8C; color:#4D4F3A; height:17px;vertical-align:top;padding:0px"></form>
<?php
$i = 1;
$to_print = '';
foreach($results->result as $res){
	if(access_check($res->result_du)){
		$to_print .= result_output($i,api_convert_encoding(urldecode($res->result_dt),$charset,'utf-8'),$res->result_du,api_html_entity_decode(urldecode($res->result_de)),api_htmlentities(urldecode($res->result_dm)),$res->result_dr);
		$i++;
	}
}
//TODO check if a time and number of results is defined
$i--;
if($to_print != ''){
	//$time = $res['search_time'] + (time() - $start_time);
	//echo "<div class='search_info'>".$i.' '.$lang_search_found.' '.$time." $lang_seconds</div><br/>\n";
	echo "<div class='search_info'>".$i.' '.$lang_search_found."</div><br/>\n";
	echo $to_print;
}else{
	echo "<div class='search_info'>".$lang_no_result_found."</div><br/>\n";
}
include('../../../main/inc/footer.inc.php');
?>
