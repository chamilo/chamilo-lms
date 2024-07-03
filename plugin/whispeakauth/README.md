Speech authentication with Whispeak
===================================

**Notice:**

This plugin requires the user to grant permission to use the microphone connected on the web browser. Currently,
browsers are limiting this permission to be used only in a secure environment with HTTPS. 
**If your portal does not work with HTTPS, then Whispeak authentication may not work.**

Installation:
-------------

*Prior to installing/uninstalling this plugin, you will need to make sure the src/Chamilo/PluginBundle/Entity folder is
temporarily writeable by the web server. This might imply a manual change on your server (outside of the Chamilo
interface).*

1. Install plugin in Chamilo.
2. Set the plugin configuration enabling the plugin and (optionally) set the max attempts. 
3. Set the `login_bottom` region to the plugin. 
4. Add `$_configuration['whispeak_auth_enabled'] = true;` to `configuration.php` file.
5. Optionally, you can add the `menu_administrator` region to se the user logged activities from Whispeak.

To have more information about whispeak or create an account to be able to use it on Chamilo you can go here <a href="https://whispeak.io/elearning/?source=chamilo">Whispeak</a>
