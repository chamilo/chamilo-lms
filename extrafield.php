<?php

require_once 'main/inc/global.inc.php';

$choices = [
    'La semaine',
    'Le week-end',
    'Le matin',
    'Le midi',
    'Le soir',
];

$variables = [
    'moment_de_disponibilite' => [
        'type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
        'title' => 'En général, je suis plutôt disponible',
        'choices' => $choices,
    ],
    'deja_sur_place' => [
        'title' => 'Je suis déjà sur place /mon stage/mon emploi a déjà commencé',
        'type' => ExtraField::FIELD_TYPE_CHECKBOX,
    ],
    'outil_de_travail_ordinateur' => [
        'title' => 'Un ordinateur fixe ou portable',
        'type' => ExtraField::FIELD_TYPE_CHECKBOX,
    ],
    'outil_de_travail_tablette' => [
        'title' => 'Une tablette',
        'type' => ExtraField::FIELD_TYPE_CHECKBOX,
    ],
    'outil_de_travail_smartphone' => [
        'title' => 'Un smartphone',
        'type' => ExtraField::FIELD_TYPE_CHECKBOX,
    ],
    'outil_de_travail_ordinateur_so' => [
        'title' => 'Quel est le système d’exploitation ? ',
        'type' => ExtraField::FIELD_TYPE_TEXT,
    ],
    'outil_de_travail_tablette_so' => [
        'title' => 'Quel est le système d’exploitation ? ',
        'type' => ExtraField::FIELD_TYPE_TEXT,
    ],
    'outil_de_travail_smartphone_so' => [
        'title' => 'Quel est le système d’exploitation ? ',
        'type' => ExtraField::FIELD_TYPE_TEXT,
    ],
    'browser_platforme' => [
        'type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
        'title' => 'Pour travailler sur la plateforme, j’utilise le browser suivant:',
        'choices' => ['Firefox', 'Chrome', 'Safari', 'Internet Explorer'],
    ],
    'browser_platforme_autre' => [
        'title' => 'Autre (préciser)',
        'type' => ExtraField::FIELD_TYPE_TEXT,
    ],
    'browser_platforme_version' => [
        'title' => 'Quelle est la version ?',
        'type' => ExtraField::FIELD_TYPE_TEXT,
    ],
];

$extraField = new ExtraField('user');
foreach ($variables as $variable => $data) {
    $params = [
        'variable' => $variable,
        'field_type' => $data['type'],
        'display_text' => $data['title'],
        'visible_to_self' => true,
        'visible_to_others' => false,
        'changeable' => true,
        'filter' => true,
    ];
    if (isset($data['choices'])) {
        $params['field_options'] = implode(';', $data['choices']);
    }
    $id = $extraField->save($params);
    //$extraField->delete($id);
}