<?php
/**
 * Ejemplo 5
 * Como crear una devolution usando Culqi PHP.
 */

try {
  // Usando Composer (o puedes incluir las dependencias manualmente)
  require '../vendor/autoload.php';

  // Configurar tu API Key y autenticación
  $SECRET_KEY = "{SECRET KEY}";
  $culqi = new Culqi\Culqi(array('api_key' => $SECRET_KEY));

  // Creando Cargo a una tarjeta
  $refund = $culqi->Refunds->create(
      array(
        "amount" => 500,
        "charge_id" => "{charge_id}",
        "reason" => "bought an incorrect product"
      )
  );
  // Respuesta
  echo json_encode($refund);

} catch (Exception $e) {
  echo json_encode($e->getMessage());
}
