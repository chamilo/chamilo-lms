<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\SSHKeys;

use DigitalOcean\Tests\TestCase;
use DigitalOcean\SSHKeys\SSHKeys;
use DigitalOcean\SSHKeys\SSHKeysActions;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class SSHKeysTest extends TestCase
{
    protected $sshKeyId;
    protected $sshKeys;
    protected $sshKeysBuildQueryMethod;

    protected function setUp()
    {
        $this->sshKeyId = 123;

        $this->sshKeys = new SSHKeys($this->getMockCredentials(), $this->getMockAdapter($this->never()));
        $this->sshKeysBuildQueryMethod = new \ReflectionMethod(
            $this->sshKeys, 'buildQuery'
        );
        $this->sshKeysBuildQueryMethod->setAccessible(true);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMEssage Impossible to process this query: https://api.digitalocean.com/droplets/?client_id=foo&api_key=bar
     */
    public function testProcessQuery()
    {
        $sshKeys = new SSHKeys($this->getMockCredentials(), $this->getMockAdapterReturns(null));
        $sshKeys->getAll();
    }

    public function testGetAllUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/ssh_keys/?client_id=foo&api_key=bar',
            $this->sshKeysBuildQueryMethod->invoke($this->sshKeys)
        );
    }

    public function testGetAll()
    {
        $response = <<<JSON
{"status":"OK","ssh_keys":[{"id":10,"name":"office-imac"},{"id":11,"name":"macbook-air"}]}
JSON
        ;

        $sshKeys = new SSHKeys($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $sshKeys = $sshKeys->getAll();

        $this->assertTrue(is_object($sshKeys));
        $this->assertEquals('OK', $sshKeys->status);
        $this->assertCount(2, $sshKeys->ssh_keys);

        $key1 = $sshKeys->ssh_keys[0];
        $this->assertSame(10, $key1->id);
        $this->assertSame('office-imac', $key1->name);

        $key2 = $sshKeys->ssh_keys[1];
        $this->assertSame(11, $key2->id);
        $this->assertSame('macbook-air', $key2->name);
    }

    public function testShowUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/ssh_keys/123/?client_id=foo&api_key=bar',
            $this->sshKeysBuildQueryMethod->invoke($this->sshKeys, $this->sshKeyId)
        );
    }

    public function testShow()
    {
        $response = <<<JSON
{"status":"OK","ssh_key":{"id":10,"name":"office-imac","ssh_pub_key":"ssh-dss AHJASDBVY6723bgBVhusadkih238723kjLKFnbkjGFklaslkhfgBAFFHGBJbju8)H3hnNGjASGFkjgZn86ZCqk02NX3BTcMV4YI2I4/sebg8VnuebDn0XUbbmVrAq4YqGiobn86ZCqk02NX3BTcMp4QGmyL4/sebg8Vnusytv93cA2PsXOxvbU0CdebDn0XUbbmVrAq4YqGiob48KzCT/NT6L6VoD5n+jSZvQAAAIAspspAelh4bW5ncO5+CedFZPZn86ZCqk02NX3BTcMV4YIaSCO43Y+ghI2of4+E1TDJ1R9Znk9XJsald/U0u0uXwtyHXP2sommNWuAGtzp4QGmyL4/sebg8Vnusytv93cA2PsXOxvbU0CdebDn0XUbbmVrAq4YqGiob48KzCT/NT6L6VoD5n+jSZflFD684gdLsW1+gjVoFBk0MZWuGSXEQyIwlBRq/8jAAAAFQDrxI/h35BewJUmVjid8Qk1NprMvQAAAIAspspAelh4bW5ncO5+CedFZPZn86ZCqk02NX3BTcMV4YI2IEzb6R2vzZkjCTuZVy6dcH3ag6JlEfju67euWT5yMnT1I0Ow== me@office-imac"}}
JSON
        ;

        $sshKeys = new SSHKeys($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $sshKey  = $sshKeys->show($this->sshKeyId);

        $this->assertTrue(is_object($sshKey));
        $this->assertEquals('OK', $sshKey->status);

        $this->assertSame(10, $sshKey->ssh_key->id);
        $this->assertSame('office-imac', $sshKey->ssh_key->name);
        $this->assertSame('ssh-dss AHJASDBVY6723bgBVhusadkih238723kjLKFnbkjGFklaslkhfgBAFFHGBJbju8)H3hnNGjASGFkjgZn86ZCqk02NX3BTcMV4YI2I4/sebg8VnuebDn0XUbbmVrAq4YqGiobn86ZCqk02NX3BTcMp4QGmyL4/sebg8Vnusytv93cA2PsXOxvbU0CdebDn0XUbbmVrAq4YqGiob48KzCT/NT6L6VoD5n+jSZvQAAAIAspspAelh4bW5ncO5+CedFZPZn86ZCqk02NX3BTcMV4YIaSCO43Y+ghI2of4+E1TDJ1R9Znk9XJsald/U0u0uXwtyHXP2sommNWuAGtzp4QGmyL4/sebg8Vnusytv93cA2PsXOxvbU0CdebDn0XUbbmVrAq4YqGiob48KzCT/NT6L6VoD5n+jSZflFD684gdLsW1+gjVoFBk0MZWuGSXEQyIwlBRq/8jAAAAFQDrxI/h35BewJUmVjid8Qk1NprMvQAAAIAspspAelh4bW5ncO5+CedFZPZn86ZCqk02NX3BTcMV4YI2IEzb6R2vzZkjCTuZVy6dcH3ag6JlEfju67euWT5yMnT1I0Ow== me@office-imac', $sshKey->ssh_key->ssh_pub_key);
    }

    public function testAddUrl()
    {
        $newSshKey = array(
            'name'        => 'office-imac',
            'ssh_key_pub' => 'ssh-dss AHJASDBVY6723bgBVhusadkih238723kjLKFnbkjGFklaslkhfgBAFFHGBJbju8)H3hnNGjASGFkjgZn86ZCqk02NX3BTcMV4YI2I4/sebg8VnuebDn0XUbbmVrAq4YqGiobn86ZCqk02NX3BTcMp4QGmyL4/sebg8Vnusytv93cA2PsXOxvbU0CdebDn0XUbbmVrAq4YqGiob48KzCT/NT6L6VoD5n+jSZvQAAAIAspspAelh4bW5ncO5+CedFZPZn86ZCqk02NX3BTcMV4YIaSCO43Y+ghI2of4+E1TDJ1R9Znk9XJsald/U0u0uXwtyHXP2sommNWuAGtzp4QGmyL4/sebg8Vnusytv93cA2PsXOxvbU0CdebDn0XUbbmVrAq4YqGiob48KzCT/NT6L6VoD5n+jSZflFD684gdLsW1+gjVoFBk0MZWuGSXEQyIwlBRq/8jAAAAFQDrxI/h35BewJUmVjid8Qk1NprMvQAAAIAspspAelh4bW5ncO5+CedFZPZn86ZCqk02NX3BTcMV4YI2IEzb6R2vzZkjCTuZVy6dcH3ag6JlEfju67euWT5yMnT1I0Ow== me@office-imac',
        );

        $this->assertEquals(
            'https://api.digitalocean.com/ssh_keys/new/?name=office-imac&ssh_key_pub=ssh-dss+AHJASDBVY6723bgBVhusadkih238723kjLKFnbkjGFklaslkhfgBAFFHGBJbju8%29H3hnNGjASGFkjgZn86ZCqk02NX3BTcMV4YI2I4%2Fsebg8VnuebDn0XUbbmVrAq4YqGiobn86ZCqk02NX3BTcMp4QGmyL4%2Fsebg8Vnusytv93cA2PsXOxvbU0CdebDn0XUbbmVrAq4YqGiob48KzCT%2FNT6L6VoD5n%2BjSZvQAAAIAspspAelh4bW5ncO5%2BCedFZPZn86ZCqk02NX3BTcMV4YIaSCO43Y%2BghI2of4%2BE1TDJ1R9Znk9XJsald%2FU0u0uXwtyHXP2sommNWuAGtzp4QGmyL4%2Fsebg8Vnusytv93cA2PsXOxvbU0CdebDn0XUbbmVrAq4YqGiob48KzCT%2FNT6L6VoD5n%2BjSZflFD684gdLsW1%2BgjVoFBk0MZWuGSXEQyIwlBRq%2F8jAAAAFQDrxI%2Fh35BewJUmVjid8Qk1NprMvQAAAIAspspAelh4bW5ncO5%2BCedFZPZn86ZCqk02NX3BTcMV4YI2IEzb6R2vzZkjCTuZVy6dcH3ag6JlEfju67euWT5yMnT1I0Ow%3D%3D+me%40office-imac&client_id=foo&api_key=bar',
            $this->sshKeysBuildQueryMethod->invoke($this->sshKeys, null, SSHKeysActions::ACTION_ADD, $newSshKey)
        );
    }

    public function testAdd()
    {
        $response = <<<JSON
{"status":"OK","ssh_key":{"id":47,"name":"my_key","ssh_pub_key":"ssh-dss AAAAB3NzaC1kc3MAAACBAK5uLwicCrFEpaVKBzkWxC7RQn+smg5ZQb5keh9RQKo8AszFTol5npgUAr0JWmqKIHv7nof0HndO86x9iIqNjq3vrz9CIVcFfZM7poKBJZ27Hv3v0fmSKfAc6eGdx8eM9UkZe1gzcLXK8UP2HaeY1Y4LlaHXS5tPi/dXooFVgiA7AAAAFQCQl6LZo/VYB9VgPEZzOmsmQevnswAAAIBCNKGsVP5eZ+IJklXheUyzyuL75i04OOtEGW6MO5TymKMwTZlU9r4ukuwxty+T9Ot2LqlNRnLSPQUjb0vplasZ8Ix45JOpRbuSvPovryn7rvS7//klu9hIkFAAQ/AZfGTw+696EjFBg4F5tN6MGMA6KrTQVLXeuYcZeRXwE5t5lwAAAIEAl2xYh098bozJUANQ82DiZznjHc5FW76Xm1apEqsZtVRFuh3V9nc7QNcBekhmHp5Z0sHthXCm1XqnFbkRCdFlX02NpgtNs7OcKpaJP47N8C+C/Yrf8qK/Wt3fExrL2ZLX5XD2tiotugSkwZJMW5Bv0mtjrNt0Q7P45rZjNNTag2c= user@host"}}
JSON
        ;

        $sshKeys = new SSHKeys($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $sshKey  = $sshKeys->add(array('name' => 'foo', 'ssh_pub_key' => 'bar'));

        $this->assertTrue(is_object($sshKey));
        $this->assertEquals('OK', $sshKey->status);

        $this->assertSame(47, $sshKey->ssh_key->id);
        $this->assertSame('my_key', $sshKey->ssh_key->name);
        $this->assertSame('ssh-dss AAAAB3NzaC1kc3MAAACBAK5uLwicCrFEpaVKBzkWxC7RQn+smg5ZQb5keh9RQKo8AszFTol5npgUAr0JWmqKIHv7nof0HndO86x9iIqNjq3vrz9CIVcFfZM7poKBJZ27Hv3v0fmSKfAc6eGdx8eM9UkZe1gzcLXK8UP2HaeY1Y4LlaHXS5tPi/dXooFVgiA7AAAAFQCQl6LZo/VYB9VgPEZzOmsmQevnswAAAIBCNKGsVP5eZ+IJklXheUyzyuL75i04OOtEGW6MO5TymKMwTZlU9r4ukuwxty+T9Ot2LqlNRnLSPQUjb0vplasZ8Ix45JOpRbuSvPovryn7rvS7//klu9hIkFAAQ/AZfGTw+696EjFBg4F5tN6MGMA6KrTQVLXeuYcZeRXwE5t5lwAAAIEAl2xYh098bozJUANQ82DiZznjHc5FW76Xm1apEqsZtVRFuh3V9nc7QNcBekhmHp5Z0sHthXCm1XqnFbkRCdFlX02NpgtNs7OcKpaJP47N8C+C/Yrf8qK/Wt3fExrL2ZLX5XD2tiotugSkwZJMW5Bv0mtjrNt0Q7P45rZjNNTag2c= user@host', $sshKey->ssh_key->ssh_pub_key);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide the name of the SSH Key.
     */
    public function testAddThrowsNameInvalidArgumentException()
    {
        $this->sshKeys->add(array());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide the SSH key.
     */
    public function testAddThrowsSSHKeyPubInvalidArgumentException()
    {
        $this->sshKeys->add(array('name' => 'my-new-ssh-key'));
    }

    public function testEditUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/ssh_keys/123/?client_id=foo&api_key=bar',
            $this->sshKeysBuildQueryMethod->invoke($this->sshKeys, $this->sshKeyId)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide the new public SSH Key.
     */
    public function testEditThrowsSSHKeyPubInvalidArgumentException()
    {
        $this->sshKeys->edit($this->sshKeyId, array());
    }

    public function testEdit()
    {
        $response = <<<JSON
{"status":"OK","ssh_key":{"id":47,"name":"new_ssh_pub_key","ssh_pub_key":"ssh-dss AAAAB3NzaC1kc3MAAACBAK5uLwicCrFEpaVKBzkWxC7RQn+smg5ZQb5keh9RQKo8AszFTol5npgUAr0JWmqKIHv7nof0HndO86x9iIqNjq3vrz9CIVcFfZM7poKBJZ27Hv3v0fmSKfAc6eGdx8eM9UkZe1gzcLXK8UP2HaeY1Y4LlaHXS5tPi/dXooFVgiA7AAAAFQCQl6LZo/VYB9VgPEZzOmsmQevnswAAAIBCNKGsVP5eZ+IJklXheUyzyuL75i04OOtEGW6MO5TymKMwTZlU9r4ukuwxty+T9Ot2LqlNRnLSPQUjb0vplasZ8Ix45JOpRbuSvPovryn7rvS7//klu9hIkFAAQ/AZfGTw+696EjFBg4F5tN6MGMA6KrTQVLXeuYcZeRXwE5t5lwAAAIEAl2xYh098bozJUANQ82DiZznjHc5FW76Xm1apEqsZtVRFuh3V9nc7QNcBekhmHp5Z0sHthXCm1XqnFbkRCdFlX02NpgtNs7OcKpaJP47N8C+C/Yrf8qK/Wt3fExrL2ZLX5XD2tiotugSkwZJMW5Bv0mtjrNt0Q7P45rZjNNTag2c= user@host"}}
JSON
        ;

        $sshKeys = new SSHKeys($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $sshKey  = $sshKeys->edit($this->sshKeyId, array('ssh_pub_key' => 'new_ssh_pub_key'));

        $this->assertTrue(is_object($sshKey));
        $this->assertEquals('OK', $sshKey->status);

        $this->assertSame(47, $sshKey->ssh_key->id);
        $this->assertSame('new_ssh_pub_key', $sshKey->ssh_key->name);
        $this->assertSame('ssh-dss AAAAB3NzaC1kc3MAAACBAK5uLwicCrFEpaVKBzkWxC7RQn+smg5ZQb5keh9RQKo8AszFTol5npgUAr0JWmqKIHv7nof0HndO86x9iIqNjq3vrz9CIVcFfZM7poKBJZ27Hv3v0fmSKfAc6eGdx8eM9UkZe1gzcLXK8UP2HaeY1Y4LlaHXS5tPi/dXooFVgiA7AAAAFQCQl6LZo/VYB9VgPEZzOmsmQevnswAAAIBCNKGsVP5eZ+IJklXheUyzyuL75i04OOtEGW6MO5TymKMwTZlU9r4ukuwxty+T9Ot2LqlNRnLSPQUjb0vplasZ8Ix45JOpRbuSvPovryn7rvS7//klu9hIkFAAQ/AZfGTw+696EjFBg4F5tN6MGMA6KrTQVLXeuYcZeRXwE5t5lwAAAIEAl2xYh098bozJUANQ82DiZznjHc5FW76Xm1apEqsZtVRFuh3V9nc7QNcBekhmHp5Z0sHthXCm1XqnFbkRCdFlX02NpgtNs7OcKpaJP47N8C+C/Yrf8qK/Wt3fExrL2ZLX5XD2tiotugSkwZJMW5Bv0mtjrNt0Q7P45rZjNNTag2c= user@host', $sshKey->ssh_key->ssh_pub_key);
    }

    public function testDestroyUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/ssh_keys/123/destroy/?client_id=foo&api_key=bar',
            $this->sshKeysBuildQueryMethod->invoke($this->sshKeys, $this->sshKeyId, SSHKeysActions::ACTION_DESTROY)
        );
    }

    public function testDestroy()
    {
        $response = <<<JSON
{"status":"OK"}
JSON
        ;

        $sshKeys = new SSHKeys($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $destroy = $sshKeys->destroy($this->sshKeyId);

        $this->assertTrue(is_object($destroy));
        $this->assertEquals('OK', $destroy->status);
    }
}
