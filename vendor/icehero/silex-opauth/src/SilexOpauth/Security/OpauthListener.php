<?php

namespace SilexOpauth\Security;

use Opauth;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;

/**
 * @author Rafal Lindemann
 *  */
class OpauthListener extends AbstractAuthenticationListener
{

    protected function requiresAuthentication(Request $request)
    {
        if ($this->httpUtils->checkRequestPath($request, 'opauth_' . ($this->providerKey) . '_login')) {
            return true;
        }

        return parent::requiresAuthentication($request);
    }


    protected function attemptAuthentication(Request $request)
    {

        $config = array_merge(
            array(
            'callback_url' => $this->options['check_path'],
            'callback_transport' => 'post' // Won't work with silex session
            ), $this->options['opauth']
        );


        if (parent::requiresAuthentication($request)) {
            if (!isset($_POST['opauth'])) {
                throw new AuthenticationException('opauth post parameter is missing');
            }
            // check_path
            $opauth = new Opauth($config, false);

            $response = unserialize(base64_decode($_POST['opauth']));

            $failureReason = null;
            /**
             * Check if it's an error callback
             */
            if (array_key_exists('error', $response)) {
                throw new AuthenticationException($response['error']);
            } else {
                if (empty($response['auth']) 
                    || empty($response['timestamp']) 
                    || empty($response['signature']) 
                    || empty($response['auth']['provider']) 
                    || empty($response['auth']['uid'])
                ) {
                    throw new AuthenticationException('Missing key auth response components');
                } elseif (
                    !$opauth->validate(
                        sha1(print_r($response['auth'], true)), 
                        $response['timestamp'], 
                        $response['signature'], 
                        $failureReason
                    )
                ) {
                    throw new AuthenticationException($failureReason);
                } else {
                    $token = new OpauthToken(new OpauthResult($response['auth']));
                    return $this->authenticationManager->authenticate($token);
                }
            }
        } else {
            // this should redirect or print, it's an opauth thing...
            new Opauth($config);
            // we need to exit now unfortunately...
            exit();
        }
    }


}