<?php

require_once('TestAutoLoad.php');

use Culqi\Culqi;

/**
 *  Test Patch
 */
class TestPatch extends PHPUnit_Framework_TestCase
{
    protected $API_KEY;

    protected function setUp() {
        $this->API_KEY = getenv("API_KEY");
        $this->culqi = new Culqi(array("api_key" => $this->API_KEY ));
    }

    public function testUpdatePlan() {
        $plan = $this->culqi->Plans->update("pln_test_pLFzcWkwj33xFGF1",
            array(
                "metadata" => array(
                    "test" => "test555"
                )
            )
        );
        $this->assertEquals('plan', $plan->object);
    }
}
