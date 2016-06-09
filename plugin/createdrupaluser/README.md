Create Drupal user plugin
=========================

This plugin creates a user on a Drupal portal when a user is registered in 
Chamilo.

This uses the Hook mechanism available to Chamilo plugins: when enabling this 
plugin, the HookCreateDrupalUser is automatically added to the hooks stack, and
the UserManager::create_user() method calls the HookCreateUser hook and notifies
it, resulting in the plugin code to be executed.

The Drupal portal settings must be configured in the configuration panel for the
plugin. A SOAP call is then initiated inside the plugin code, that will use the
Drupal's addUser() web service. See src/HookCreateDrupalUser.php for more info
on the call parameters.

After calling the web service and receiving a positive answer, Chamilo stores
the remote (Drupal) user ID inside the extra_field_values table, as field name
"drupal_user_id". This later serves for updates and other synchronisation
purposes.

Extending
---------

Other plugins could easily be created by copying this one and modifying the
class names and web services to call. Simply review every variable coined with
the "drupal" name and update according to your own portal.

