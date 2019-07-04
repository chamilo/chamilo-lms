Speech authentication with Whispeak
===================================

Instructions:
-------------

1. Install plugin in Chamilo.
2. Create one file called `tokenTest` in `/path/to/chamilo/plugin/whispeak/`
folder with your JSON Web Token provided by Whispeak.
```shell
echo "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c" > /path/to/chamilo/plugin/whispeak/tokenTest
``` 
3. Set the plugin configuration enabling the plugin and (optionally) set the max attempts. 
4. Set the `login_bottom` region to the plugin. 
5. Add `$_configuration['whispeak_auth_enabled'] = true;` to `configuration.php` file.
