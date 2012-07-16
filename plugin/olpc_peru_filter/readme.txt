== English ==

This plugin will create administration settings to configure the content 
filtering on OLPC Peru's XS servers (uses Squid).

In order to test this plugin, you should have a working version of the Squid
proxy system as well as a series of directories and files. Here is an example
on how to generate a fake structure that will work with the default plugin
config (it is *not* a secure way to do it, though, so don't use in production):
sudo mkdir /var/sqg
sudo mkdir /var/squidGuard
sudo mkdir /var/squidGuard/blacklists
sudo mkdir /var/squidGuard/blacklists/Games
sudo touch /var/sqg/blacklists
sudo chmod -R 0777 /var/sqg
sudo chmod -R 0777 /var/squidGuard/blacklists

After that, enable the plugin, then go to some course's config screen and
check/uncheck the "Games" option. Now check that it updated the 
/var/sqg/blacklists file... That's all folks!

The blacklists in /var/squidGuard/blacklists/ can be downloaded from 
http://dsi.ut-capitole.fr/blacklists/index_en.php

The SquidGuard software documentation can be found here: 
http://www.squidguard.org/Doc/

The right way to install the filtering system (which should already be done
on the XS servers in Peru) is to install and configure Squid (yum install
squid) and add SquidGuard (see URL above), then finally change the permissions
on the configuration files as in the two last command lines above (with chmod)

== Spanish ==

Este plugin crea parámetros de administración para configurar el filtrado de 
contenido en los servidores XS del proyecto Una Laptop Para Cada Niño en 
Perú (usando Squid).

Para probar este plugin, debería tener una versión funcional del sistema de
proxy Squid y una serie de carpetas y ficheros. Le planteamos aquí un ejemplo
de como generar una estructura de simulación que funcionará con el plugin
predeterminado (*no* es una forma segura de hacerlo, así que por favor solo
usar en máquinas de desarrollo):
sudo mkdir /var/sqg
sudo mkdir /var/squidGuard
sudo mkdir /var/squidGuard/blacklists
sudo mkdir /var/squidGuard/blacklists/Games
sudo touch /var/sqg/blacklists
sudo chmod -R 0777 /var/sqg
sudo chmod -R 0777 /var/squidGuard/blacklists

Después de esto, activar el plugin e ir en alguna pantalla de configuración
de curso y marcar/desmarcar la opción "Games". Ahora verifique que se ha 
actualizado el archivo /var/sqg/blacklists... Ya está!

Las listas negras (de exclusión) dentro de /var/squidGard/blacklists/ pueden
ser descargadas desde http://dsi.ut-capitole.fr/blacklists/index_en.php

La documentación del software SquidGuard puede ser encontrada aquí:
http://www.squidguard.org/Doc/

La forma correcta de instalar el sistema de filtrado (esto debería estar ya
preconfigurado en los servidores XS de Perú) es de instalar y configurar
Squid (yum install squid) y agregarle SquidGuard, y finalmente permitir
cambios de configuración a los archivos de configuración (ver dos últimas 
líneas de comando, con chmod).
