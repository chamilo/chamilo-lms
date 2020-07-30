<?php

require_once('TestAutoLoad.php');

use Culqi\Culqi;

class CaptureTest extends PHPUnit_Framework_TestCase {

    protected $API_KEY;
    protected $PUBLIC_API_KEY;

    protected function setUp() {
        $this->PUBLIC_API_KEY = getenv("PUBLIC_API_KEY");
        $this->API_KEY =  getenv("API_KEY");
        $this->culqi_token = new Culqi(array("api_key" => $this->PUBLIC_API_KEY ));
        $this->culqi = new Culqi(array("api_key" => $this->API_KEY ));
    }

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

    public function testVerifyToken() {
        $this->assertEquals('token', $this->createToken()->object);
    }

    public function createCharge() {
        $charge = $this->culqi->Charges->create(
            array(
                "amount" => 1000,
                "capture" => false,
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
        $this->assertEquals('charge', $this->createCharge()->object);
    }

    public function testCaptureCharge() {
        $captureCharge = $this->culqi->Charges->capture($this->createCharge()->id);
        $this->assertEquals('charge', $captureCharge->object);
    }

}