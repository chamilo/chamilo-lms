<?php

require_once('TestAutoLoad.php');

use Culqi\Culqi;

class ListTest extends PHPUnit_Framework_TestCase {

    protected $API_KEY;

    protected function setUp() {
        $this->API_KEY = getenv("API_KEY");
        $this->culqi = new Culqi(array("api_key" => $this->API_KEY ));
    }

    public function testListTokens() {
        $tokens = $this->culqi->Tokens->all(array("limit" => 50));
        $valid = false;
        if(count($tokens->data) >= 0) {
            $valid = true;
        }
        $this->assertTrue($valid);
    }

    public function testListCharges() {
        $charges = $this->culqi->Charges->all(array("min_amount" => 1000, "max_amount" => 1000000, "limit" => 50));
        $valid = false;
        if(count($charges->data) >= 0) {
            $valid = true;
        }
        $this->assertTrue($valid);
    }

    public function testListPlans() {
        $plans = $this->culqi->Plans->all(array("limit" => 50));
        $valid = false;
        if(count($plans->data) >= 0) {
            $valid = true;
        }
        $this->assertTrue($valid);
    }

    public function testListCustomers() {
        $customers = $this->culqi->Customers->all(array("limit" => 50));
        $valid = false;
        if(count($customers->data) >= 0) {
            $valid = true;
        }
        $this->assertTrue($valid);
    }

    public function testListCards() {
        $cards = $this->culqi->Cards->all(array("limit" => 50));
        $valid = false;
        if(count($cards->data) >= 0) {
            $valid = true;
        }
        $this->assertTrue($valid);
    }

    public function testListSubscriptions() {
        $subscriptions = $this->culqi->Subscriptions->all(array("limit" => 50));
        $valid = false;
        if(count($subscriptions->data) >= 0) {
            $valid = true;
        }
        $this->assertTrue($valid);
    }

    public function testListRefunds() {
        $refunds = $this->culqi->Refunds->all(array("limit" => 50));
        $valid = false;
        if(count($refunds->data) >= 0) {
            $valid = true;
        }
        $this->assertTrue($valid);
    }

    public function testListTransfers() {
        $transfers = $this->culqi->Transfers->all(array("limit" => 50));
        $valid = false;
        if(count($transfers->data) >= 0) {
            $valid = true;
        }
        $this->assertTrue($valid);
    }

}