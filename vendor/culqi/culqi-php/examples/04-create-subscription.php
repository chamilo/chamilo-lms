|<?php
/**
 * Ejemplo 4
 * Como añadir un suscriptor a un plan específico usando Culqi PHP.
 */

try {
  // Usando Composer
  require '../vendor/autoload.php';

  // Configurar tu API Key y autenticación
  $SECRET_KEY = "{SECRET KEY}";
  $culqi = new Culqi\Culqi(array('api_key' => $SECRET_KEY));

  // Creando Cargo a una tarjeta
  $subscription = $culqi->Subscriptions->create(
    array(
        "card_id"=> "{card_id}",
        "plan_id" => "{plan_id}"
    )
  );

  // Respuesta
  echo json_encode($subscription);

} catch (Exception $e) {
  echo json_encode($e->getMessage());
}
