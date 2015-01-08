<?php
/*
 * This script insert session extra fields
 */

//exit;

require_once '../../main/inc/global.inc.php';

api_protect_admin_script();

$horasLectivas = new ExtraField('session');
$horasLectivas->save(array(
    'field_type' => ExtraField::FIELD_TYPE_INTEGER,
    'field_variable' => 'horas_lectivas',
    'field_display_text' => 'Horas lectivas',
    'field_visible' => 1,
    'field_changeable' => 1
));

$costo = new ExtraField('session');
$costo->save(array(
    'field_type' => ExtraField::FIELD_TYPE_FLOAT,
    'field_variable' => 'costo',
    'field_display_text' => 'Costo',
    'field_visible' => 1,
    'field_changeable' => 1
));

$vacantes = new ExtraField('session');
$vacantes->save(array(
    'field_type' => ExtraField::FIELD_TYPE_INTEGER,
    'field_variable' => 'vacantes',
    'field_display_text' => 'Vacantes',
    'field_visible' => 1,
    'field_changeable' => 1
));

$numeroRecomendadoParticipantes = new ExtraField('session');
$numeroRecomendadoParticipantes->save(array(
    'field_type' => ExtraField::FIELD_TYPE_INTEGER,
    'field_variable' => 'numero_recomendado_participantes',
    'field_display_text' => 'Número recomendado de participantes',
    'field_visible' => 1,
    'field_changeable' => 1
));

$lugar = new ExtraField('session');
$lugar->save(array(
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'field_variable' => 'lugar',
    'field_display_text' => 'Lugar',
    'field_visible' => 1,
    'field_changeable' => 1
));

$horario = new ExtraField('session');
$horario->save(array(
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'field_variable' => 'horario',
    'field_display_text' => 'Horario',
    'field_visible' => 1,
    'field_changeable' => 1
));

$permitirVisitantes = new ExtraField('session');
$permitirVisitantes->save(array(
    'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
    'field_variable' => 'permitir_visitantes',
    'field_display_text' => 'Permitir visitantes',
    'field_visible' => 1,
    'field_changeable' => 1
));

$modalidad = new ExtraField('session');
$modalidad->save(array(
    'field_type' => ExtraField::FIELD_TYPE_SELECT,
    'field_variable' => 'modalidad',
    'field_display_text' => 'Modalidad',
    'field_visible' => 1,
    'field_changeable' => 1,
    'field_options' => 'Online; Presencial; B-Learning'
));

$esSesionInduccion = new ExtraField('session');
$esSesionInduccion->save(array(
    'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
    'field_variable' => 'es_induccion',
    'field_display_text' => 'Es sesión de inducción',
    'field_visible' => 1,
    'field_changeable' => 1
));

$esSesionAbierta = new ExtraField('session');
$esSesionAbierta->save(array(
    'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
    'field_variable' => 'es_abierta',
    'field_display_text' => 'Es sesión abierta',
    'field_visible' => 1,
    'field_changeable' => 1
));

$duracion = new ExtraField('session');
$duracion->save(array(
    'field_type' => ExtraField::FIELD_TYPE_INTEGER,
    'field_variable' => 'duracion',
    'field_display_text' => 'Duración',
    'field_visible' => 1,
    'field_changeable' => 1
));

$mostrarEstado = new ExtraField('session');
$mostrarEstado->save(array(
    'field_type' => ExtraField::FIELD_TYPE_SELECT,
    'field_variable' => 'mostrar_estado',
    'field_display_text' => 'Mostrar estado',
    'field_visible' => 1,
    'field_changeable' => 1,
    'field_options' => 'Abierto; En proceso; Cerrado'
));

$inicioPublicacion = new ExtraField('session');
$inicioPublicacion->save(array(
    'field_type' => ExtraField::FIELD_TYPE_DATE,
    'field_variable' => 'inicio_publicacion',
    'field_display_text' => 'Inicio de publicación',
    'field_visible' => 1,
    'field_changeable' => 1
));

$finPublicacion = new ExtraField('session');
$finPublicacion->save(array(
    'field_type' => ExtraField::FIELD_TYPE_DATE,
    'field_variable' => 'fin_publicacion',
    'field_display_text' => 'Fin de publicación',
    'field_visible' => 1,
    'field_changeable' => 1
));

$banner = new ExtraField('session');
$banner->save(array(
    'field_type' => ExtraField::FIELD_TYPE_FILE_IMAGE,
    'field_variable' => 'banner',
    'field_display_text' => 'Banner de la sesión',
    'field_visible' => 1,
    'field_changeable' => 1
));

$brochure = new ExtraField('session');
$brochure->save(array(
    'field_type' => ExtraField::FIELD_TYPE_FILE,
    'field_variable' => 'brochure',
    'field_display_text' => 'Brochure',
    'field_visible' => 1,
    'field_changeable' => 1
));
