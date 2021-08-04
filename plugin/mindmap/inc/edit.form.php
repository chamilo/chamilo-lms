<?php

/* For licensing terms, see /license.txt */

/**
 * Generate the form to add/edit a mindmap record.
 */
$form = new FormValidator(
    'dictionary',
    'post',
    api_get_self().'?action='.$action.'&id='.$id.'&cid='.$cid.'&sid='.$sessionId
);

if ($action === 'add' || $action === 'edit') {
    $form->addText('title', get_lang('Title'), true);
    $form->addText('description', get_lang('Description'), false);
    $isPublic = $form->addElement('checkbox', 'is_public', null, $plugin->get_lang('VisibleByAll'));
    $isShared = $form->addElement('checkbox', 'is_shared', null, $plugin->get_lang('EditableByAll'));
    $form->addElement('hidden', 'mindmap_type', 'mind');
    $form->addButtonSave(get_lang('Save'));
} else {
    $ht = '<p style="text-align:center;" >
            <img alt="'.htmlentities(get_lang('Save')).'" src="img/mindmap128.png" /></p>';
    $form->addElement('static', '', '', $ht);
}
