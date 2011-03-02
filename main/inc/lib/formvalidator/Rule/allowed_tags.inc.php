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
 * @link http://www.w3schools.com/tags/
 */

// KSES-COMPATIBLE SETTINGS

// ALLOWED HTML FOR STUDENTS

// a
$allowed_tags_student['a'] = array();
$allowed_tags_student['a']['class'] = array();
$allowed_tags_student['a']['dir'] = array();
$allowed_tags_student['a']['id'] = array();
$allowed_tags_student['a']['href'] = array();
$allowed_tags_student['a']['lang'] = array();
$allowed_tags_student['a']['name'] = array();
$allowed_tags_student['a']['rel'] = array();
$allowed_tags_student['a']['rev'] = array();
$allowed_tags_student['a']['style'] = array();
$allowed_tags_student['a']['target'] = array();
$allowed_tags_student['a']['title'] = array();
$allowed_tags_student['a']['xml:lang'] = array();

// abbr
$allowed_tags_student['abbr'] = array();
$allowed_tags_student['abbr']['class'] = array();
$allowed_tags_student['abbr']['dir'] = array();
$allowed_tags_student['abbr']['id'] = array();
$allowed_tags_student['abbr']['lang'] = array();
$allowed_tags_student['abbr']['style'] = array();
$allowed_tags_student['abbr']['title'] = array();
$allowed_tags_student['abbr']['xml:lang'] = array();

// acronym
$allowed_tags_student['acronym'] = array();
$allowed_tags_student['acronym']['class'] = array();
$allowed_tags_student['acronym']['dir'] = array();
$allowed_tags_student['acronym']['id'] = array();
$allowed_tags_student['acronym']['lang'] = array();
$allowed_tags_student['acronym']['style'] = array();
$allowed_tags_student['acronym']['title'] = array();
$allowed_tags_student['acronym']['xml:lang'] = array();

// address
$allowed_tags_student['address'] = array();
$allowed_tags_student['address']['class'] = array();
$allowed_tags_student['address']['dir'] = array();
$allowed_tags_student['address']['id'] = array();
$allowed_tags_student['address']['lang'] = array();
$allowed_tags_student['address']['style'] = array();
$allowed_tags_student['address']['title'] = array();
$allowed_tags_student['address']['xml:lang'] = array();

// b
$allowed_tags_student['b'] = array();
$allowed_tags_student['b']['class'] = array();
$allowed_tags_student['b']['dir'] = array();
$allowed_tags_student['b']['id'] = array();
$allowed_tags_student['b']['lang'] = array();
$allowed_tags_student['b']['style'] = array();
$allowed_tags_student['b']['title'] = array();
$allowed_tags_student['b']['xml:lang'] = array();

// base
$allowed_tags_student_full_page['base'] = array();
$allowed_tags_student_full_page['base']['href'] = array();
$allowed_tags_student_full_page['base']['target'] = array();

// basefont (IE only)
$allowed_tags_student['basefont'] = array();
$allowed_tags_student['basefont']['face'] = array();
$allowed_tags_student['basefont']['color'] = array();
$allowed_tags_student['basefont']['id'] = array();
$allowed_tags_student['basefont']['size'] = array();

// bdo
$allowed_tags_student['bdo'] = array();
$allowed_tags_student['bdo']['class'] = array();
$allowed_tags_student['bdo']['dir'] = array();
$allowed_tags_student['bdo']['id'] = array();
$allowed_tags_student['bdo']['lang'] = array();
$allowed_tags_student['bdo']['style'] = array();
$allowed_tags_student['bdo']['title'] = array();
$allowed_tags_student['bdo']['xml:lang'] = array();

// big
$allowed_tags_student['big'] = array();
$allowed_tags_student['big']['class'] = array();
$allowed_tags_student['big']['dir'] = array();
$allowed_tags_student['big']['id'] = array();
$allowed_tags_student['big']['lang'] = array();
$allowed_tags_student['big']['style'] = array();
$allowed_tags_student['big']['title'] = array();
$allowed_tags_student['big']['xml:lang'] = array();

// blockquote
$allowed_tags_student['blockquote'] = array();
$allowed_tags_student['blockquote']['cite'] = array();
$allowed_tags_student['blockquote']['class'] = array();
$allowed_tags_student['blockquote']['dir'] = array();
$allowed_tags_student['blockquote']['id'] = array();
$allowed_tags_student['blockquote']['lang'] = array();
$allowed_tags_student['blockquote']['style'] = array();
$allowed_tags_student['blockquote']['title'] = array();
$allowed_tags_student['blockquote']['xml:lang'] = array();

// body
$allowed_tags_student_full_page['body'] = array();
$allowed_tags_student_full_page['body']['alink'] = array();
$allowed_tags_student_full_page['body']['background'] = array();
$allowed_tags_student_full_page['body']['bgcolor'] = array();
$allowed_tags_student_full_page['body']['link'] = array();
$allowed_tags_student_full_page['body']['text'] = array();
$allowed_tags_student_full_page['body']['vlink'] = array();
$allowed_tags_student_full_page['body']['class'] = array();
$allowed_tags_student_full_page['body']['dir'] = array();
$allowed_tags_student_full_page['body']['id'] = array();
$allowed_tags_student_full_page['body']['lang'] = array();
$allowed_tags_student_full_page['body']['style'] = array();
$allowed_tags_student_full_page['body']['title'] = array();
$allowed_tags_student_full_page['body']['xml:lang'] = array();

// br
$allowed_tags_student['br'] = array();
$allowed_tags_student['br']['class'] = array();
$allowed_tags_student['br']['id'] = array();
$allowed_tags_student['br']['style'] = array();
$allowed_tags_student['br']['title'] = array();

// caption
$allowed_tags_student['caption'] = array();
$allowed_tags_student['caption']['align'] = array();
$allowed_tags_student['caption']['class'] = array();
$allowed_tags_student['caption']['dir'] = array();
$allowed_tags_student['caption']['id'] = array();
$allowed_tags_student['caption']['lang'] = array();
$allowed_tags_student['caption']['style'] = array();
$allowed_tags_student['caption']['title'] = array();
$allowed_tags_student['caption']['xml:lang'] = array();

// center
$allowed_tags_student['center'] = array();
$allowed_tags_student['center']['class'] = array();
$allowed_tags_student['center']['dir'] = array();
$allowed_tags_student['center']['id'] = array();
$allowed_tags_student['center']['lang'] = array();
$allowed_tags_student['center']['style'] = array();
$allowed_tags_student['center']['title'] = array();

// cite
$allowed_tags_student['cite'] = array();
$allowed_tags_student['cite']['class'] = array();
$allowed_tags_student['cite']['dir'] = array();
$allowed_tags_student['cite']['id'] = array();
$allowed_tags_student['cite']['lang'] = array();
$allowed_tags_student['cite']['style'] = array();
$allowed_tags_student['cite']['title'] = array();
$allowed_tags_student['cite']['xml:lang'] = array();

// code
$allowed_tags_student['code'] = array();
$allowed_tags_student['code']['class'] = array();
$allowed_tags_student['code']['dir'] = array();
$allowed_tags_student['code']['id'] = array();
$allowed_tags_student['code']['lang'] = array();
$allowed_tags_student['code']['style'] = array();
$allowed_tags_student['code']['title'] = array();
$allowed_tags_student['code']['xml:lang'] = array();

// col
$allowed_tags_student['col'] = array();
$allowed_tags_student['col']['align'] = array();
$allowed_tags_student['col']['class'] = array();
$allowed_tags_student['col']['dir'] = array();
$allowed_tags_student['col']['id'] = array();
$allowed_tags_student['col']['lang'] = array();
$allowed_tags_student['col']['span'] = array();
$allowed_tags_student['col']['style'] = array();
$allowed_tags_student['col']['title'] = array();
$allowed_tags_student['col']['valign'] = array();
$allowed_tags_student['col']['width'] = array();
$allowed_tags_student['col']['xml:lang'] = array();

// colgroup
$allowed_tags_student['colgroup'] = array();
$allowed_tags_student['colgroup']['align'] = array();
$allowed_tags_student['colgroup']['class'] = array();
$allowed_tags_student['colgroup']['dir'] = array();
$allowed_tags_student['colgroup']['id'] = array();
$allowed_tags_student['colgroup']['lang'] = array();
$allowed_tags_student['colgroup']['span'] = array();
$allowed_tags_student['colgroup']['style'] = array();
$allowed_tags_student['colgroup']['title'] = array();
$allowed_tags_student['colgroup']['valign'] = array();
$allowed_tags_student['colgroup']['width'] = array();
$allowed_tags_student['colgroup']['xml:lang'] = array();

// dd
$allowed_tags_student['dd'] = array();
$allowed_tags_student['dd']['class'] = array();
$allowed_tags_student['dd']['dir'] = array();
$allowed_tags_student['dd']['id'] = array();
$allowed_tags_student['dd']['lang'] = array();
$allowed_tags_student['dd']['style'] = array();
$allowed_tags_student['dd']['title'] = array();
$allowed_tags_student['dd']['xml:lang'] = array();

// del
$allowed_tags_student['del'] = array();
$allowed_tags_student['del']['cite'] = array();
$allowed_tags_student['del']['class'] = array();
$allowed_tags_student['del']['dir'] = array();
$allowed_tags_student['del']['id'] = array();
$allowed_tags_student['del']['lang'] = array();
$allowed_tags_student['del']['style'] = array();
$allowed_tags_student['del']['title'] = array();
$allowed_tags_student['del']['xml:lang'] = array();

// dfn
$allowed_tags_student['dfn'] = array();
$allowed_tags_student['dfn']['class'] = array();
$allowed_tags_student['dfn']['dir'] = array();
$allowed_tags_student['dfn']['id'] = array();
$allowed_tags_student['dfn']['lang'] = array();
$allowed_tags_student['dfn']['style'] = array();
$allowed_tags_student['dfn']['title'] = array();
$allowed_tags_student['dfn']['xml:lang'] = array();

// dir
$allowed_tags_student['dir'] = array();
$allowed_tags_student['dir']['class'] = array();
$allowed_tags_student['dir']['compact'] = array();
$allowed_tags_student['dir']['dir'] = array();
$allowed_tags_student['dir']['id'] = array();
$allowed_tags_student['dir']['lang'] = array();
$allowed_tags_student['dir']['style'] = array();
$allowed_tags_student['dir']['title'] = array();

// div
$allowed_tags_student['div'] = array();
$allowed_tags_student['div']['align'] = array();
$allowed_tags_student['div']['class'] = array();
$allowed_tags_student['div']['dir'] = array();
$allowed_tags_student['div']['id'] = array();
$allowed_tags_student['div']['lang'] = array();
$allowed_tags_student['div']['style'] = array();
$allowed_tags_student['div']['title'] = array();
$allowed_tags_student['div']['xml:lang'] = array();

// dl
$allowed_tags_student['dl'] = array();
$allowed_tags_student['dl']['class'] = array();
$allowed_tags_student['dl']['dir'] = array();
$allowed_tags_student['dl']['id'] = array();
$allowed_tags_student['dl']['lang'] = array();
$allowed_tags_student['dl']['style'] = array();
$allowed_tags_student['dl']['title'] = array();
$allowed_tags_student['dl']['xml:lang'] = array();

// dt
$allowed_tags_student['dt'] = array();
$allowed_tags_student['dt']['class'] = array();
$allowed_tags_student['dt']['dir'] = array();
$allowed_tags_student['dt']['id'] = array();
$allowed_tags_student['dt']['lang'] = array();
$allowed_tags_student['dt']['style'] = array();
$allowed_tags_student['dt']['title'] = array();
$allowed_tags_student['dt']['xml:lang'] = array();

// em
$allowed_tags_student['em'] = array();
$allowed_tags_student['em']['class'] = array();
$allowed_tags_student['em']['dir'] = array();
$allowed_tags_student['em']['id'] = array();
$allowed_tags_student['em']['lang'] = array();
$allowed_tags_student['em']['style'] = array();
$allowed_tags_student['em']['title'] = array();
$allowed_tags_student['em']['xml:lang'] = array();

// embed
$allowed_tags_student['embed'] = array();
$allowed_tags_student['embed']['height'] = array();
$allowed_tags_student['embed']['width'] = array();
$allowed_tags_student['embed']['type'] = array();
//$allowed_tags_student['embed']['quality'] = array();
$allowed_tags_student['embed']['src'] = array();
$allowed_tags_student['embed']['flashvars'] = array();
$allowed_tags_student['embed']['allowscriptaccess'] = array();
$allowed_tags_student['embed']['allowfullscreen'] = array();
//$allowed_tags_student['embed']['bgcolor'] = array();
//$allowed_tags_student['embed']['pluginspage'] = array();

// font
$allowed_tags_student['font'] = array();
$allowed_tags_student['font']['face'] = array();
$allowed_tags_student['font']['class'] = array();
$allowed_tags_student['font']['color'] = array();
$allowed_tags_student['font']['dir'] = array();
$allowed_tags_student['font']['id'] = array();
$allowed_tags_student['font']['lang'] = array();
$allowed_tags_student['font']['size'] = array();
$allowed_tags_student['font']['style'] = array();
$allowed_tags_student['font']['title'] = array();

// frame
$allowed_tags_student_full_page['frame'] = array();
$allowed_tags_student_full_page['frame']['class'] = array();
$allowed_tags_student_full_page['frame']['frameborder'] = array();
$allowed_tags_student_full_page['frame']['id'] = array();
$allowed_tags_student_full_page['frame']['longsesc'] = array();
$allowed_tags_student_full_page['frame']['marginheight'] = array();
$allowed_tags_student_full_page['frame']['marginwidth'] = array();
$allowed_tags_student_full_page['frame']['name'] = array();
$allowed_tags_student_full_page['frame']['noresize'] = array();
$allowed_tags_student_full_page['frame']['scrolling'] = array();
$allowed_tags_student_full_page['frame']['src'] = array();
$allowed_tags_student_full_page['frame']['style'] = array();
$allowed_tags_student_full_page['frame']['title'] = array();

// frameset
$allowed_tags_student_full_page['frameset'] = array();
$allowed_tags_student_full_page['frameset']['class'] = array();
$allowed_tags_student_full_page['frameset']['cols'] = array();
$allowed_tags_student_full_page['frameset']['id'] = array();
$allowed_tags_student_full_page['frameset']['rows'] = array();
$allowed_tags_student_full_page['frameset']['style'] = array();
$allowed_tags_student_full_page['frameset']['title'] = array();

// head
$allowed_tags_student_full_page['head'] = array();
$allowed_tags_student_full_page['head']['dir'] = array();
$allowed_tags_student_full_page['head']['lang'] = array();
$allowed_tags_student_full_page['head']['profile'] = array();
$allowed_tags_student_full_page['head']['xml:lang'] = array();

// h1
$allowed_tags_student['h1'] = array();
$allowed_tags_student['h1']['align'] = array();
$allowed_tags_student['h1']['class'] = array();
$allowed_tags_student['h1']['dir'] = array();
$allowed_tags_student['h1']['id'] = array();
$allowed_tags_student['h1']['lang'] = array();
$allowed_tags_student['h1']['style'] = array();
$allowed_tags_student['h1']['title'] = array();
$allowed_tags_student['h1']['xml:lang'] = array();

// h2
$allowed_tags_student['h2'] = array();
$allowed_tags_student['h2']['align'] = array();
$allowed_tags_student['h2']['class'] = array();
$allowed_tags_student['h2']['dir'] = array();
$allowed_tags_student['h2']['id'] = array();
$allowed_tags_student['h2']['lang'] = array();
$allowed_tags_student['h2']['style'] = array();
$allowed_tags_student['h2']['title'] = array();
$allowed_tags_student['h2']['xml:lang'] = array();

// h3
$allowed_tags_student['h3'] = array();
$allowed_tags_student['h3']['align'] = array();
$allowed_tags_student['h3']['class'] = array();
$allowed_tags_student['h3']['dir'] = array();
$allowed_tags_student['h3']['id'] = array();
$allowed_tags_student['h3']['lang'] = array();
$allowed_tags_student['h3']['style'] = array();
$allowed_tags_student['h3']['title'] = array();
$allowed_tags_student['h3']['xml:lang'] = array();

// h4
$allowed_tags_student['h4'] = array();
$allowed_tags_student['h4']['align'] = array();
$allowed_tags_student['h4']['class'] = array();
$allowed_tags_student['h4']['dir'] = array();
$allowed_tags_student['h4']['id'] = array();
$allowed_tags_student['h4']['lang'] = array();
$allowed_tags_student['h4']['style'] = array();
$allowed_tags_student['h4']['title'] = array();
$allowed_tags_student['h4']['xml:lang'] = array();

// h5
$allowed_tags_student['h5'] = array();
$allowed_tags_student['h5']['align'] = array();
$allowed_tags_student['h5']['class'] = array();
$allowed_tags_student['h5']['dir'] = array();
$allowed_tags_student['h5']['id'] = array();
$allowed_tags_student['h5']['lang'] = array();
$allowed_tags_student['h5']['style'] = array();
$allowed_tags_student['h5']['title'] = array();
$allowed_tags_student['h5']['xml:lang'] = array();

// h6
$allowed_tags_student['h6'] = array();
$allowed_tags_student['h6']['align'] = array();
$allowed_tags_student['h6']['class'] = array();
$allowed_tags_student['h6']['dir'] = array();
$allowed_tags_student['h6']['id'] = array();
$allowed_tags_student['h6']['lang'] = array();
$allowed_tags_student['h6']['style'] = array();
$allowed_tags_student['h6']['title'] = array();
$allowed_tags_student['h6']['xml:lang'] = array();

// hr
$allowed_tags_student['hr'] = array();
$allowed_tags_student['hr']['align'] = array();
$allowed_tags_student['hr']['class'] = array();
$allowed_tags_student['hr']['dir'] = array();
$allowed_tags_student['hr']['id'] = array();
$allowed_tags_student['hr']['lang'] = array();
$allowed_tags_student['hr']['noshade'] = array();
$allowed_tags_student['hr']['size'] = array();
$allowed_tags_student['hr']['style'] = array();
$allowed_tags_student['hr']['title'] = array();
$allowed_tags_student['hr']['width'] = array();
$allowed_tags_student['hr']['xml:lang'] = array();

// html
$allowed_tags_student_full_page['html'] = array();
$allowed_tags_student_full_page['html']['dir'] = array();
$allowed_tags_student_full_page['html']['lang'] = array();
$allowed_tags_student_full_page['html']['xml:lang'] = array();
$allowed_tags_student_full_page['html']['xmlns'] = array();

// i
$allowed_tags_student['i'] = array();
$allowed_tags_student['i']['class'] = array();
$allowed_tags_student['i']['dir'] = array();
$allowed_tags_student['i']['id'] = array();
$allowed_tags_student['i']['lang'] = array();
$allowed_tags_student['i']['style'] = array();
$allowed_tags_student['i']['title'] = array();
$allowed_tags_student['i']['xml:lang'] = array();

// img
$allowed_tags_student['img'] = array();
$allowed_tags_student['img']['alt'] = array();
$allowed_tags_student['img']['align'] = array();
$allowed_tags_student['img']['border'] = array();
$allowed_tags_student['img']['class'] = array();
$allowed_tags_student['img']['dir'] = array();
$allowed_tags_student['img']['id'] = array();
$allowed_tags_student['img']['height'] = array();
$allowed_tags_student['img']['hspace'] = array();
//$allowed_tags_student['img']['ismap'] = array();
$allowed_tags_student['img']['lang'] = array();
$allowed_tags_student['img']['longdesc'] = array();
$allowed_tags_student['img']['style'] = array();
$allowed_tags_student['img']['src'] = array();
$allowed_tags_student['img']['title'] = array();
//$allowed_tags_student['img']['usemap'] = array();
$allowed_tags_student['img']['vspace'] = array();
$allowed_tags_student['img']['width'] = array();
$allowed_tags_student['img']['xml:lang'] = array();

// ins
$allowed_tags_student['ins'] = array();
$allowed_tags_student['ins']['cite'] = array();
$allowed_tags_student['ins']['class'] = array();
$allowed_tags_student['ins']['dir'] = array();
$allowed_tags_student['ins']['id'] = array();
$allowed_tags_student['ins']['lang'] = array();
$allowed_tags_student['ins']['style'] = array();
$allowed_tags_student['ins']['title'] = array();
$allowed_tags_student['ins']['xml:lang'] = array();

// kbd
$allowed_tags_student['kbd'] = array();
$allowed_tags_student['kbd']['class'] = array();
$allowed_tags_student['kbd']['dir'] = array();
$allowed_tags_student['kbd']['id'] = array();
$allowed_tags_student['kbd']['lang'] = array();
$allowed_tags_student['kbd']['style'] = array();
$allowed_tags_student['kbd']['title'] = array();
$allowed_tags_student['kbd']['xml:lang'] = array();

// li
$allowed_tags_student['li'] = array();
$allowed_tags_student['li']['class'] = array();
$allowed_tags_student['li']['dir'] = array();
$allowed_tags_student['li']['id'] = array();
$allowed_tags_student['li']['lang'] = array();
$allowed_tags_student['li']['style'] = array();
$allowed_tags_student['li']['title'] = array();
$allowed_tags_student['li']['type'] = array();
$allowed_tags_student['li']['value'] = array();
$allowed_tags_student['li']['xml:lang'] = array();

// link
$allowed_tags_student_full_page['link'] = array();
$allowed_tags_student_full_page['link']['charset'] = array();
$allowed_tags_student_full_page['link']['href'] = array();
$allowed_tags_student_full_page['link']['hreflang'] = array();
$allowed_tags_student_full_page['link']['media'] = array();
$allowed_tags_student_full_page['link']['rel'] = array();
$allowed_tags_student_full_page['link']['rev'] = array();
$allowed_tags_student_full_page['link']['target'] = array();
$allowed_tags_student_full_page['link']['type'] = array();

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
$allowed_tags_student['menu'] = array();
$allowed_tags_student['menu']['class'] = array();
$allowed_tags_student['menu']['compact'] = array();
$allowed_tags_student['menu']['dir'] = array();
$allowed_tags_student['menu']['id'] = array();
$allowed_tags_student['menu']['lang'] = array();
$allowed_tags_student['menu']['style'] = array();
$allowed_tags_student['menu']['title'] = array();

// meta
$allowed_tags_student_full_page['meta'] = array();
$allowed_tags_student_full_page['meta']['content'] = array();
$allowed_tags_student_full_page['meta']['dir'] = array();
$allowed_tags_student_full_page['meta']['http-equiv'] = array();
$allowed_tags_student_full_page['meta']['lang'] = array();
$allowed_tags_student_full_page['meta']['name'] = array();
$allowed_tags_student_full_page['meta']['scheme'] = array();
$allowed_tags_student_full_page['meta']['xml:lang'] = array();

// noframes
$allowed_tags_student_full_page['noframes'] = array();
$allowed_tags_student_full_page['noframes']['class'] = array();
$allowed_tags_student_full_page['noframes']['dir'] = array();
$allowed_tags_student_full_page['noframes']['id'] = array();
$allowed_tags_student_full_page['noframes']['lang'] = array();
$allowed_tags_student_full_page['noframes']['style'] = array();
$allowed_tags_student_full_page['noframes']['title'] = array();
$allowed_tags_student_full_page['noframes']['xml:lang'] = array();

// object
$allowed_tags_student['object'] = array();
//$allowed_tags_student['object']['align'] = array();
//$allowed_tags_student['object']['archive'] = array();
//$allowed_tags_student['object']['border'] = array();
$allowed_tags_student['object']['class'] = array();
$allowed_tags_student['object']['classid'] = array();
$allowed_tags_student['object']['codebase'] = array();
//$allowed_tags_student['object']['codetype'] = array();
$allowed_tags_student['object']['data'] = array();
//$allowed_tags_student['object']['declare'] = array();
$allowed_tags_student['object']['dir'] = array();
$allowed_tags_student['object']['id'] = array();
$allowed_tags_student['object']['height'] = array();
//$allowed_tags_student['object']['hspace'] = array();
$allowed_tags_student['object']['lang'] = array();
//$allowed_tags_student['object']['name'] = array();
//$allowed_tags_student['object']['standby'] = array();
$allowed_tags_student['object']['style'] = array();
$allowed_tags_student['object']['title'] = array();
$allowed_tags_student['object']['type'] = array();
//$allowed_tags_student['object']['usemap'] = array();
//$allowed_tags_student['object']['vspace'] = array();
$allowed_tags_student['object']['width'] = array();
$allowed_tags_student['object']['xml:lang'] = array();

// ol
$allowed_tags_student['ol'] = array();
$allowed_tags_student['ol']['class'] = array();
$allowed_tags_student['ol']['compact'] = array();
$allowed_tags_student['ol']['dir'] = array();
$allowed_tags_student['ol']['id'] = array();
$allowed_tags_student['ol']['lang'] = array();
$allowed_tags_student['ol']['start'] = array();
$allowed_tags_student['ol']['style'] = array();
$allowed_tags_student['ol']['title'] = array();
$allowed_tags_student['ol']['type'] = array();
$allowed_tags_student['ol']['xml:lang'] = array();

// p
$allowed_tags_student['p'] = array();
$allowed_tags_student['p']['align'] = array();
$allowed_tags_student['p']['class'] = array();
$allowed_tags_student['p']['dir'] = array();
$allowed_tags_student['p']['id'] = array();
$allowed_tags_student['p']['lang'] = array();
$allowed_tags_student['p']['style'] = array();
$allowed_tags_student['p']['title'] = array();
$allowed_tags_student['p']['xml:lang'] = array();

// param
$allowed_tags_student['param'] = array();
$allowed_tags_student['param']['name'] = array();
//$allowed_tags_student['param']['type'] = array();
$allowed_tags_student['param']['value'] = array();
//$allowed_tags_student['param']['valuetype'] = array();

// pre
$allowed_tags_student['pre'] = array();
$allowed_tags_student['pre']['class'] = array();
$allowed_tags_student['pre']['dir'] = array();
$allowed_tags_student['pre']['id'] = array();
$allowed_tags_student['pre']['lang'] = array();
$allowed_tags_student['pre']['style'] = array();
$allowed_tags_student['pre']['title'] = array();
$allowed_tags_student['pre']['width'] = array();
$allowed_tags_student['pre']['xml:lang'] = array();

// q
$allowed_tags_student['q'] = array();
$allowed_tags_student['q']['cite'] = array();
$allowed_tags_student['q']['class'] = array();
$allowed_tags_student['q']['dir'] = array();
$allowed_tags_student['q']['id'] = array();
$allowed_tags_student['q']['lang'] = array();
$allowed_tags_student['q']['style'] = array();
$allowed_tags_student['q']['title'] = array();
$allowed_tags_student['q']['xml:lang'] = array();

// s
$allowed_tags_student['s'] = array();
$allowed_tags_student['s']['class'] = array();
$allowed_tags_student['s']['dir'] = array();
$allowed_tags_student['s']['id'] = array();
$allowed_tags_student['s']['lang'] = array();
$allowed_tags_student['s']['style'] = array();
$allowed_tags_student['q']['title'] = array();

// samp
$allowed_tags_student['samp'] = array();
$allowed_tags_student['samp']['class'] = array();
$allowed_tags_student['samp']['dir'] = array();
$allowed_tags_student['samp']['id'] = array();
$allowed_tags_student['samp']['lang'] = array();
$allowed_tags_student['samp']['style'] = array();
$allowed_tags_student['samp']['title'] = array();
$allowed_tags_student['samp']['xml:lang'] = array();

// small
$allowed_tags_student['small'] = array();
$allowed_tags_student['small']['class'] = array();
$allowed_tags_student['small']['dir'] = array();
$allowed_tags_student['small']['id'] = array();
$allowed_tags_student['small']['lang'] = array();
$allowed_tags_student['small']['style'] = array();
$allowed_tags_student['small']['title'] = array();
$allowed_tags_student['small']['xml:lang'] = array();

// span
$allowed_tags_student['span'] = array();
$allowed_tags_student['span']['class'] = array();
$allowed_tags_student['span']['dir'] = array();
$allowed_tags_student['span']['id'] = array();
$allowed_tags_student['span']['lang'] = array();
$allowed_tags_student['span']['style'] = array();
$allowed_tags_student['span']['title'] = array();
$allowed_tags_student['span']['xml:lang'] = array();

// strike
$allowed_tags_student['strike'] = array();
$allowed_tags_student['strike']['class'] = array();
$allowed_tags_student['strike']['dir'] = array();
$allowed_tags_student['strike']['id'] = array();
$allowed_tags_student['strike']['lang'] = array();
$allowed_tags_student['strike']['style'] = array();
$allowed_tags_student['strike']['title'] = array();

// strong
$allowed_tags_student['strong'] = array();
$allowed_tags_student['strong']['class'] = array();
$allowed_tags_student['strong']['dir'] = array();
$allowed_tags_student['strong']['id'] = array();
$allowed_tags_student['strong']['lang'] = array();
$allowed_tags_student['strong']['style'] = array();
$allowed_tags_student['strong']['title'] = array();
$allowed_tags_student['strong']['xml:lang'] = array();

// style
$allowed_tags_student_full_page['style'] = array();
$allowed_tags_student_full_page['style']['dir'] = array();
$allowed_tags_student_full_page['style']['lang'] = array();
$allowed_tags_student_full_page['style']['media'] = array();
$allowed_tags_student_full_page['style']['title'] = array();
$allowed_tags_student_full_page['style']['type'] = array();
$allowed_tags_student_full_page['style']['xml:lang'] = array();

// sub
$allowed_tags_student['sub'] = array();
$allowed_tags_student['sub']['class'] = array();
$allowed_tags_student['sub']['dir'] = array();
$allowed_tags_student['sub']['id'] = array();
$allowed_tags_student['sub']['lang'] = array();
$allowed_tags_student['sub']['style'] = array();
$allowed_tags_student['sub']['title'] = array();
$allowed_tags_student['sub']['xml:lang'] = array();

// sup
$allowed_tags_student['sup'] = array();
$allowed_tags_student['sup']['class'] = array();
$allowed_tags_student['sup']['dir'] = array();
$allowed_tags_student['sup']['id'] = array();
$allowed_tags_student['sup']['lang'] = array();
$allowed_tags_student['sup']['style'] = array();
$allowed_tags_student['sup']['title'] = array();
$allowed_tags_student['sup']['xml:lang'] = array();

// table
$allowed_tags_student['table'] = array();
$allowed_tags_student['table']['align'] = array();
$allowed_tags_student['table']['bgcolor'] = array();
$allowed_tags_student['table']['border'] = array();
$allowed_tags_student['table']['cellpadding'] = array();
$allowed_tags_student['table']['cellspacing'] = array();
$allowed_tags_student['table']['class'] = array();
$allowed_tags_student['table']['dir'] = array();
$allowed_tags_student['table']['frame'] = array();
$allowed_tags_student['table']['id'] = array();
$allowed_tags_student['table']['lang'] = array();
$allowed_tags_student['table']['rules'] = array();
$allowed_tags_student['table']['style'] = array();
$allowed_tags_student['table']['summary'] = array();
$allowed_tags_student['table']['title'] = array();
$allowed_tags_student['table']['width'] = array();
$allowed_tags_student['table']['xml:lang'] = array();

// tbody
$allowed_tags_student['tbody'] = array();
$allowed_tags_student['tbody']['align'] = array();
//$allowed_tags_student['tbody']['char'] = array();
//$allowed_tags_student['tbody']['charoff'] = array();
$allowed_tags_student['tbody']['class'] = array();
$allowed_tags_student['tbody']['dir'] = array();
$allowed_tags_student['tbody']['id'] = array();
$allowed_tags_student['tbody']['lang'] = array();
$allowed_tags_student['tbody']['style'] = array();
$allowed_tags_student['tbody']['title'] = array();
$allowed_tags_student['tbody']['valign'] = array();
$allowed_tags_student['tbody']['xml:lang'] = array();

// td
$allowed_tags_student['td'] = array();
$allowed_tags_student['td']['abbr'] = array();
$allowed_tags_student['td']['align'] = array();
//$allowed_tags_student['td']['axis'] = array();
$allowed_tags_student['td']['bgcolor'] = array();
//$allowed_tags_student['td']['char'] = array();
//$allowed_tags_student['td']['charoff'] = array();
$allowed_tags_student['td']['class'] = array();
$allowed_tags_student['td']['colspan'] = array();
$allowed_tags_student['td']['dir'] = array();
//$allowed_tags_student['td']['headers'] = array();
$allowed_tags_student['td']['height'] = array();
$allowed_tags_student['td']['id'] = array();
$allowed_tags_student['td']['lang'] = array();
$allowed_tags_student['td']['nowrap'] = array();
$allowed_tags_student['td']['rowspan'] = array();
//$allowed_tags_student['td']['scope'] = array();
$allowed_tags_student['td']['style'] = array();
$allowed_tags_student['td']['title'] = array();
$allowed_tags_student['td']['valign'] = array();
$allowed_tags_student['td']['width'] = array();
$allowed_tags_student['td']['xml:lang'] = array();

// tfoot
$allowed_tags_student['tfoot'] = array();
$allowed_tags_student['tfoot']['align'] = array();
//$allowed_tags_student['tfoot']['char'] = array();
//$allowed_tags_student['tfoot']['charoff'] = array();
$allowed_tags_student['tfoot']['class'] = array();
$allowed_tags_student['tfoot']['dir'] = array();
$allowed_tags_student['tfoot']['id'] = array();
$allowed_tags_student['tfoot']['lang'] = array();
$allowed_tags_student['tfoot']['style'] = array();
$allowed_tags_student['tfoot']['title'] = array();
$allowed_tags_student['tfoot']['valign'] = array();
$allowed_tags_student['tfoot']['xml:lang'] = array();

// th
$allowed_tags_student['th'] = array();
$allowed_tags_student['th']['abbr'] = array();
$allowed_tags_student['th']['align'] = array();
//$allowed_tags_student['th']['axis'] = array();
$allowed_tags_student['th']['bgcolor'] = array();
//$allowed_tags_student['th']['char'] = array();
//$allowed_tags_student['th']['charoff'] = array();
$allowed_tags_student['th']['class'] = array();
$allowed_tags_student['th']['colspan'] = array();
$allowed_tags_student['th']['dir'] = array();
//$allowed_tags_student['th']['headers'] = array();
$allowed_tags_student['th']['height'] = array();
$allowed_tags_student['th']['id'] = array();
$allowed_tags_student['th']['lang'] = array();
$allowed_tags_student['th']['nowrap'] = array();
$allowed_tags_student['th']['rowspan'] = array();
//$allowed_tags_student['th']['scope'] = array();
$allowed_tags_student['th']['style'] = array();
$allowed_tags_student['th']['title'] = array();
$allowed_tags_student['th']['valign'] = array();
$allowed_tags_student['th']['width'] = array();
$allowed_tags_student['th']['xml:lang'] = array();

// thead
$allowed_tags_student['thead'] = array();
$allowed_tags_student['thead']['align'] = array();
$allowed_tags_student['thead']['class'] = array();
//$allowed_tags_student['thead']['char'] = array();
//$allowed_tags_student['thead']['charoff'] = array();
$allowed_tags_student['thead']['dir'] = array();
$allowed_tags_student['thead']['id'] = array();
$allowed_tags_student['thead']['lang'] = array();
$allowed_tags_student['thead']['style'] = array();
$allowed_tags_student['thead']['title'] = array();
$allowed_tags_student['thead']['valign'] = array();
$allowed_tags_student['thead']['xml:lang'] = array();

// title
$allowed_tags_student_full_page['title'] = array();
$allowed_tags_student_full_page['title']['dir'] = array();
$allowed_tags_student_full_page['title']['lang'] = array();
$allowed_tags_student_full_page['title']['xml:lang'] = array();

// tr
$allowed_tags_student['tr'] = array();
$allowed_tags_student['tr']['align'] = array();
$allowed_tags_student['tr']['bgcolor'] = array();
//$allowed_tags_student['tr']['char'] = array();
//$allowed_tags_student['tr']['charoff'] = array();
$allowed_tags_student['tr']['class'] = array();
$allowed_tags_student['tr']['dir'] = array();
$allowed_tags_student['tr']['id'] = array();
$allowed_tags_student['tr']['lang'] = array();
$allowed_tags_student['tr']['style'] = array();
$allowed_tags_student['tr']['title'] = array();
$allowed_tags_student['tr']['valign'] = array();
$allowed_tags_student['tr']['xml:lang'] = array();

// tt
$allowed_tags_student['tt'] = array();
$allowed_tags_student['tt']['class'] = array();
$allowed_tags_student['tt']['dir'] = array();
$allowed_tags_student['tt']['id'] = array();
$allowed_tags_student['tt']['lang'] = array();
$allowed_tags_student['tt']['style'] = array();
$allowed_tags_student['tt']['title'] = array();
$allowed_tags_student['tt']['xml:lang'] = array();

// u
$allowed_tags_student['u'] = array();
$allowed_tags_student['u']['class'] = array();
$allowed_tags_student['u']['dir'] = array();
$allowed_tags_student['u']['id'] = array();
$allowed_tags_student['u']['lang'] = array();
$allowed_tags_student['u']['style'] = array();
$allowed_tags_student['u']['title'] = array();

// ul
$allowed_tags_student['ul'] = array();
$allowed_tags_student['ul']['class'] = array();
$allowed_tags_student['ul']['compact'] = array();
$allowed_tags_student['ul']['dir'] = array();
$allowed_tags_student['ul']['id'] = array();
$allowed_tags_student['ul']['lang'] = array();
$allowed_tags_student['ul']['style'] = array();
$allowed_tags_student['ul']['title'] = array();
$allowed_tags_student['ul']['type'] = array();
$allowed_tags_student['ul']['xml:lang'] = array();

// var
$allowed_tags_student['var'] = array();
$allowed_tags_student['var']['class'] = array();
$allowed_tags_student['var']['dir'] = array();
$allowed_tags_student['var']['id'] = array();
$allowed_tags_student['var']['lang'] = array();
$allowed_tags_student['var']['style'] = array();
$allowed_tags_student['var']['title'] = array();
$allowed_tags_student['var']['xml:lang'] = array();


// ALLOWED HTML FOR TEACHERS

// Allow all HTML allowed for students
$allowed_tags_teacher = $allowed_tags_student;

// noscript
$allowed_tags_teacher['noscript'] = array();

// script
$allowed_tags_teacher['script'] = array();
$allowed_tags_teacher['script']['type'] = array();

// TODO:
// 1. The tags <html>, <head>, <body> should not be allowed for document fragments.
// 2. To be checked whether HTMLPurifier "silently" passes these tags.

$allowed_tags_teacher['html'] = array();
$allowed_tags_teacher['html']['xmlns'] = array();

$allowed_tags_teacher['head'] = array();
$allowed_tags_teacher['head']['profile'] = array();

// body
$allowed_tags_teacher['body'] = array();
$allowed_tags_teacher['body']['alink'] = array();
$allowed_tags_teacher['body']['background'] = array();
$allowed_tags_teacher['body']['bgcolor'] = array();
$allowed_tags_teacher['body']['link'] = array();
$allowed_tags_teacher['body']['text'] = array();
$allowed_tags_teacher['body']['vlink'] = array();


$allowed_tags_teacher_full_page = $allowed_tags_student_full_page;


// ALLOWED HTML FOR ANONYMOUS USERS

$allowed_tags_anonymous = $allowed_tags_student;
$allowed_tags_anonymous_full_page = $allowed_tags_student_full_page;
// Add restrictions here.
unset($allowed_tags_anonymous['embed']);
unset($allowed_tags_anonymous['object']);
unset($allowed_tags_anonymous['param']);


// HTMLPURIFIER-COMPATIBLE SETTINGS

function convert_kses_to_htmlpurifier($allowed_tags) {
    $allowed_html = array();
    foreach ($allowed_tags as $key1 => & $value1) {
        $result[0][] = $key1;
        if (count($value1) > 0) {
            $attr = array();
            foreach ($value1 as $key2 => & $value2) {
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
