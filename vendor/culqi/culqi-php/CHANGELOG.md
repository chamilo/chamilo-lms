### 1.3.4 10-10-2018
* Se agrega recurso de Orders

### 1.3.3 17-03-2017
* Actualización de composer.json


### 1.3.2 15-03-2017
* Se cambia el metodo getCapture de Cargo por capture
* Corección en el composer.json. 

### 1.3.1 16-02-2017
* Se corrige el metodo DELETE
* Se cambia el nombre del metodo "getList" por "all"
* Se corrige Transfers
* Se agrega el metodo update a Tokens

### 1.3.0 16-02-2017
* Se utiliza API v2.0
* Cambios en Cargos.php a Charges.php
* Cambios en Client.php
* Cambios en Culqi.php
* Cambios en Devoluciones.php a Refunds.php
* Cambios en Planes.php a Plan.php
* Cambios en Suscripciones.php a Subscriptions.php
* Se agrega Cards.php
* Se agrega Events.php
* Se agrega Iins.php
* Se agrega Customers.php
* Cambios en Tokens.php
* Cambios en /examples/
* Cambios en /tests/

### 1.2.5 16-11-2016
* Cambios en Client.php
* Cambios en /examples/

### 1.2.4 03-11-2016
* Ejemplo de Planes añadido.

### 1.2.3 08-10-2016
* Cambio en el timeout de la conexión a 120 segundos.

### 1.2.2 23-09-2016
* Cambios en los ejemplos.
* Nuevo método: setEnv() para definir entorno.

### 1.2.1 05-09-2016
* Añadidos ejemplos de "Crear Cargo" y "Crear Suscripción".
* Correcciones menores en el composer.json.

### 1.2.0 31-08-2016
* Conexión con la API v1.2.
* Reescritura completa de la biblioteca.
* Ya no usa cURL, gracias a la dependencia "Requests".

### 1.1.1 26-07-2016
* Desofuscación de la librería.
* Nuevos ejemplos
* Pequeños fixes en las rutas de los ejemplos.

### 1.1.0 15-10-2015

* Actualización de las rutas del nuevo API(v1) de Culqi.
* Nuevos parámetros de envío para la creación de una venta.


### 1.0.1 10-09-2015

* Segunda versión de la librería, mejoras en las validaciones.


### 1.0.0 17-08-2015

* Primera versión de la librería, soporta las operaciones de Anulación, Consulta, Autorización.
