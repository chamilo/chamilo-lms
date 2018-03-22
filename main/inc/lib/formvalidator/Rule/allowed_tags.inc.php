<?php
/* For licensing terms, see /license.txt */

/**
 * This page defines all HTML-tages and their attributes that are allowed in
 * Chamilo. 2 arrays are defined, one contains the allowed HTML for students and
 * the other the allowed HTML for teachers.
 *
 * Modifying this page:
 * - for each allowed tag there should be a line like
 *    $allowed_tags_XXXX   ['tagname'] = array();
 * - for each of the attributes allowed in the tag, there should be a line like
 *    $allowed_tags_XXXX['tagname']['attributename'] = array();
 * - please keep the content of this file alphabetically structured
 *
 * @see http://www.w3schools.com/tags/
 */

// KSES-COMPATIBLE SETTINGS

// ALLOWED HTML FOR STUDENTS

// a
$allowed_tags_student['a'] = [];
$allowed_tags_student['a']['class'] = [];
$allowed_tags_student['a']['dir'] = [];
$allowed_tags_student['a']['id'] = [];
$allowed_tags_student['a']['href'] = [];
$allowed_tags_student['a']['lang'] = [];
$allowed_tags_student['a']['name'] = [];
$allowed_tags_student['a']['rel'] = [];
$allowed_tags_student['a']['rev'] = [];
$allowed_tags_student['a']['style'] = [];
$allowed_tags_student['a']['target'] = [];
$allowed_tags_student['a']['title'] = [];
$allowed_tags_student['a']['xml:lang'] = [];

// abbr
$allowed_tags_student['abbr'] = [];
$allowed_tags_student['abbr']['class'] = [];
$allowed_tags_student['abbr']['dir'] = [];
$allowed_tags_student['abbr']['id'] = [];
$allowed_tags_student['abbr']['lang'] = [];
$allowed_tags_student['abbr']['style'] = [];
$allowed_tags_student['abbr']['title'] = [];
$allowed_tags_student['abbr']['xml:lang'] = [];

// acronym
$allowed_tags_student['acronym'] = [];
$allowed_tags_student['acronym']['class'] = [];
$allowed_tags_student['acronym']['dir'] = [];
$allowed_tags_student['acronym']['id'] = [];
$allowed_tags_student['acronym']['lang'] = [];
$allowed_tags_student['acronym']['style'] = [];
$allowed_tags_student['acronym']['title'] = [];
$allowed_tags_student['acronym']['xml:lang'] = [];

// address
$allowed_tags_student['address'] = [];
$allowed_tags_student['address']['class'] = [];
$allowed_tags_student['address']['dir'] = [];
$allowed_tags_student['address']['id'] = [];
$allowed_tags_student['address']['lang'] = [];
$allowed_tags_student['address']['style'] = [];
$allowed_tags_student['address']['title'] = [];
$allowed_tags_student['address']['xml:lang'] = [];

// b
$allowed_tags_student['b'] = [];
$allowed_tags_student['b']['class'] = [];
$allowed_tags_student['b']['dir'] = [];
$allowed_tags_student['b']['id'] = [];
$allowed_tags_student['b']['lang'] = [];
$allowed_tags_student['b']['style'] = [];
$allowed_tags_student['b']['title'] = [];
$allowed_tags_student['b']['xml:lang'] = [];

// base
$allowed_tags_student_full_page['base'] = [];
$allowed_tags_student_full_page['base']['href'] = [];
$allowed_tags_student_full_page['base']['target'] = [];

// basefont (IE only)
$allowed_tags_student['basefont'] = [];
$allowed_tags_student['basefont']['face'] = [];
$allowed_tags_student['basefont']['color'] = [];
$allowed_tags_student['basefont']['id'] = [];
$allowed_tags_student['basefont']['size'] = [];

// bdo
$allowed_tags_student['bdo'] = [];
$allowed_tags_student['bdo']['class'] = [];
$allowed_tags_student['bdo']['dir'] = [];
$allowed_tags_student['bdo']['id'] = [];
$allowed_tags_student['bdo']['lang'] = [];
$allowed_tags_student['bdo']['style'] = [];
$allowed_tags_student['bdo']['title'] = [];
$allowed_tags_student['bdo']['xml:lang'] = [];

// big
$allowed_tags_student['big'] = [];
$allowed_tags_student['big']['class'] = [];
$allowed_tags_student['big']['dir'] = [];
$allowed_tags_student['big']['id'] = [];
$allowed_tags_student['big']['lang'] = [];
$allowed_tags_student['big']['style'] = [];
$allowed_tags_student['big']['title'] = [];
$allowed_tags_student['big']['xml:lang'] = [];

// blockquote
$allowed_tags_student['blockquote'] = [];
$allowed_tags_student['blockquote']['cite'] = [];
$allowed_tags_student['blockquote']['class'] = [];
$allowed_tags_student['blockquote']['dir'] = [];
$allowed_tags_student['blockquote']['id'] = [];
$allowed_tags_student['blockquote']['lang'] = [];
$allowed_tags_student['blockquote']['style'] = [];
$allowed_tags_student['blockquote']['title'] = [];
$allowed_tags_student['blockquote']['xml:lang'] = [];

// body
$allowed_tags_student_full_page['body'] = [];
$allowed_tags_student_full_page['body']['alink'] = [];
$allowed_tags_student_full_page['body']['background'] = [];
$allowed_tags_student_full_page['body']['bgcolor'] = [];
$allowed_tags_student_full_page['body']['link'] = [];
$allowed_tags_student_full_page['body']['text'] = [];
$allowed_tags_student_full_page['body']['vlink'] = [];
$allowed_tags_student_full_page['body']['class'] = [];
$allowed_tags_student_full_page['body']['dir'] = [];
$allowed_tags_student_full_page['body']['id'] = [];
$allowed_tags_student_full_page['body']['lang'] = [];
$allowed_tags_student_full_page['body']['style'] = [];
$allowed_tags_student_full_page['body']['title'] = [];
$allowed_tags_student_full_page['body']['xml:lang'] = [];

// br
$allowed_tags_student['br'] = [];
$allowed_tags_student['br']['class'] = [];
$allowed_tags_student['br']['id'] = [];
$allowed_tags_student['br']['style'] = [];
$allowed_tags_student['br']['title'] = [];

// caption
$allowed_tags_student['caption'] = [];
$allowed_tags_student['caption']['align'] = [];
$allowed_tags_student['caption']['class'] = [];
$allowed_tags_student['caption']['dir'] = [];
$allowed_tags_student['caption']['id'] = [];
$allowed_tags_student['caption']['lang'] = [];
$allowed_tags_student['caption']['style'] = [];
$allowed_tags_student['caption']['title'] = [];
$allowed_tags_student['caption']['xml:lang'] = [];

// center
$allowed_tags_student['center'] = [];
$allowed_tags_student['center']['class'] = [];
$allowed_tags_student['center']['dir'] = [];
$allowed_tags_student['center']['id'] = [];
$allowed_tags_student['center']['lang'] = [];
$allowed_tags_student['center']['style'] = [];
$allowed_tags_student['center']['title'] = [];

// cite
$allowed_tags_student['cite'] = [];
$allowed_tags_student['cite']['class'] = [];
$allowed_tags_student['cite']['dir'] = [];
$allowed_tags_student['cite']['id'] = [];
$allowed_tags_student['cite']['lang'] = [];
$allowed_tags_student['cite']['style'] = [];
$allowed_tags_student['cite']['title'] = [];
$allowed_tags_student['cite']['xml:lang'] = [];

// code
$allowed_tags_student['code'] = [];
$allowed_tags_student['code']['class'] = [];
$allowed_tags_student['code']['dir'] = [];
$allowed_tags_student['code']['id'] = [];
$allowed_tags_student['code']['lang'] = [];
$allowed_tags_student['code']['style'] = [];
$allowed_tags_student['code']['title'] = [];
$allowed_tags_student['code']['xml:lang'] = [];

// col
$allowed_tags_student['col'] = [];
$allowed_tags_student['col']['align'] = [];
$allowed_tags_student['col']['class'] = [];
$allowed_tags_student['col']['dir'] = [];
$allowed_tags_student['col']['id'] = [];
$allowed_tags_student['col']['lang'] = [];
$allowed_tags_student['col']['span'] = [];
$allowed_tags_student['col']['style'] = [];
$allowed_tags_student['col']['title'] = [];
$allowed_tags_student['col']['valign'] = [];
$allowed_tags_student['col']['width'] = [];
$allowed_tags_student['col']['xml:lang'] = [];

// colgroup
$allowed_tags_student['colgroup'] = [];
$allowed_tags_student['colgroup']['align'] = [];
$allowed_tags_student['colgroup']['class'] = [];
$allowed_tags_student['colgroup']['dir'] = [];
$allowed_tags_student['colgroup']['id'] = [];
$allowed_tags_student['colgroup']['lang'] = [];
$allowed_tags_student['colgroup']['span'] = [];
$allowed_tags_student['colgroup']['style'] = [];
$allowed_tags_student['colgroup']['title'] = [];
$allowed_tags_student['colgroup']['valign'] = [];
$allowed_tags_student['colgroup']['width'] = [];
$allowed_tags_student['colgroup']['xml:lang'] = [];

// dd
$allowed_tags_student['dd'] = [];
$allowed_tags_student['dd']['class'] = [];
$allowed_tags_student['dd']['dir'] = [];
$allowed_tags_student['dd']['id'] = [];
$allowed_tags_student['dd']['lang'] = [];
$allowed_tags_student['dd']['style'] = [];
$allowed_tags_student['dd']['title'] = [];
$allowed_tags_student['dd']['xml:lang'] = [];

// del
$allowed_tags_student['del'] = [];
$allowed_tags_student['del']['cite'] = [];
$allowed_tags_student['del']['class'] = [];
$allowed_tags_student['del']['dir'] = [];
$allowed_tags_student['del']['id'] = [];
$allowed_tags_student['del']['lang'] = [];
$allowed_tags_student['del']['style'] = [];
$allowed_tags_student['del']['title'] = [];
$allowed_tags_student['del']['xml:lang'] = [];

// dfn
$allowed_tags_student['dfn'] = [];
$allowed_tags_student['dfn']['class'] = [];
$allowed_tags_student['dfn']['dir'] = [];
$allowed_tags_student['dfn']['id'] = [];
$allowed_tags_student['dfn']['lang'] = [];
$allowed_tags_student['dfn']['style'] = [];
$allowed_tags_student['dfn']['title'] = [];
$allowed_tags_student['dfn']['xml:lang'] = [];

// dir
$allowed_tags_student['dir'] = [];
$allowed_tags_student['dir']['class'] = [];
$allowed_tags_student['dir']['compact'] = [];
$allowed_tags_student['dir']['dir'] = [];
$allowed_tags_student['dir']['id'] = [];
$allowed_tags_student['dir']['lang'] = [];
$allowed_tags_student['dir']['style'] = [];
$allowed_tags_student['dir']['title'] = [];

// div
$allowed_tags_student['div'] = [];
$allowed_tags_student['div']['align'] = [];
$allowed_tags_student['div']['class'] = [];
$allowed_tags_student['div']['dir'] = [];
$allowed_tags_student['div']['id'] = [];
$allowed_tags_student['div']['lang'] = [];
$allowed_tags_student['div']['style'] = [];
$allowed_tags_student['div']['title'] = [];
$allowed_tags_student['div']['xml:lang'] = [];

// dl
$allowed_tags_student['dl'] = [];
$allowed_tags_student['dl']['class'] = [];
$allowed_tags_student['dl']['dir'] = [];
$allowed_tags_student['dl']['id'] = [];
$allowed_tags_student['dl']['lang'] = [];
$allowed_tags_student['dl']['style'] = [];
$allowed_tags_student['dl']['title'] = [];
$allowed_tags_student['dl']['xml:lang'] = [];

// dt
$allowed_tags_student['dt'] = [];
$allowed_tags_student['dt']['class'] = [];
$allowed_tags_student['dt']['dir'] = [];
$allowed_tags_student['dt']['id'] = [];
$allowed_tags_student['dt']['lang'] = [];
$allowed_tags_student['dt']['style'] = [];
$allowed_tags_student['dt']['title'] = [];
$allowed_tags_student['dt']['xml:lang'] = [];

// em
$allowed_tags_student['em'] = [];
$allowed_tags_student['em']['class'] = [];
$allowed_tags_student['em']['dir'] = [];
$allowed_tags_student['em']['id'] = [];
$allowed_tags_student['em']['lang'] = [];
$allowed_tags_student['em']['style'] = [];
$allowed_tags_student['em']['title'] = [];
$allowed_tags_student['em']['xml:lang'] = [];

// embed
$allowed_tags_student['embed'] = [];
$allowed_tags_student['embed']['height'] = [];
$allowed_tags_student['embed']['width'] = [];
$allowed_tags_student['embed']['type'] = [];
//$allowed_tags_student['embed']['quality'] = array();
$allowed_tags_student['embed']['src'] = [];
$allowed_tags_student['embed']['flashvars'] = [];
$allowed_tags_student['embed']['allowscriptaccess'] = [];
//$allowed_tags_student['embed']['allowfullscreen'] = array();
//$allowed_tags_student['embed']['bgcolor'] = array();
//$allowed_tags_student['embed']['pluginspage'] = array();

// embed
$allowed_tags_student['video'] = [];
$allowed_tags_student['video']['height'] = [];
$allowed_tags_student['video']['width'] = [];
$allowed_tags_student['video']['type'] = [];
$allowed_tags_student['video']['poster'] = [];
$allowed_tags_student['video']['preload'] = [];
$allowed_tags_student['video']['src'] = [];
$allowed_tags_student['video']['controls'] = [];
$allowed_tags_student['video']['id'] = [];
$allowed_tags_student['video']['class'] = [];

$allowed_tags_student['audio'] = [];
$allowed_tags_student['audio']['autoplay'] = [];
$allowed_tags_student['audio']['src'] = [];
$allowed_tags_student['audio']['loop'] = [];
$allowed_tags_student['audio']['preload'] = [];
$allowed_tags_student['audio']['controls'] = [];
$allowed_tags_student['audio']['muted'] = [];
$allowed_tags_student['audio']['id'] = [];
$allowed_tags_student['audio']['class'] = [];

$allowed_tags_student['source'] = [];
$allowed_tags_student['source']['type'] = [];
$allowed_tags_student['source']['src'] = [];

// font
$allowed_tags_student['font'] = [];
$allowed_tags_student['font']['face'] = [];
$allowed_tags_student['font']['class'] = [];
$allowed_tags_student['font']['color'] = [];
$allowed_tags_student['font']['dir'] = [];
$allowed_tags_student['font']['id'] = [];
$allowed_tags_student['font']['lang'] = [];
$allowed_tags_student['font']['size'] = [];
$allowed_tags_student['font']['style'] = [];
$allowed_tags_student['font']['title'] = [];

// frame
$allowed_tags_student_full_page['frame'] = [];
$allowed_tags_student_full_page['frame']['class'] = [];
$allowed_tags_student_full_page['frame']['frameborder'] = [];
$allowed_tags_student_full_page['frame']['id'] = [];
$allowed_tags_student_full_page['frame']['longsesc'] = [];
$allowed_tags_student_full_page['frame']['marginheight'] = [];
$allowed_tags_student_full_page['frame']['marginwidth'] = [];
$allowed_tags_student_full_page['frame']['name'] = [];
$allowed_tags_student_full_page['frame']['noresize'] = [];
$allowed_tags_student_full_page['frame']['scrolling'] = [];
$allowed_tags_student_full_page['frame']['src'] = [];
$allowed_tags_student_full_page['frame']['style'] = [];
$allowed_tags_student_full_page['frame']['title'] = [];

// frameset
$allowed_tags_student_full_page['frameset'] = [];
$allowed_tags_student_full_page['frameset']['class'] = [];
$allowed_tags_student_full_page['frameset']['cols'] = [];
$allowed_tags_student_full_page['frameset']['id'] = [];
$allowed_tags_student_full_page['frameset']['rows'] = [];
$allowed_tags_student_full_page['frameset']['style'] = [];
$allowed_tags_student_full_page['frameset']['title'] = [];

// head
$allowed_tags_student_full_page['head'] = [];
$allowed_tags_student_full_page['head']['dir'] = [];
$allowed_tags_student_full_page['head']['lang'] = [];
$allowed_tags_student_full_page['head']['profile'] = [];
$allowed_tags_student_full_page['head']['xml:lang'] = [];

// h1
$allowed_tags_student['h1'] = [];
$allowed_tags_student['h1']['align'] = [];
$allowed_tags_student['h1']['class'] = [];
$allowed_tags_student['h1']['dir'] = [];
$allowed_tags_student['h1']['id'] = [];
$allowed_tags_student['h1']['lang'] = [];
$allowed_tags_student['h1']['style'] = [];
$allowed_tags_student['h1']['title'] = [];
$allowed_tags_student['h1']['xml:lang'] = [];

// h2
$allowed_tags_student['h2'] = [];
$allowed_tags_student['h2']['align'] = [];
$allowed_tags_student['h2']['class'] = [];
$allowed_tags_student['h2']['dir'] = [];
$allowed_tags_student['h2']['id'] = [];
$allowed_tags_student['h2']['lang'] = [];
$allowed_tags_student['h2']['style'] = [];
$allowed_tags_student['h2']['title'] = [];
$allowed_tags_student['h2']['xml:lang'] = [];

// h3
$allowed_tags_student['h3'] = [];
$allowed_tags_student['h3']['align'] = [];
$allowed_tags_student['h3']['class'] = [];
$allowed_tags_student['h3']['dir'] = [];
$allowed_tags_student['h3']['id'] = [];
$allowed_tags_student['h3']['lang'] = [];
$allowed_tags_student['h3']['style'] = [];
$allowed_tags_student['h3']['title'] = [];
$allowed_tags_student['h3']['xml:lang'] = [];

// h4
$allowed_tags_student['h4'] = [];
$allowed_tags_student['h4']['align'] = [];
$allowed_tags_student['h4']['class'] = [];
$allowed_tags_student['h4']['dir'] = [];
$allowed_tags_student['h4']['id'] = [];
$allowed_tags_student['h4']['lang'] = [];
$allowed_tags_student['h4']['style'] = [];
$allowed_tags_student['h4']['title'] = [];
$allowed_tags_student['h4']['xml:lang'] = [];

// h5
$allowed_tags_student['h5'] = [];
$allowed_tags_student['h5']['align'] = [];
$allowed_tags_student['h5']['class'] = [];
$allowed_tags_student['h5']['dir'] = [];
$allowed_tags_student['h5']['id'] = [];
$allowed_tags_student['h5']['lang'] = [];
$allowed_tags_student['h5']['style'] = [];
$allowed_tags_student['h5']['title'] = [];
$allowed_tags_student['h5']['xml:lang'] = [];

// h6
$allowed_tags_student['h6'] = [];
$allowed_tags_student['h6']['align'] = [];
$allowed_tags_student['h6']['class'] = [];
$allowed_tags_student['h6']['dir'] = [];
$allowed_tags_student['h6']['id'] = [];
$allowed_tags_student['h6']['lang'] = [];
$allowed_tags_student['h6']['style'] = [];
$allowed_tags_student['h6']['title'] = [];
$allowed_tags_student['h6']['xml:lang'] = [];

// hr
$allowed_tags_student['hr'] = [];
$allowed_tags_student['hr']['align'] = [];
$allowed_tags_student['hr']['class'] = [];
$allowed_tags_student['hr']['dir'] = [];
$allowed_tags_student['hr']['id'] = [];
$allowed_tags_student['hr']['lang'] = [];
$allowed_tags_student['hr']['noshade'] = [];
$allowed_tags_student['hr']['size'] = [];
$allowed_tags_student['hr']['style'] = [];
$allowed_tags_student['hr']['title'] = [];
$allowed_tags_student['hr']['width'] = [];
$allowed_tags_student['hr']['xml:lang'] = [];

// html
$allowed_tags_student_full_page['html'] = [];
$allowed_tags_student_full_page['html']['dir'] = [];
$allowed_tags_student_full_page['html']['lang'] = [];
$allowed_tags_student_full_page['html']['xml:lang'] = [];
$allowed_tags_student_full_page['html']['xmlns'] = [];

// i
$allowed_tags_student['i'] = [];
$allowed_tags_student['i']['class'] = [];
$allowed_tags_student['i']['dir'] = [];
$allowed_tags_student['i']['id'] = [];
$allowed_tags_student['i']['lang'] = [];
$allowed_tags_student['i']['style'] = [];
$allowed_tags_student['i']['title'] = [];
$allowed_tags_student['i']['xml:lang'] = [];

// img
$allowed_tags_student['img'] = [];
$allowed_tags_student['img']['alt'] = [];
$allowed_tags_student['img']['align'] = [];
$allowed_tags_student['img']['border'] = [];
$allowed_tags_student['img']['class'] = [];
$allowed_tags_student['img']['dir'] = [];
$allowed_tags_student['img']['id'] = [];
$allowed_tags_student['img']['height'] = [];
$allowed_tags_student['img']['hspace'] = [];
//$allowed_tags_student['img']['ismap'] = array();
$allowed_tags_student['img']['lang'] = [];
$allowed_tags_student['img']['longdesc'] = [];
$allowed_tags_student['img']['style'] = [];
$allowed_tags_student['img']['src'] = [];
$allowed_tags_student['img']['title'] = [];
//$allowed_tags_student['img']['usemap'] = array();
$allowed_tags_student['img']['vspace'] = [];
$allowed_tags_student['img']['width'] = [];
$allowed_tags_student['img']['xml:lang'] = [];

// ins
$allowed_tags_student['ins'] = [];
$allowed_tags_student['ins']['cite'] = [];
$allowed_tags_student['ins']['class'] = [];
$allowed_tags_student['ins']['dir'] = [];
$allowed_tags_student['ins']['id'] = [];
$allowed_tags_student['ins']['lang'] = [];
$allowed_tags_student['ins']['style'] = [];
$allowed_tags_student['ins']['title'] = [];
$allowed_tags_student['ins']['xml:lang'] = [];

// kbd
$allowed_tags_student['kbd'] = [];
$allowed_tags_student['kbd']['class'] = [];
$allowed_tags_student['kbd']['dir'] = [];
$allowed_tags_student['kbd']['id'] = [];
$allowed_tags_student['kbd']['lang'] = [];
$allowed_tags_student['kbd']['style'] = [];
$allowed_tags_student['kbd']['title'] = [];
$allowed_tags_student['kbd']['xml:lang'] = [];

// li
$allowed_tags_student['li'] = [];
$allowed_tags_student['li']['class'] = [];
$allowed_tags_student['li']['dir'] = [];
$allowed_tags_student['li']['id'] = [];
$allowed_tags_student['li']['lang'] = [];
$allowed_tags_student['li']['style'] = [];
$allowed_tags_student['li']['title'] = [];
$allowed_tags_student['li']['type'] = [];
$allowed_tags_student['li']['value'] = [];
$allowed_tags_student['li']['xml:lang'] = [];

// link
$allowed_tags_student_full_page['link'] = [];
$allowed_tags_student_full_page['link']['charset'] = [];
$allowed_tags_student_full_page['link']['href'] = [];
$allowed_tags_student_full_page['link']['hreflang'] = [];
$allowed_tags_student_full_page['link']['media'] = [];
$allowed_tags_student_full_page['link']['rel'] = [];
$allowed_tags_student_full_page['link']['rev'] = [];
$allowed_tags_student_full_page['link']['target'] = [];
$allowed_tags_student_full_page['link']['type'] = [];

// map
/*
$allowed_tags_student['map'] = array();
$allowed_tags_student['map']['class'] = array();
$allowed_tags_student['map']['dir'] = array();
$allowed_tags_student['map']['id'] = array();
$allowed_tags_student['map']['lang'] = array();
$allowed_tags_student['map']['name'] = array();
$allowed_tags_student['map']['style'] = array();
$allowed_tags_student['map']['title'] = array();
$allowed_tags_student['map']['xml:lang'] = array();
*/

// menu
$allowed_tags_student['menu'] = [];
$allowed_tags_student['menu']['class'] = [];
$allowed_tags_student['menu']['compact'] = [];
$allowed_tags_student['menu']['dir'] = [];
$allowed_tags_student['menu']['id'] = [];
$allowed_tags_student['menu']['lang'] = [];
$allowed_tags_student['menu']['style'] = [];
$allowed_tags_student['menu']['title'] = [];

// meta
$allowed_tags_student_full_page['meta'] = [];
$allowed_tags_student_full_page['meta']['content'] = [];
$allowed_tags_student_full_page['meta']['dir'] = [];
$allowed_tags_student_full_page['meta']['http-equiv'] = [];
$allowed_tags_student_full_page['meta']['lang'] = [];
$allowed_tags_student_full_page['meta']['name'] = [];
$allowed_tags_student_full_page['meta']['scheme'] = [];
$allowed_tags_student_full_page['meta']['xml:lang'] = [];

// noframes
$allowed_tags_student_full_page['noframes'] = [];
$allowed_tags_student_full_page['noframes']['class'] = [];
$allowed_tags_student_full_page['noframes']['dir'] = [];
$allowed_tags_student_full_page['noframes']['id'] = [];
$allowed_tags_student_full_page['noframes']['lang'] = [];
$allowed_tags_student_full_page['noframes']['style'] = [];
$allowed_tags_student_full_page['noframes']['title'] = [];
$allowed_tags_student_full_page['noframes']['xml:lang'] = [];

// object
$allowed_tags_student['object'] = [];
//$allowed_tags_student['object']['align'] = array();
//$allowed_tags_student['object']['archive'] = array();
//$allowed_tags_student['object']['border'] = array();
$allowed_tags_student['object']['class'] = [];
//$allowed_tags_student['object']['classid'] = array();
$allowed_tags_student['object']['codebase'] = [];
//$allowed_tags_student['object']['codetype'] = array();
$allowed_tags_student['object']['data'] = [];
//$allowed_tags_student['object']['declare'] = array();
$allowed_tags_student['object']['dir'] = [];
$allowed_tags_student['object']['id'] = [];
$allowed_tags_student['object']['height'] = [];
//$allowed_tags_student['object']['hspace'] = array();
$allowed_tags_student['object']['lang'] = [];
//$allowed_tags_student['object']['name'] = array();
//$allowed_tags_student['object']['standby'] = array();
$allowed_tags_student['object']['style'] = [];
$allowed_tags_student['object']['title'] = [];
$allowed_tags_student['object']['type'] = [];
//$allowed_tags_student['object']['usemap'] = array();
//$allowed_tags_student['object']['vspace'] = array();
$allowed_tags_student['object']['width'] = [];
$allowed_tags_student['object']['xml:lang'] = [];

// ol
$allowed_tags_student['ol'] = [];
$allowed_tags_student['ol']['class'] = [];
$allowed_tags_student['ol']['compact'] = [];
$allowed_tags_student['ol']['dir'] = [];
$allowed_tags_student['ol']['id'] = [];
$allowed_tags_student['ol']['lang'] = [];
$allowed_tags_student['ol']['start'] = [];
$allowed_tags_student['ol']['style'] = [];
$allowed_tags_student['ol']['title'] = [];
$allowed_tags_student['ol']['type'] = [];
$allowed_tags_student['ol']['xml:lang'] = [];

// p
$allowed_tags_student['p'] = [];
$allowed_tags_student['p']['align'] = [];
$allowed_tags_student['p']['class'] = [];
$allowed_tags_student['p']['dir'] = [];
$allowed_tags_student['p']['id'] = [];
$allowed_tags_student['p']['lang'] = [];
$allowed_tags_student['p']['style'] = [];
$allowed_tags_student['p']['title'] = [];
$allowed_tags_student['p']['xml:lang'] = [];

// param
$allowed_tags_student['param'] = [];
$allowed_tags_student['param']['name'] = [];
//$allowed_tags_student['param']['type'] = array();
$allowed_tags_student['param']['value'] = [];
//$allowed_tags_student['param']['valuetype'] = array();

// pre
$allowed_tags_student['pre'] = [];
$allowed_tags_student['pre']['class'] = [];
$allowed_tags_student['pre']['dir'] = [];
$allowed_tags_student['pre']['id'] = [];
$allowed_tags_student['pre']['lang'] = [];
$allowed_tags_student['pre']['style'] = [];
$allowed_tags_student['pre']['title'] = [];
$allowed_tags_student['pre']['width'] = [];
$allowed_tags_student['pre']['xml:lang'] = [];

// q
$allowed_tags_student['q'] = [];
$allowed_tags_student['q']['cite'] = [];
$allowed_tags_student['q']['class'] = [];
$allowed_tags_student['q']['dir'] = [];
$allowed_tags_student['q']['id'] = [];
$allowed_tags_student['q']['lang'] = [];
$allowed_tags_student['q']['style'] = [];
$allowed_tags_student['q']['title'] = [];
$allowed_tags_student['q']['xml:lang'] = [];

// s
$allowed_tags_student['s'] = [];
$allowed_tags_student['s']['class'] = [];
$allowed_tags_student['s']['dir'] = [];
$allowed_tags_student['s']['id'] = [];
$allowed_tags_student['s']['lang'] = [];
$allowed_tags_student['s']['style'] = [];
$allowed_tags_student['q']['title'] = [];

// samp
$allowed_tags_student['samp'] = [];
$allowed_tags_student['samp']['class'] = [];
$allowed_tags_student['samp']['dir'] = [];
$allowed_tags_student['samp']['id'] = [];
$allowed_tags_student['samp']['lang'] = [];
$allowed_tags_student['samp']['style'] = [];
$allowed_tags_student['samp']['title'] = [];
$allowed_tags_student['samp']['xml:lang'] = [];

// small
$allowed_tags_student['small'] = [];
$allowed_tags_student['small']['class'] = [];
$allowed_tags_student['small']['dir'] = [];
$allowed_tags_student['small']['id'] = [];
$allowed_tags_student['small']['lang'] = [];
$allowed_tags_student['small']['style'] = [];
$allowed_tags_student['small']['title'] = [];
$allowed_tags_student['small']['xml:lang'] = [];

// span
$allowed_tags_student['span'] = [];
$allowed_tags_student['span']['class'] = [];
$allowed_tags_student['span']['dir'] = [];
$allowed_tags_student['span']['id'] = [];
$allowed_tags_student['span']['lang'] = [];
$allowed_tags_student['span']['style'] = [];
$allowed_tags_student['span']['title'] = [];
$allowed_tags_student['span']['xml:lang'] = [];

// strike
$allowed_tags_student['strike'] = [];
$allowed_tags_student['strike']['class'] = [];
$allowed_tags_student['strike']['dir'] = [];
$allowed_tags_student['strike']['id'] = [];
$allowed_tags_student['strike']['lang'] = [];
$allowed_tags_student['strike']['style'] = [];
$allowed_tags_student['strike']['title'] = [];

// strong
$allowed_tags_student['strong'] = [];
$allowed_tags_student['strong']['class'] = [];
$allowed_tags_student['strong']['dir'] = [];
$allowed_tags_student['strong']['id'] = [];
$allowed_tags_student['strong']['lang'] = [];
$allowed_tags_student['strong']['style'] = [];
$allowed_tags_student['strong']['title'] = [];
$allowed_tags_student['strong']['xml:lang'] = [];

// style
$allowed_tags_student_full_page['style'] = [];
$allowed_tags_student_full_page['style']['dir'] = [];
$allowed_tags_student_full_page['style']['lang'] = [];
$allowed_tags_student_full_page['style']['media'] = [];
$allowed_tags_student_full_page['style']['title'] = [];
$allowed_tags_student_full_page['style']['type'] = [];
$allowed_tags_student_full_page['style']['xml:lang'] = [];

// sub
$allowed_tags_student['sub'] = [];
$allowed_tags_student['sub']['class'] = [];
$allowed_tags_student['sub']['dir'] = [];
$allowed_tags_student['sub']['id'] = [];
$allowed_tags_student['sub']['lang'] = [];
$allowed_tags_student['sub']['style'] = [];
$allowed_tags_student['sub']['title'] = [];
$allowed_tags_student['sub']['xml:lang'] = [];

// sup
$allowed_tags_student['sup'] = [];
$allowed_tags_student['sup']['class'] = [];
$allowed_tags_student['sup']['dir'] = [];
$allowed_tags_student['sup']['id'] = [];
$allowed_tags_student['sup']['lang'] = [];
$allowed_tags_student['sup']['style'] = [];
$allowed_tags_student['sup']['title'] = [];
$allowed_tags_student['sup']['xml:lang'] = [];

// table
$allowed_tags_student['table'] = [];
$allowed_tags_student['table']['align'] = [];
$allowed_tags_student['table']['bgcolor'] = [];
$allowed_tags_student['table']['border'] = [];
$allowed_tags_student['table']['cellpadding'] = [];
$allowed_tags_student['table']['cellspacing'] = [];
$allowed_tags_student['table']['class'] = [];
$allowed_tags_student['table']['dir'] = [];
$allowed_tags_student['table']['frame'] = [];
$allowed_tags_student['table']['id'] = [];
$allowed_tags_student['table']['lang'] = [];
$allowed_tags_student['table']['rules'] = [];
$allowed_tags_student['table']['style'] = [];
$allowed_tags_student['table']['summary'] = [];
$allowed_tags_student['table']['title'] = [];
$allowed_tags_student['table']['width'] = [];
$allowed_tags_student['table']['xml:lang'] = [];

// tbody
$allowed_tags_student['tbody'] = [];
$allowed_tags_student['tbody']['align'] = [];
//$allowed_tags_student['tbody']['char'] = array();
//$allowed_tags_student['tbody']['charoff'] = array();
$allowed_tags_student['tbody']['class'] = [];
$allowed_tags_student['tbody']['dir'] = [];
$allowed_tags_student['tbody']['id'] = [];
$allowed_tags_student['tbody']['lang'] = [];
$allowed_tags_student['tbody']['style'] = [];
$allowed_tags_student['tbody']['title'] = [];
$allowed_tags_student['tbody']['valign'] = [];
$allowed_tags_student['tbody']['xml:lang'] = [];

// td
$allowed_tags_student['td'] = [];
$allowed_tags_student['td']['abbr'] = [];
$allowed_tags_student['td']['align'] = [];
//$allowed_tags_student['td']['axis'] = array();
$allowed_tags_student['td']['bgcolor'] = [];
//$allowed_tags_student['td']['char'] = array();
//$allowed_tags_student['td']['charoff'] = array();
$allowed_tags_student['td']['class'] = [];
$allowed_tags_student['td']['colspan'] = [];
$allowed_tags_student['td']['dir'] = [];
//$allowed_tags_student['td']['headers'] = array();
$allowed_tags_student['td']['height'] = [];
$allowed_tags_student['td']['id'] = [];
$allowed_tags_student['td']['lang'] = [];
$allowed_tags_student['td']['nowrap'] = [];
$allowed_tags_student['td']['rowspan'] = [];
//$allowed_tags_student['td']['scope'] = array();
$allowed_tags_student['td']['style'] = [];
$allowed_tags_student['td']['title'] = [];
$allowed_tags_student['td']['valign'] = [];
$allowed_tags_student['td']['width'] = [];
$allowed_tags_student['td']['xml:lang'] = [];

// tfoot
$allowed_tags_student['tfoot'] = [];
$allowed_tags_student['tfoot']['align'] = [];
//$allowed_tags_student['tfoot']['char'] = array();
//$allowed_tags_student['tfoot']['charoff'] = array();
$allowed_tags_student['tfoot']['class'] = [];
$allowed_tags_student['tfoot']['dir'] = [];
$allowed_tags_student['tfoot']['id'] = [];
$allowed_tags_student['tfoot']['lang'] = [];
$allowed_tags_student['tfoot']['style'] = [];
$allowed_tags_student['tfoot']['title'] = [];
$allowed_tags_student['tfoot']['valign'] = [];
$allowed_tags_student['tfoot']['xml:lang'] = [];

// th
$allowed_tags_student['th'] = [];
$allowed_tags_student['th']['abbr'] = [];
$allowed_tags_student['th']['align'] = [];
//$allowed_tags_student['th']['axis'] = array();
$allowed_tags_student['th']['bgcolor'] = [];
//$allowed_tags_student['th']['char'] = array();
//$allowed_tags_student['th']['charoff'] = array();
$allowed_tags_student['th']['class'] = [];
$allowed_tags_student['th']['colspan'] = [];
$allowed_tags_student['th']['dir'] = [];
//$allowed_tags_student['th']['headers'] = array();
$allowed_tags_student['th']['height'] = [];
$allowed_tags_student['th']['id'] = [];
$allowed_tags_student['th']['lang'] = [];
$allowed_tags_student['th']['nowrap'] = [];
$allowed_tags_student['th']['rowspan'] = [];
//$allowed_tags_student['th']['scope'] = array();
$allowed_tags_student['th']['style'] = [];
$allowed_tags_student['th']['title'] = [];
$allowed_tags_student['th']['valign'] = [];
$allowed_tags_student['th']['width'] = [];
$allowed_tags_student['th']['xml:lang'] = [];

// thead
$allowed_tags_student['thead'] = [];
$allowed_tags_student['thead']['align'] = [];
$allowed_tags_student['thead']['class'] = [];
//$allowed_tags_student['thead']['char'] = array();
//$allowed_tags_student['thead']['charoff'] = array();
$allowed_tags_student['thead']['dir'] = [];
$allowed_tags_student['thead']['id'] = [];
$allowed_tags_student['thead']['lang'] = [];
$allowed_tags_student['thead']['style'] = [];
$allowed_tags_student['thead']['title'] = [];
$allowed_tags_student['thead']['valign'] = [];
$allowed_tags_student['thead']['xml:lang'] = [];

// title
$allowed_tags_student_full_page['title'] = [];
$allowed_tags_student_full_page['title']['dir'] = [];
$allowed_tags_student_full_page['title']['lang'] = [];
$allowed_tags_student_full_page['title']['xml:lang'] = [];

// tr
$allowed_tags_student['tr'] = [];
$allowed_tags_student['tr']['align'] = [];
$allowed_tags_student['tr']['bgcolor'] = [];
//$allowed_tags_student['tr']['char'] = array();
//$allowed_tags_student['tr']['charoff'] = array();
$allowed_tags_student['tr']['class'] = [];
$allowed_tags_student['tr']['dir'] = [];
$allowed_tags_student['tr']['id'] = [];
$allowed_tags_student['tr']['lang'] = [];
$allowed_tags_student['tr']['style'] = [];
$allowed_tags_student['tr']['title'] = [];
$allowed_tags_student['tr']['valign'] = [];
$allowed_tags_student['tr']['xml:lang'] = [];

// tt
$allowed_tags_student['tt'] = [];
$allowed_tags_student['tt']['class'] = [];
$allowed_tags_student['tt']['dir'] = [];
$allowed_tags_student['tt']['id'] = [];
$allowed_tags_student['tt']['lang'] = [];
$allowed_tags_student['tt']['style'] = [];
$allowed_tags_student['tt']['title'] = [];
$allowed_tags_student['tt']['xml:lang'] = [];

// u
$allowed_tags_student['u'] = [];
$allowed_tags_student['u']['class'] = [];
$allowed_tags_student['u']['dir'] = [];
$allowed_tags_student['u']['id'] = [];
$allowed_tags_student['u']['lang'] = [];
$allowed_tags_student['u']['style'] = [];
$allowed_tags_student['u']['title'] = [];

// ul
$allowed_tags_student['ul'] = [];
$allowed_tags_student['ul']['class'] = [];
$allowed_tags_student['ul']['compact'] = [];
$allowed_tags_student['ul']['dir'] = [];
$allowed_tags_student['ul']['id'] = [];
$allowed_tags_student['ul']['lang'] = [];
$allowed_tags_student['ul']['style'] = [];
$allowed_tags_student['ul']['title'] = [];
$allowed_tags_student['ul']['type'] = [];
$allowed_tags_student['ul']['xml:lang'] = [];

// var
$allowed_tags_student['var'] = [];
$allowed_tags_student['var']['class'] = [];
$allowed_tags_student['var']['dir'] = [];
$allowed_tags_student['var']['id'] = [];
$allowed_tags_student['var']['lang'] = [];
$allowed_tags_student['var']['style'] = [];
$allowed_tags_student['var']['title'] = [];
$allowed_tags_student['var']['xml:lang'] = [];

// ALLOWED HTML FOR TEACHERS

// Allow all HTML allowed for students
$allowed_tags_teacher = $allowed_tags_student;

// noscript
//$allowed_tags_teacher['noscript'] = array();

// script
//$allowed_tags_teacher['script'] = array();
//$allowed_tags_teacher['script']['type'] = array();

// TODO:
// 1. The tags <html>, <head>, <body> should not be allowed for document fragments.
// 2. To be checked whether HTMLPurifier "silently" passes these tags.

/*$allowed_tags_teacher['html'] = array();
$allowed_tags_teacher['html']['xmlns'] = array();

$allowed_tags_teacher['head'] = array();
$allowed_tags_teacher['head']['profile'] = array();*/

// body
/*
$allowed_tags_teacher['body'] = array();
$allowed_tags_teacher['body']['alink'] = array();
$allowed_tags_teacher['body']['background'] = array();
$allowed_tags_teacher['body']['bgcolor'] = array();
$allowed_tags_teacher['body']['link'] = array();
$allowed_tags_teacher['body']['text'] = array();
$allowed_tags_teacher['body']['vlink'] = array();*/

$allowed_tags_teacher_full_page = $allowed_tags_student_full_page;

// ALLOWED HTML FOR ANONYMOUS USERS

$allowed_tags_anonymous = $allowed_tags_student;
$allowed_tags_anonymous_full_page = $allowed_tags_student_full_page;
// Add restrictions here.
unset($allowed_tags_anonymous['embed']);
unset($allowed_tags_anonymous['object']);
unset($allowed_tags_anonymous['param']);

// HTMLPURIFIER-COMPATIBLE SETTINGS

function convert_kses_to_htmlpurifier($allowed_tags)
{
    $allowed_html = [];
    foreach ($allowed_tags as $key1 => &$value1) {
        $result[0][] = $key1;
        if (count($value1) > 0) {
            $attr = [];
            foreach ($value1 as $key2 => &$value2) {
                $attr[] = $key2;
            }
            $allowed_html[] = $key1.'['.implode('|', $attr).']';
        } else {
            $allowed_html[] = $key1;
        }
    }

    return implode(",\n", $allowed_html);
}

global $allowed_html_student, $allowed_html_teacher, $allowed_html_anonymous;

// TODO: Support for full-page tags is needed for HTMLPurifier.
//$allowed_html_student = convert_kses_to_htmlpurifier(array_merge($allowed_tags_student, $allowed_tags_student_full_page));
//$allowed_html_teacher = convert_kses_to_htmlpurifier(array_merge($allowed_tags_teacher, $allowed_tags_teacher_full_page));
//$allowed_html_anonymous = convert_kses_to_htmlpurifier(array_merge($allowed_tags_anonymous, $allowed_tags_anonymous_full_page));
$allowed_html_student = convert_kses_to_htmlpurifier(array_merge($allowed_tags_student));
$allowed_html_teacher = convert_kses_to_htmlpurifier(array_merge($allowed_tags_teacher));
$allowed_html_anonymous = convert_kses_to_htmlpurifier(array_merge($allowed_tags_anonymous));
