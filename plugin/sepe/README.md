SEPE plugin
===

This plugin is specific to the Spanish context. As such, most of its documentation and code might include Spanish language.

Plugin que conecta el SEPE con la plataforma de formación Chamilo
---

*Integra*:

- Conexiones SOAP
- Formularios para editar datos

*Instrucciones*:

- Instalar plugin
- En configuración del plugin: Habilitar Sepe -> SI -> Guardar
- Seleccionar una región del plugin -> menu_administrator
- Crear un usuario llamado SEPE con perfil de recursos humanos.
- Ir al menú del plugin Sepe (en la sección de plugin activos en administración) y seleccionar el link de "Configuración" -> Generar API key. Usar esta clave para realizar pruebas con el SOAP.
- En el fichero <em>/plugin/sepe/ws/ProveedorCentroTFWS.wsdl</em> modificar la linea 910 para indicar el dominio de la plataforma.

*Composer*:

- Es necesario incluir en el fichero <i>composer.json</i> en el apartado de "require" bajo la linea <em>"zendframework/zend-config": "2.3.3",</em> insertar <em>"zendframework/zend-soap": "2.*",</em>
- A continuación habrá que actualizar desde la linea de comandos el directorio vendor, usando la orden 'composer update'

*Verificación del WebService*:

- Para verificar que el webservice está activo, habrá que entrar desde un navegador web a la siguiente dirección:
<em>http://dominioquecorresponda/plugin/sepe/ws/service.php</em>


Icons made by <a href="http://www.flaticon.com/authors/freepik" title="Freepik">Freepik</a> from <a href="http://www.flaticon.com" title="Flaticon">www.flaticon.com</a>             is licensed by <a href="http://creativecommons.org/licenses/by/3.0/" title="Creative Commons BY 3.0">CC BY 3.0</a>

