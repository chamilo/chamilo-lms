CustomCertificate plugin
===============
Este plugin da la posibilidad al administrador de disponer de una herramienta de certificados alternativa
 a la que tiene por defecto la plataforma Chamilo.

**Instrucciones de puesta en funcionamiento**

- Subir la carpeta **customcertificate** a la carpeta plugin de chamilo.
- Habilitar el plugin en la administraci&oacute;n de Chamilo.
- Indicar 'menu_administrator' en la configuración de la región del plugin.

**accesos a la herramienta**

- Desde la pantalla de Administración para configurar el certificado por defecto.
- Desde las herramientas del curso, para la configuración del diploma especifico.

**Importante a tener en cuenta**

Por defecto los certificados utilizados serán los de la plataforma chamilo. Para habilitar el certificado alternativo
en un curso se debe entrar en la configuración del curso y habilitar en la pestaña de "certificados personalizado" la 
casilla de verificación de "Habilitar en el curso el certificado alternativo".
Si se desea usar el certificado por defecto se deberá mostrar la segunda casilla de verificación.

**Problema visualización icono de herramienta**

El icono de la herramienta aparecer&aacute; en pantalla de los cursos con el resto de herramientas
Si no se visualiza el icono en el cursos correctamente y sale el icono de plugin gen&eacute;rico:
- Copiar los iconos de la carpeta resources/img/64 dentro de /main/img/icons/64
- Copiar el icono de la carpeta resources/img/22 dentro de /main/img

Credits
-------
Contributed by [Nosolored](https://www.nosolored.com/).