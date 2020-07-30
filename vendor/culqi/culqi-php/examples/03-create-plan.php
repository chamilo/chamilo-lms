<?php
/**
 * Ejemplo 3
 * Como crear un plan usando Culqi PHP.
 */

try {
  // Usando Composer (o puedes incluir las dependencias manualmente)
  require '../vendor/autoload.php';

  // Configurar tu API Key y autenticación
  $SECRET_KEY = "{SECRET KEY}";
  $culqi = new Culqi\Culqi(array('api_key' => $SECRET_KEY));

  // Creando Cargo a una tarjeta
  $plan = $culqi->Plans->create(
      array(
        "amount" => 10000,
        "currency_code" => "PEN",
        "interval" => "months",
        "interval_count" => 1,
        "limit" => 12,
        "name" => "Plan de Prueba ".uniqid(),
        "trial_days" => 15
      )
  );
  // Respuesta
  echo json_encode($plan);

} catch (Exception $e) {
  echo json_encode($e->getMessage());
}
