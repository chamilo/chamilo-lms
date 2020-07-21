<?php

/* For license terms, see /license.txt */

$varString = api_get_self();
$varUrlList = str_replace("node_process.php", "list.php", $varString);
$varUrlProcess = str_replace("list.php", "node_process.php", $varString);

switch ($action) {
    case 'add':
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $date = new DateTime();
            $year = $date->format("Y");
            $month = $date->format('m');
            $day = $date->format('j');
            $dateStr = $day.'/'.$month.'/'.$year;
            $params = [
                'title' => $values['title'],
                'creation_date' => $dateStr,
                'user_id' => $userId,
                'node_type' => $values['node_type'],
                'terms_a' => $values['terms_a'],
                'terms_b' => $values['terms_b'],
                'terms_c' => $values['terms_c'],
                'terms_d' => $values['terms_d'],
                'terms_e' => $values['terms_e'],
                'terms_f' => $values['terms_f'],
                'descript' => $values['descript'],
                'url_id' => $urlId,
            ];
            $result = Database::insert($table, $params);

            $objectId = 0;
            if (!$result) {
            } else {
                Display::addFlash(Display::return_message(get_lang('Added')));
                $objectId = Database::insert_id();
            }

            $varUrlProcess = $varUrlProcess.'?id='.$objectId.'&action=add';
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
                'terms_a' => $values['terms_a'],
                'terms_b' => $values['terms_b'],
                'terms_c' => $values['terms_c'],
                'terms_d' => $values['terms_d'],
                'terms_e' => $values['terms_e'],
                'terms_f' => $values['terms_f'],
                'descript' => $values['descript'],
            ];
            Database::update($table, $params, ['id = ?' => $id]);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            $varUrlProcess = $varUrlProcess.'?id='.$id.'&action=edit';
            header('Location: '.$varUrlProcess);
            exit;
        }
        break;
    case 'delete':
        if (!empty($term)) {
            Database::delete($table, ['id = ?' => $id]);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
            header('Location: '.$varUrlList);
            exit;
        }
        break;
}
