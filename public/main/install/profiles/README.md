Installation profiles repository
================================

This folder contains installation profiles, that will pre-configure an
installation process so no post-installation configuration is necessary if you
already know what settings you want.

The files are configured in JSON format and, at this time, will not be openly
provided through the web installer interface. To install using one of these
profiles, you will have to add a parameter to the URL manually.

In step 1 of the installer, reload the page adding profile=[profile-name] at the
end of the URL (where profile-name is the name of the file without the ".json"
extension), like so (on the language selection page):
```
http://example.com/main/install/index.php?profile=hr
```

Also, the php-json extension has to be enabled.