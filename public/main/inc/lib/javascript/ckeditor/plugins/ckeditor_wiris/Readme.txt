To activate Wiris Editor for CkEditor you need to 
Download the Plugin from here :
http://www.wiris.com/es/plugins3/ckeditor/download
and place the resulting folder inside
main/inc/lib/javascript/ckeditor/plugins/

To configure the plugin you need to assign read and write permissions to the cache and formulas directories inside the plugin or rename the configuration.ini.dist to configuration.ini and uncomment this lines if you want to change the place where stored the formula images :

#wiriscachedirectory = /var/cache
#wirisformuladirectory = /var/formulas
