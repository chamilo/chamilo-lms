<?php

require_once('TestAutoLoad.php');

use Culqi\Culqi;

class DeleteTest extends PHPUnit_Framework_TestCase
{

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

    public function createCard() {
        $card = $this->culqi->Cards->create(
            array(
                "customer_id" => $this->createCustomer()->id,
                "token_id" => $this->createToken()->id
            )
        );
        return $card;
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

    public function createSubscription() {
        $subscription = $this->culqi->Subscriptions->create(
            array(
                "card_id" => $this->createCard()->id,
                "plan_id" => $this->createPlan()->id
            )
        );
        return $subscription;
    }

    public function testDeleteSubscription() {
        $subscriptionDeleted = $this->culqi->Subscriptions->delete($this->createSubscription()->id);
        $this->assertTrue($subscriptionDeleted->deleted);
    }

    public function testDeletePlan() {
        $planDeleted = $this->culqi->Plans->delete($this->createPlan()->id);
        $this->assertTrue($planDeleted->deleted);
    }

    public function testDeleteCard() {
        $cardDeleted = $this->culqi->Cards->delete($this->createCard()->id);
        $this->assertTrue($cardDeleted->deleted);
    }

    public function testDeleteCustomer() {
        $customerDeleted = $this->culqi->Customers->delete($this->createCustomer()->id);
        $this->assertTrue($customerDeleted->deleted);
    }

}