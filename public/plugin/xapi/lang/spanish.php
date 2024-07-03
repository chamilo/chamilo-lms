<?php

/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Experience API (xAPI)';
$strings['plugin_comment'] = 'Permite incorporar un Learning Record Store externo (o interno) y usar actividades con la especificación xAPI.';

$strings[XApiPlugin::SETTING_UUID_NAMESPACE] = 'UUID Namespace';
$strings[XApiPlugin::SETTING_UUID_NAMESPACE.'_help'] = 'Namespace para los identificadores unicos universales (UUID) usados como IDs de statements.'
    .'<br>Esto es generado automáticamente por Chamilo LMS. <strong>No reemplazarlo.</strong>';
$strings['lrs_url'] = 'LRS endpoint';
$strings['lrs_url_help'] = 'Base de la URL del LRS';
$strings['lrs_auth_username'] = 'Usuario del LRS';
$strings['lrs_auth_username_help'] = 'Usuario para autenticación con HTTP básica';
$strings['lrs_auth_password'] = 'Contraseña del LRS';
$strings['lrs_auth_password_help'] = 'Contraseña para autenticación con HTTP básica';
$strings['cron_lrs_url'] = 'Cron: LRS endpoint';
$strings['cron_lrs_url_help'] = 'Opcional. Base de la URL alternativa del LRS del proceso cron.';
$strings['cron_lrs_auth_username'] = 'Cron: Usuario del LRS';
$strings['cron_lrs_auth_username_help'] = 'Opcional. Usuario alternativo para autenticación con HTTP básica del proceso cron';
$strings['cron_lrs_auth_password'] = 'Cron: Contraseña del LRS';
$strings['cron_lrs_auth_password_help'] = 'Opcional. Contraseña alternativa para autenticación con HTTP básica del proceso cron';
$strings['lrs_lp_item_viewed_active'] = 'Visualización de contenido de lección';
$strings['lrs_lp_end_active'] = 'Finalización de lección';
$strings['lrs_quiz_active'] = 'Finalización de ejercicio';
$strings['lrs_quiz_question_active'] = 'Resolución de pregunta en ejercicio';
$strings['lrs_portfolio_active'] = 'Eventos en portafolio';

$strings['NoActivities'] = 'No hay actividades aún';
$strings['ActivityTitle'] = 'Actividad';
$strings['AddActivity'] = 'Agregar actividad';
$strings['TinCanPackage'] = 'Paquete TinCan (zip)';
$strings['Cmi5Package'] = 'Paquete Cmi5(zip)';
$strings['OnlyZipAllowed'] = 'Sólo archivos ZIP están permitidos (.zip).';
$strings['ActivityImported'] = 'Actividad importada.';
$strings['EditActivity'] = 'Editar actividad';
$strings['ActivityUpdated'] = 'Actividad actualizada';
$strings['ActivityLaunchUrl'] = 'URL de inicio';
$strings['ActivityId'] = 'ID de actividad';
$strings['ActivityType'] = 'Tipo de actividad';
$strings['ActivityDeleted'] = 'Actividad eliminada';
$strings['ActivityLaunch'] = 'Iniciar';
$strings['ActivityFirstLaunch'] = 'Primer inicio';
$strings['ActivityLastLaunch'] = 'Últimmo inicio';
$strings['LaunchNewAttempt'] = 'Iniciar nuevo intento';
$strings['LrsConfiguration'] = 'Configuración de LRS';
$strings['Verb'] = 'Verbo';
$strings['Actor'] = 'Actor';
$strings['ToolTinCan'] = 'Actividades';
$strings['Terminated'] = 'Terminó';
$strings['Completed'] = 'Completó';
$strings['Answered'] = 'Respondió';
$strings['Viewed'] = 'Visualizó';
$strings['ActivityAddedToLPCannotBeAccessed'] = 'Esta actividad ha sido incluida en una secuencia de aprendizaje, por lo cual no podrá ser accesible directamente por los estudiantes desde aquí.';
$strings['XApiPackage'] = 'Paquete XApi';
$strings['TinCanAllowMultipleAttempts'] = 'Permitir múltiples intentos';
