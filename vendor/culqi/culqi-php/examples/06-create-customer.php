<?php
/**
 * Ejemplo 6
 * Como crear un customer usando Culqi PHP.
 */

try {
  // Usando Composer (o puedes incluir las dependencias manualmente)
  require '../vendor/autoload.php';

  // Configurar tu API Key y autenticación
  $SECRET_KEY = "{SECRET KEY}";
  $culqi = new Culqi\Culqi(array('api_key' => $SECRET_KEY));

  // Creando Cargo a una tarjeta
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
  // Respuesta
  echo json_encode($customer);

} catch (Exception $e) {
  echo json_encode($e->getMessage());
}
