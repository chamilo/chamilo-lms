# Culqi PHP

[![Latest Stable Version](https://poser.pugx.org/culqi/culqi-php/v/stable)](https://packagist.org/packages/culqi/culqi-php)
[![Total Downloads](https://poser.pugx.org/culqi/culqi-php/downloads)](https://packagist.org/packages/culqi/culqi-php)
[![License](https://poser.pugx.org/culqi/culqi-php/license)](https://packagist.org/packages/culqi/culqi-php)

Biblioteca PHP oficial de CULQI, pagos simples en tu sitio web.


Esta biblioteca trabaja con la [v2.0](https://culqi.com/api/) de Culqi API.


## Requisitos

* PHP 5.3 o superiores.
* Credenciales de comercio Culqi (1).

(1) Debes registrarte [aquí](https://integ-panel.culqi.com/#/registro). Luego, crear un comercio y estando en el panel, acceder a Desarrollo > [***API Keys***](https://integ-panel.culqi.com/#/panel/comercio/desarrollo/llaves).

![alt tag](http://i.imgur.com/NhE6mS9.png)

## Instalación

### Vía Composer
```json
{
  "require": {
    "culqi/culqi-php": "1.3.4"
  }
}
```

Y cargar todo usando el autoloader de Composer.

```php
require 'vendor/autoload.php';
```

### Manualmente

Clonarse el repositorio o bajarse el código fuente

```bash
git clone git@github.com:culqi/culqi-php.git
```

Ahora, incluir en la cabecera a `culqi-php` y también la dependencia [`Requests`](https://github.com/rmccue/requests). Debes hacer el llamado correctamente a la carpeta y/o archivo dependiendo de tu estructura.

```php
<?php
// Cargamos Requests y Culqi PHP
include_once dirname(__FILE__).'/libraries/Requests/library/Requests.php';
Requests::register_autoloader();
include_once dirname(__FILE__).'/libraries/culqi-php/lib/culqi.php';
```

## Modo de uso

En todos ejemplos, inicialmente hay que configurar la credencial `$API_KEY `

```php
// Configurar tu API Key y autenticación
$SECRET_KEY = "vk9Xjpe2YZMEOSBzEwiRcPDibnx2NlPBYsusKbDobAk";
$culqi = new Culqi\Culqi(array('api_key' => $SECRET_KEY));
```

### Crear un token (Usarlo SOLO en DESARROLLO)

Antes de crear un Cargo, Plan o un Suscriptor es necesario crear un `token` de tarjeta. Dentro de esta librería se encuentra una funcionalidad para generar 'tokens', pero solo
debe ser usada para **desarrollo**. Lo recomendable es generar los 'tokens' con **CULQI.JS** cuando pases a producción, **debido a que es muy importante que los datos de tarjeta sean enviados desde el dispositivo de tus clientes directamente a los servidores de Culqi**, para no poner en riesgo información sensible.


### Crear un cargo (Cargos)
Crear un cargo significa cobrar una venta a una tarjeta. Para esto previamente
deberías obtener el  `token` que refiera a la tarjeta de tu cliente.

```php
// Creamos Cargo a una tarjeta
$charge = $culqi->Charges->create(
    array(
      "amount" => 1000,
      "capture" => true,
      "currency_code" => "PEN",
      "description" => "Venta de prueba",
      "email" => "test@culqi.com",
      "installments" => 0,
      "antifraud_details" => array(
          "address" => "Av. Lima 123",
          "address_city" => "LIMA",
          "country_code" => "PE",
          "first_name" => "Will",
          "last_name" => "Muro",
          "phone_number" => "9889678986",
      ),
      "source_id" => "{token_id o card_id}"
    )
);

//Respuesta
print_r($charge);
```
### Crear un Plan
```php
$plan = $culqi->Plans->create(
  array(
    "alias" => "plan-culqi".uniqid(),
    "amount" => 10000,
    "currency_code" => "PEN",
    "interval" => "dias",
    "interval_count" => 1,
    "limit" => 12,
    "name" => "Plan de Prueba ".uniqid(),
    "trial_days" => 15
  )
);

//Respuesta
print_r($plan);
```

### Crear un Customer
```php
$customer = $culqi->Customers->create(
  array(
    "address" => "av lima 123",
    "address_city" => "lima",
    "country_code" => "PE",
    "email" => "www@".uniqid()."me.com",
    "first_name" => "Will",
    "last_name" => "Muro",
    "metadata" => array("test"=>"test"),
    "phone_number" => 899898999
  )
);
print_r($customer);
```

### Crear un Card
```php
$card = $culqi->Cards->create(
  array(
    "customer_id" => "{customer_id}",
    "token_id" => "{token_id}"
  )
);
print_r($card);
```

### Crear un Suscripción a un plan
```php
// Creando Suscriptor a un plan
$subscription = $culqi->Subscriptions->create(
  array(
    "card_id" => "{card_id}",
    "plan_id" => "{plan_id}"
  )
);

//Respuesta
print_r($subscription);
```

### Crear un Order 

[Ver ejemplo completo](/examples/08-create-order.php)

```php
// Creando orden (con 1 dia de duracion)
$order = $culqi->Orders->create(
      array(
        "amount" => 1000,
        "currency_code" => "PEN",
        "description" => 'Venta de prueba',        
        "order_number" => 'pedido-9999',  
        "client_details" => array( 
            "first_name"=> "Brayan", 
            "last_name" => "Cruces",
            "email" => "micorreo@gmail.com", 
            "phone_number" => "51945145222"
         ),
        "expiration_date" => time() + 24*60*60   // Orden con un dia de validez
      )
);
print_r($order);
```

## Probar ejemplos
```bash
git clone https://github.com/culqi/culqi-php.git
composer install
cd culqi-php/examples
php -S 0.0.0.0:8000
```

## Documentación
¿Necesitas más información para integrar `culqi-php`? La documentación completa se encuentra en [https://culqi.com/docs/](https://culqi.com/docs/)


## Tests

```bash
composer install
phpunit --verbose --tap tests/*
```
## Licencia

Licencia MIT. Revisar el LICENSE.md.
