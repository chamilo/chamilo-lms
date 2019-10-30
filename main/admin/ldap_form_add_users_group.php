<?php
/* For licensing terms, see /license.txt */
/**
 * Form to add users through LDAP (deprecated?).
 *
 * @package chamilo.admin
 * Copyright (c) 2007 Mustapha Alouani (supervised by Michel Moreau-Belliard)
 */

/**
 * This form is included by ldap_import_students.php and ldap_import_students_to_session.php.
 */
$nbre = 0;
echo '<form name="form" method="post" action="'.api_get_self().'?annee='.Security::remove_XSS($annee).'">';
if ($statut == 1) {
    echo get_lang(
            'e-mailNotifySubscription'
        ).': <input type="checkbox" name="mailling" value="1" checked="checked"><i>'.get_lang(
            'DontUnchek'
        ).'</i>';
} else {
    echo '<input type="hidden" name="mailling" value="1">';
}
if (!empty($course)) {
    echo '<input type="hidden" name="course" value="'.Security::remove_XSS($course).'">';
} elseif (!empty($id_session)) {
    echo '<input type="hidden" name="id_session" value="'.Security::remove_XSS($id_session).'">';
}
$is_western_name_order = api_is_western_name_order();
echo '<input type="hidden" name="confirmed" value="yes">';
echo '<table border="0" cellspacing="0" width="100%">';
echo '<tr align="center" id="header3">'.
        '<td width="15%"><input type="button" value="'.get_lang('All/None').'" onClick="checkAll();"></td>'.
        '<td width="40%"><b>'.get_lang('e-mail').'</b></td>'.
        ($is_western_name_order
            ? '<td width="15%"><b>'.get_lang('First name').'</b></td>'.
            '<td width="15%"><b>'.get_lang('Name').'</b></td>'
            : '<td width="15%"><b>'.get_lang('Name').'</b></td>'.
            '<td width="15%"><b>'.get_lang('First name').'</b></td>').
        '<td width="15%"><b>'.get_lang('Login').'</b></td>'.
      '</tr>'."\n";
foreach ($nom_form as $key => $val) {
    $nbre = $nbre + 1;
    if ($nbre & 1) {
        $ndiv = 2;
    } else {
        $ndiv = 3;
    }
    echo '<tr align="center" id="header'.$ndiv.'">';
    echo '<td><input type="checkbox" name="checkboxes[]" value="'.$key.'" checked="checked"></td>';
    echo '<td>'.$email_form[$key].'<input type="hidden" name="email_form['.$key.']" size="40" value="'.$email_form[$key].'"></td>';
    if ($is_western_name_order) {
        echo '<td>'.$prenom_form[$key].'<input type="hidden" name="prenom_form['.$key.']" size="20" value="'.$prenom_form[$key].'"></td>';
        echo '<td>'.$nom_form[$key].'<input type="hidden" name="nom_form['.$key.']" size="20" value="'.$nom_form[$key].'"></td>';
    } else {
        echo '<td>'.$nom_form[$key].'<input type="hidden" name="nom_form['.$key.']" size="20" value="'.$nom_form[$key].'"></td>';
        echo '<td>'.$prenom_form[$key].'<input type="hidden" name="prenom_form['.$key.']" size="20" value="'.$prenom_form[$key].'"></td>';
    }
    echo '<td>'.$username_form[$key].'<input type="hidden" name="username_form['.$key.']" size="10" value="'.$username_form[$key].'">';
    echo '<input type="hidden" name="tutor_form['.$key.']" value="0">';
    echo '<input type="hidden" name="admin_form['.$key.']" value="1">';
    echo '<input type="hidden" name="password_form['.$key.']"  value="'.$password_form[$key].'">';
    echo '<input type="hidden" name="statut['.$key.']"  value="'.$statut.'">';
    echo '</td>';
    echo '</tr>';
}
echo '</table>';
echo '<br />';
echo '<br />';
echo '<input type="submit"  name="submit" value="'.get_lang('Submit').'">';
echo '</form>';
