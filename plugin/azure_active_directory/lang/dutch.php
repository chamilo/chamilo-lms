<?php
/* For licensing terms, see /license.txt */
/**
 * Strings to Dutch L10n.
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 * @package chamilo.plugin.azure_active_directory
 */
$strings['plugin_title'] = 'Azure Active Directory';
$strings['plugin_comment'] = 'Sta authenticatie met Microsoft\'s Azure Active Directory toe';

$strings['enable'] = 'Inschakelen';
$strings['app_id'] = 'Applicatie ID';
$strings['app_id_help'] = 'Voeg de Applicatie Id toegewezen aan uw app bij de Azure portal, b.v. 580e250c-8f26-49d0-bee8-1c078add1609';
$strings['app_secret'] = 'Applicatie gehem';
$strings['force_logout'] = 'Forceer uitlogknop';
$strings['force_logout_help'] = 'Toon een knop om afmeldingssessie van Azure af te dwingen.';
$strings['block_name'] = 'Blok naam';
$strings['management_login_enable'] = 'Beheer login';
$strings['management_login_enable_help'] = 'Schakel de chamilo-login uit en schakel een alternatieve inlogpagina in voor gebruikers.<br>'
    .'U zult moeten kopiëren de <code>/plugin/azure_active_directory/layout/login_form.tpl</code> bestand in het <code>/main/template/overrides/layout/</code> dossier.';
$strings['management_login_name'] = 'Naam voor de beheeraanmelding';
$strings['management_login_name_help'] = 'De standaardinstelling is "Beheer login".';
$strings['existing_user_verification_order'] = 'Existing user verification order';
$strings['existing_user_verification_order_help'] = 'This value indicates the order in which the user will be searched in Chamilo to verify its existence. '
    .'By default is <code>1, 2, 3</code>.'
    .'<ol><li>EXTRA_FIELD_ORGANISATION_EMAIL (<code>mail</code>)</li><li>EXTRA_FIELD_AZURE_ID (<code>mailNickname</code>)</li><li>EXTRA_FIELD_AZURE_UID (<code>id</code> of <code>objectId</code>)</li></ol>';
$strings['OrganisationEmail'] = 'Organisatie e-mail';
$strings['AzureId'] = 'Azure ID (mailNickname)';
$strings['AzureUid'] = 'Azure UID (internal ID)';
$strings['ManagementLogin'] = 'Beheer Login';
$strings['InvalidId'] = 'Deze identificatie is niet geldig (verkeerde log-in of wachtwoord). Errocode: AZMNF';
$strings['provisioning'] = 'Geautomatiseerde inrichting';
$strings['update_users'] = 'Update users';
$strings['update_users_help'] = 'Allow user data to be updated at the start of the session.';
$strings['provisioning_help'] = 'Maak automatisch nieuwe gebruikers (als studenten) vanuit Azure wanneer ze niet in Chamilo zijn.';
$strings['group_id_admin'] = 'Groeps-ID voor platformbeheerders';
$strings['group_id_admin_help'] = 'De groeps-ID is te vinden in de details van de gebruikersgroep en ziet er ongeveer zo uit: ae134eef-cbd4-4a32-ba99-49898a1314b6. Indien leeg, wordt er automatisch geen gebruiker aangemaakt als admin.';
$strings['group_id_session_admin'] = 'Groeps-ID voor sessiebeheerders';
$strings['group_id_session_admin_help'] = 'De groeps-ID voor sessiebeheerders. Indien leeg, wordt er automatisch geen gebruiker aangemaakt als sessiebeheerder.';
$strings['group_id_teacher'] = 'Groeps-ID voor docenten';
$strings['group_id_teacher_help'] = 'De groeps-ID voor docenten. Indien leeg, wordt er automatisch geen gebruiker aangemaakt als docent.';
$strings['additional_interaction_required'] = 'Er is aanvullende interactie vereist om u te authenticeren. Log rechtstreeks in via <a href="https://login.microsoftonline.com" target="_blank">uw authenticatiesysteem</a> en kom dan terug naar deze pagina om in te loggen.';
$strings['tenant_id'] = 'Mandanten-ID';
$strings['tenant_id_help'] = 'Required to run scripts.';
$strings['deactivate_nonexisting_users'] = 'Deactivate non-existing users';
$strings['deactivate_nonexisting_users_help'] = 'Compare registered users in Chamilo with those in Azure and deactivate accounts in Chamilo that do not exist in Azure.';
$strings['script_users_delta'] = 'Delta query for users';
$strings['script_users_delta_help'] = 'Get newly created, updated, or deleted users without having to perform a full read of the entire user collection. By default, is <code>No</code>.';
$strings['script_usergroups_delta'] = 'Delta query for usergroups';
$strings['script_usergroups_delta_help'] = 'Get newly created, updated, or deleted groups, including group membership changes, without having to perform a full read of the entire group collection. By default, is <code>No</code>.';
$strings['group_filter_regex'] = 'Group filter RegEx';
$strings['group_filter_regex_help'] = 'Regular expression to filter groups (only matches will be synchronized), e.g. <code>.*-FIL-.*</code> <code>.*-PAR-.*</code> <code>.*(FIL|PAR).*</code> <code>^(FIL|PAR).*</code>';
