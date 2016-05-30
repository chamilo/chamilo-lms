<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldSavedSearch;
$cidReset = true;

require_once 'main/inc/global.inc.php';

api_block_anonymous_users();

$userId = api_get_user_id();
$userInfo = api_get_user_info();

$em = Database::getManager();

$form = new FormValidator('search', 'post', api_get_self());
$form->addHeader(get_lang('Diagnosis'));

/** @var ExtraFieldSavedSearch  $saved */
$search = [
    'user' => $userId
];

$items = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findBy($search);

$extraField = new ExtraField('session');
$extraFieldValue = new ExtraFieldValue('session');
$extra = $extraField->addElements($form, '', [], true, true);

$form->addButtonSave(get_lang('Save'), 'save');

$result = SessionManager::getGridColumns('simple');
$columns = $result['columns'];
$column_model = $result['column_model'];

$defaults = [];
$tagsData = [];
if (!empty($items)) {
    /** @var ExtraFieldSavedSearch $item */
    foreach ($items as $item) {
        $variable = 'extra_'.$item->getField()->getVariable();
        if ($item->getField()->getFieldType() == Extrafield::FIELD_TYPE_TAG) {
            $tagsData[$variable] = $item->getValue();
        }
        $defaults[$variable] = $item->getValue();
    }
}

$form->setDefaults($defaults);

$view = $form->returnForm();
$filterToSend = '';

if ($form->validate()) {
    $params = $form->getSubmitValues();
    /** @var \Chamilo\UserBundle\Entity\User $user */
    $user = $em->getRepository('ChamiloUserBundle:User')->find($userId);

    if (isset($params['save'])) {
        // save
        foreach ($params as $key => $value) {
            $found = strpos($key, '__persist__');

            if ($found === false) {
                continue;
            }

            $tempKey = str_replace('__persist__', '', $key);
            if (!isset($params[$tempKey])) {
                $params[$tempKey] = array();
            }
        }

        // Parse params.
        foreach ($params as $key => $value) {
            if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
                continue;
            }

            $field_variable = substr($key, 6);
            $extraFieldInfo = $extraFieldValue
                ->getExtraField()
                ->get_handler_field_info_by_field_variable($field_variable);

            if (!$extraFieldInfo) {
                continue;
            }

            $extraFieldObj = $em->getRepository('ChamiloCoreBundle:ExtraField')->find($extraFieldInfo['id']);

            $search = [
                'field' => $extraFieldObj,
                'user' => $user
            ];

            /** @var ExtraFieldSavedSearch  $saved */
            $saved = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findOneBy($search);

            if ($saved) {
                $saved
                    ->setField($extraFieldObj)
                    ->setUser($user)
                    ->setValue($value)
                ;
                $em->merge($saved);

            } else {
                $saved = new ExtraFieldSavedSearch();
                $saved
                    ->setField($extraFieldObj)
                    ->setUser($user)
                    ->setValue($value)
                ;
                $em->persist($saved);
            }
            $em->flush();
        }

        MessageManager::send_message_simple(
            $userId,
            get_lang('DiagnosisFilledSubject'),
            get_lang('DiagnosisFilledDescription')
        );

        $drhList = UserManager::getDrhListFromUser($userId);
        if ($drhList) {
            foreach ($drhList as $drhId) {
                $subject = sprint_f(get_lang('UserXHasFilledTheDiagnosis'), $userInfo['complete_name']);
                $content = sprint_f(get_lang('UserXHasFilledTheDiagnosisDescription'), $userInfo['complete_name']);
                MessageManager::send_message_simple($drhId, $subject, $content);
            }
        }

        Display::addFlash(Display::return_message(get_lang('Saved')));
        header("Location: ".api_get_self());
        exit;
    } else {
        // Search
        $filters = [];
        // Parse params.
        foreach ($params as $key => $value) {
            if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
                continue;
            }
            if (!empty($value)) {
                $filters[$key] = $value;
            }
        }

        $filterToSend = [];
        if (!empty($filters)) {
            $filterToSend = ['groupOp' => 'AND'];
            if ($filters) {
                $count = 1;
                $countExtraField = 1;
                foreach ($result['column_model'] as $column) {
                    if ($count > 5) {
                        if (isset($filters[$column['name']])) {
                            $defaultValues['jqg'.$countExtraField] = $filters[$column['name']];
                            $filterToSend['rules'][] = ['field' => $column['name'], 'op' => 'cn', 'data' => $filters[$column['name']]];
                        }
                        $countExtraField++;
                    }
                    $count++;
                }
            }
        }
    }
}


$extraField = new ExtraField('user');

$userForm = new FormValidator('user_form', 'post', api_get_self());
$userForm->addHeader(get_lang('User'));
$extra = $extraField->addElements($userForm, api_get_user_id(), [], true, true, array('heures-disponibilite-par-semaine'));
$userForm->addButtonSave(get_lang('Save'));
$userFormToString = $userForm->returnForm();

if ($userForm->validate()) {
    $extraFieldValue = new ExtraFieldValue('user');
    $user_data = $userForm->getSubmitValues();

    $extraFieldValue->saveFieldValues($user_data);
}


$tpl = new Template(get_lang('Diagnosis'));
$tpl->assign('form', $view.$userFormToString);
$content = $tpl->fetch('default/user_portal/search_extra_field.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
