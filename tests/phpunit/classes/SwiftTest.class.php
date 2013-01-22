<?php
/*
use Silex\WebTestCase;

class SwiftTest extends WebTestCase
{
    public function createApplication()
    {
        $app = require dirname(__FILE__).'/../../../main/inc/global.inc.php';
        //$app["swiftmailer.transport"] = new \Swift_Transport_NullTransport($app['swiftmailer.transport.eventdispatcher']);
        //$app['mailer.logger'] = new Logger\MessageLogger();
        $app['mailer']->registerPlugin($app['monolog']);
        return $app;
    }

    public function testSend()
    {
       $client = $this->createClient();
       $crawler = $client->request('POST', '/', array("somedata"=>"to_send"));
       $this->assertEquals(1, $this->app['mailer.logger']->countMessages(), "Only one email sent");

       $emails = $this->app['mailer.logger']->getMessages();
       $this->assertEquals("This is my subject", $emails[0]->getSubject(), "Subject is correct");
    }
}*/