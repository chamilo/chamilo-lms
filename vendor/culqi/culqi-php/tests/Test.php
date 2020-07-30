<?php

require_once('TestAutoLoad.php');

use Culqi\Culqi;

/**
 *  Test Create
 */
class Test extends PHPUnit_Framework_TestCase {

  protected $API_KEY;
  protected $PUBLIC_API_KEY;

  protected function setUp() {
    $this->PUBLIC_API_KEY = getenv("PUBLIC_API_KEY");
    $this->API_KEY =  getenv("API_KEY");
    $this->culqi_token = new Culqi(array("api_key" => $this->PUBLIC_API_KEY ));
    $this->culqi = new Culqi(array("api_key" => $this->API_KEY ));
  }

  public function testValidIins() {
    $iin = $this->culqi_token->Iins->get("411111");
    $this->assertEquals('iin', $iin->object);
  }

  /**
   * Creación de un token con los datos de una tarjeta de prueba
   */
  protected function createToken() {
    $token = $this->culqi_token->Tokens->create(
        array(
          "card_number" => "4111111111111111",
          "cvv" => "123",
          "email" => "wmuro".uniqid()."@me.com",
          "expiration_month" => 9,
          "expiration_year" => 2020,
          "fingerprint" => "q352454534"
        )
    );
    return $token;
  }

  /**
  * Verificar creación de Token
  */
  public function testVerifyToken() {
   $this->assertEquals('token', $this->createToken()->object);
  }

  public function testFindToken() {
    $token = $this->culqi->Tokens->get($this->createToken()->id);
    $this->assertEquals('token', $token->object);
  }

  public function createCharge() {
    $charge = $this->culqi->Charges->create(
      array(
        "amount" => 1000,
        "capture" => true,
        "currency_code" => "PEN",
        "description" => "Venta de prueba",
        "email" => "test@culqi.com",
        "installments" => 0,
        "source_id" => $this->createToken()->id
      )
    );
    return $charge;
  }

  public function testCreateCharge() {
    // Verificacion del campo object no tenga el valor 'error'
    $this->assertEquals('charge', $this->createCharge()->object);
  }

  public function testFindCharge() {
    $charge = $this->culqi->Charges->get($this->createCharge()->id);
    $this->assertEquals('charge', $charge->object);
  }

  public function createPlan() {
    $plan = $this->culqi->Plans->create(
      array(
        "amount" => 10000,
        "currency_code" => "PEN",
        "interval" => "dias",
        "interval_count" => 1,
        "limit" => 12,
        "name" => "plan-culqi".uniqid(),
        "trial_days" => 15
      )
    );
    return $plan;
  }

  public function testCreatePlan() {
    // Verificacion del campo object no tenga el valor 'error'
    $this->assertEquals('plan', $this->createPlan()->object);
  }

  public function testFindPlan() {
    $plan = $this->culqi->Plans->get($this->createPlan()->id);
    $this->assertEquals('plan', $plan->object);
  }

  public function createCustomer() {
    $customer = $this->culqi->Customers->create(
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
    return $customer;
  }

  public function testCreateCustomer() {
    $this->assertEquals('customer', $this->createCustomer()->object);
  }

  public function testFindCustomer() {
    $customer = $this->culqi->Customers->get($this->createCustomer()->id);
    $this->assertEquals('customer', $customer->object);
  }

  public function createCard() {
    $card = $this->culqi->Cards->create(
      array(
        "customer_id" => $this->createCustomer()->id,
        "token_id" => $this->createToken()->id
      )
    );
    return $card;
  }

  public function testCreateCard() {
    $this->assertEquals('card', $this->createCard()->object);
  }

  public function testFindCard() {
    $card = $this->culqi->Cards->get($this->createCard()->id);
    $this->assertEquals('card', $card->object);
  }

  public function createSubscription() {
      $subscription = $this->culqi->Subscriptions->create(
          array(
              "card_id" => $this->createCard()->id,
              "plan_id" => $this->createPlan()->id
          )
      );
      return $subscription;
  }

  public function testCreateSubscription() {
    $this->assertEquals('subscription',$this->createSubscription()->object);
  }

  public function testFindSubscription() {
    $subscription = $this->culqi->Subscriptions->get($this->createSubscription()->id);
    $this->assertEquals('subscription', $subscription->object);
  }

  public function createRefund() {
    $refund = $this->culqi->Refunds->create(
      array(
        "amount" => 500,
        "charge_id" => $this->createCharge()->id,
        "reason" => "solicitud_comprador"
      )
    );
    return $refund;
  }

  public function testCreateRefund() {
    $this->assertEquals('refund',$this->createRefund()->object);
  }

  public function testFindRefund() {
    $refund = $this->culqi->Refunds->get($this->createRefund()->id);
    $this->assertEquals('refund',$refund->object);
  }

}
