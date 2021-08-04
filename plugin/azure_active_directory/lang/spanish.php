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
$strings['OrganisationEmail'] = 'E-mail profesional';
$strings['AzureId'] = 'ID Azure (mailNickname)';
$strings['ManagementLogin'] = 'Login de gestión';
$strings['InvalidId'] = 'Problema en el login - nombre de usuario o contraseña incorrecto. Errocode: AZMNF';
$strings['provisioning'] = 'Creación automatizada';
$strings['provisioning_help'] = 'Crear usuarios automáticamente (como alumnos) desde Azure si no existen en Chamilo todavía.';
$strings['group_id_admin'] = 'ID de grupo administrador';
$strings['group_id_admin_help'] = 'El ID de grupo se encuentra en los detalles del grupo en Azure, y parece a: ae134eef-cbd4-4a32-ba99-49898a1314b6. Si deja este campo vacío, ningún usuario será creado como administrador.';
$strings['group_id_session_admin'] = 'ID de grupo admin de sesiones';
$strings['group_id_session_admin_help'] = 'El ID de grupo para administradores de sesiones. Si deja este campo vacío, ningún usuario será creado como administrador de sesiones.';
$strings['group_id_teacher'] = 'ID de grupo profesor';
$strings['group_id_teacher_help'] = 'El ID de grupo para profesores. Si deja este campo vacío, ningún usuario será creado como profesor.';
