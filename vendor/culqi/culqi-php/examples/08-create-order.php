<?php
/**
 * Ejemplo 8
 * Como crear una orden usando Culqi PHP.
 */

try {
  // Usando Composer (o puedes incluir las dependencias manualmente)
  require '../vendor/autoload.php';

  // Configurar tu API Key y autenticación
  $SECRET_KEY = "{SECRET KEY}";
  $culqi = new Culqi\Culqi(array('api_key' => $SECRET_KEY));

  // Creando Cargo a una tarjeta
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
        "expiration_date" => time() + 24*60*60,   // Orden con un dia de validez
        "metadata" => array("dni" => "71702935")
      )
  );
  // Respuesta
  echo json_encode($order);

} catch (Exception $e) {
  echo json_encode($e->getMessage());
}
