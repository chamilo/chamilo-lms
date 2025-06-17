<?php
/* For licensing terms, see /license.txt */
/**
 * Strings to Spanish L10n.
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 * @package chamilo.plugin.azure_active_directory
 */
$strings['plugin_title'] = 'Azure Active Directory';
$strings['plugin_comment'] = 'Permite la autenticación de usuarios por Azure Active Directory de Microsoft';

$strings['enable'] = 'Activar';
$strings['app_id'] = 'ID de la aplicación';
$strings['app_id_help'] = 'Introduzca el ID de la aplicación asignado a su app en el portal de Azure, p.ej. 580e250c-8f26-49d0-bee8-1c078add1609';
$strings['app_secret'] = 'Clave secreta de la aplicación';
$strings['force_logout'] = 'Botón de logout';
$strings['force_logout_help'] = 'Muestra un botón para hacer logout de Azure.';
$strings['block_name'] = 'Nombre del bloque';
$strings['management_login_enable'] = 'Login de gestión';
$strings['management_login_enable_help'] = 'Desactivar el login de Chamilo y activar una página de login alternativa para los usuarios de administración.<br>'
    .'Para ello, tendrá que copiar el archivo <code>/plugin/azure_active_directory/layout/login_form.tpl</code> en la carpeta <code>/main/template/overrides/layout/</code>.';
$strings['management_login_name'] = 'Nombre del bloque de login de gestión';
$strings['management_login_name_help'] = 'El nombre por defecto es "Login de gestión".';
$strings['existing_user_verification_order'] = 'Orden de verificación de usuario existente';
$strings['existing_user_verification_order_help'] = 'Este valor indica el orden en que el usuario serña buscado en Chamilo para verificar su existencia. '
    .'Por defecto es <code>1, 2, 3</code>.'
    .'<ol><li>EXTRA_FIELD_ORGANISATION_EMAIL (<code>mail</code>)</li><li>EXTRA_FIELD_AZURE_ID (<code>mailNickname</code>)</li><li>EXTRA_FIELD_AZURE_UID (<code>id</code> o <code>objectId</code>)</li></ol>';
$strings['OrganisationEmail'] = 'E-mail profesional';
$strings['AzureId'] = 'ID Azure (mailNickname)';
$strings['AzureUid'] = 'UID Azure (ID interno)';
$strings['ManagementLogin'] = 'Login de gestión';
$strings['InvalidId'] = 'Problema en el login - nombre de usuario o contraseña incorrecto. Errocode: AZMNF';
$strings['provisioning'] = 'Creación automatizada';
$strings['provisioning_help'] = 'Crear usuarios automáticamente (como alumnos) desde Azure si no existen en Chamilo todavía.';
$strings['update_users'] = 'Actualizar los usuarios';
$strings['update_users_help'] = 'Permite actualizar los datos del usuario al iniciar sesión.';
$strings['group_id_admin'] = 'ID de grupo administrador';
$strings['group_id_admin_help'] = 'El ID de grupo se encuentra en los detalles del grupo en Azure, y parece a: ae134eef-cbd4-4a32-ba99-49898a1314b6. Si deja este campo vacío, ningún usuario será creado como administrador.';
$strings['group_id_session_admin'] = 'ID de grupo admin de sesiones';
$strings['group_id_session_admin_help'] = 'El ID de grupo para administradores de sesiones. Si deja este campo vacío, ningún usuario será creado como administrador de sesiones.';
$strings['group_id_teacher'] = 'ID de grupo profesor';
$strings['group_id_teacher_help'] = 'El ID de grupo para profesores. Si deja este campo vacío, ningún usuario será creado como profesor.';
$strings['additional_interaction_required'] = 'Alguna interacción adicional es necesaria para identificarlo/a. Por favor conéctese primero a través de su <a href="https://login.microsoftonline.com" target="_blank">sistema de autenticación</a>, luego regrese aquí para logearse.';
$strings['tenant_id'] = 'Id. del inquilino';
$strings['tenant_id_help'] = 'Necesario para ejecutar scripts.';
$strings['deactivate_nonexisting_users'] = 'Desactivar usuarios no existentes';
$strings['deactivate_nonexisting_users_help'] = 'Compara los usuarios registrados en Chamilo con los de Azure y desactiva las cuentas en Chamilo que no existan en Azure.';
$strings['script_users_delta'] = 'Consula delta para usuarios';
$strings['script_users_delta_help'] = 'Obtiene usuarios recién creados, actualizados o eliminados sin tener que realizar una lectura completa de toda la colección de usuarios. De forma predeterminada, es <code>No</code>.';
$strings['script_usergroups_delta'] = 'Consulta delta para grupos de usuarios';
$strings['script_usergroups_delta_help'] = 'Obtiene grupos recién creados, actualizados o eliminados, incluidos los cambios de membresía del grupo, sin tener que realizar una lectura completa de toda la colección de grupos. De forma predeterminada, es <code>No</code>';
$strings['group_filter_regex'] = 'Group filter RegEx';
$strings['group_filter_regex_help'] = 'Expresión regular para filtrar grupos (solo las coincidencias serán sincronizadas), p.ej. <code>.*-FIL-.*</code> <code>.*-PAR-.*</code> <code>.*(FIL|PAR).*</code> <code>^(FIL|PAR).*</code>';
