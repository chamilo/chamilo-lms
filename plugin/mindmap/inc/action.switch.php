<?php

/* For licensing terms, see /license.txt */

$varString = api_get_self();
$varUrlProcess = $varString;

switch ($action) {
    case 'add':
        if ($form->validate()) {
            $values = $form->getSubmitValues();

            $params = [
                'title' => $values['title'],
                'user_id' => $userId,
                'mindmap_type' => $values['mindmap_type'],
                'description' => $values['description'],
                'is_public' => (empty($values['is_public']) ? 0 : (int) $values['is_public']),
                'is_shared' => (empty($values['is_shared']) ? 0 : (int) $values['is_shared']),
                'c_id' => $cid,
                'url_id' => $urlId,
                'session_id' => $sessionId,
            ];
            $result = Database::insert($plugin->table, $params);

            if ($result) {
                Display::addFlash(Display::return_message(get_lang('Added')));
            }

            $varUrlProcess = $varUrlProcess.'?cid='.$cid.'&sid='.$sessionId;
            header('Location: '.$varUrlProcess);
            exit;
        }
        break;
    case 'edit':
        $form->setDefaults($term);
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $params = [
                'title' => $values['title'],
                'description' => $values['description'],
                'is_public' => $values['is_public'],
                'is_shared' => $values['is_shared'],
            ];
            Database::update($plugin->table, $params, ['id = ?' => $id]);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            $varUrlProcess = $varUrlProcess.'?cid='.$cid.'&sid='.$sessionId;
            header('Location: '.$varUrlProcess);
            exit;
        }
        break;
    case 'delete':
        if (!empty($term)) {
            Database::delete($plugin->table, ['id = ?' => $id]);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
            header('Location: '.$varUrlProcess.'?cid='.$cid.'&sid='.$sessionId);
            exit;
        }
        break;
}
