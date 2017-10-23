<?php
/**
 * soap-wsse.php
 *
 * Copyright (c) 2010, Robert Richards <rrichards@ctindustries.net>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Robert Richards nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author     Robert Richards <rrichards@ctindustries.net>
 * @copyright  2007-2010 Robert Richards <rrichards@ctindustries.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    1.1.0-dev
 */

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Class WSSESoap
 */
class WSSESoap
{
    const WSSENS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    const WSUNS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
    const WSUNAME = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0';
    const WSSEPFX = 'wsse';
    const WSUPFX = 'wsu';
    private $soapNS, $soapPFX;
    private $soapDoc = null;
    private $envelope = null;
    private $SOAPXPath = null;
    private $secNode = null;
    public $signAllHeaders = false;

    private function locateSecurityHeader($bMustUnderstand = true, $setActor = null)
    {
        if ($this->secNode == null) {
            $headers = $this->SOAPXPath->query('//wssoap:Envelope/wssoap:Header');
            $header = $headers->item(0);
            if (!$header) {
                $header = $this->soapDoc->createElementNS($this->soapNS, $this->soapPFX.':Header');
                $this->envelope->insertBefore($header, $this->envelope->firstChild);
            }
            $secnodes = $this->SOAPXPath->query('./wswsse:Security', $header);
            $secnode = null;
            foreach ($secnodes as $node) {
                $actor = $node->getAttributeNS($this->soapNS, 'actor');
                if ($actor == $setActor) {
                    $secnode = $node;
                    break;
                }
            }
            if (!$secnode) {
                $secnode = $this->soapDoc->createElementNS(self::WSSENS, self::WSSEPFX.':Security');
                ///if (isset($secnode) && !empty($secnode)) {
                    $header->appendChild($secnode);
                //}
                if ($bMustUnderstand) {
                    $secnode->setAttributeNS($this->soapNS, $this->soapPFX.':mustUnderstand', '1');
                }
                if (! empty($setActor)) {
                    $ename = 'actor';
                    if ($this->soapNS == 'http://www.w3.org/2003/05/soap-envelope') {
                        $ename = 'role';
                    }
                    $secnode->setAttributeNS($this->soapNS, $this->soapPFX.':'.$ename, $setActor);
                }
            }
            $this->secNode = $secnode;
        }
        return $this->secNode;
    }

    public function __construct($doc, $bMustUnderstand = true, $setActor = null)
    {
        $this->soapDoc = $doc;
        $this->envelope = $doc->documentElement;
        $this->soapNS = $this->envelope->namespaceURI;
        $this->soapPFX = $this->envelope->prefix;
        $this->SOAPXPath = new DOMXPath($doc);
        $this->SOAPXPath->registerNamespace('wssoap', $this->soapNS);
        $this->SOAPXPath->registerNamespace('wswsse', self::WSSENS);
        $this->locateSecurityHeader($bMustUnderstand, $setActor);
    }

    public function addTimestamp($secondsToExpire = 3600)
    {
        /* Add the WSU timestamps */
        $security = $this->locateSecurityHeader();

        $timestamp = $this->soapDoc->createElementNS(self::WSUNS, self::WSUPFX.':Timestamp');
        $security->insertBefore($timestamp, $security->firstChild);
        $currentTime = time();
        $created = $this->soapDoc->createElementNS(
            self::WSUNS,
            self::WSUPFX.':Created',
            gmdate("Y-m-d\TH:i:s", $currentTime).'Z'
        );
        $timestamp->appendChild($created);
        if (!is_null($secondsToExpire)) {
            $expire = $this->soapDoc->createElementNS(
                self::WSUNS,
                self::WSUPFX.':Expires',
                gmdate("Y-m-d\TH:i:s", $currentTime + $secondsToExpire).'Z'
            );
            $timestamp->appendChild($expire);
        }
    }

    public function addUserToken($userName, $password = null, $passwordDigest = false)
    {
        if ($passwordDigest && empty($password)) {
            throw new Exception("Cannot calculate the digest without a password");
        }

        $security = $this->locateSecurityHeader();

        $token = $this->soapDoc->createElementNS(self::WSSENS, self::WSSEPFX.':UsernameToken');
        $security->insertBefore($token, $security->firstChild);

        $username = $this->soapDoc->createElementNS(self::WSSENS, self::WSSEPFX.':Username', $userName);
        $token->appendChild($username);

        /* Generate nonce - create a 256 bit session key to be used */
        $objKey = new XMLSecurityKey(XMLSecurityKey::AES256_CBC);
        $nonce = $objKey->generateSessionKey();
        unset($objKey);
        $createdate = gmdate("Y-m-d\TH:i:s").'Z';

        if ($password) {
            $passType = self::WSUNAME.'#PasswordText';
            if ($passwordDigest) {
                $password = base64_encode(sha1($nonce.$createdate.$password, true));
                $passType = self::WSUNAME.'#PasswordDigest';
            }
            $passwordNode = $this->soapDoc->createElementNS(self::WSSENS, self::WSSEPFX.':Password', $password);
            $token->appendChild($passwordNode);
            $passwordNode->setAttribute('Type', $passType);
        }

        $nonceNode = $this->soapDoc->createElementNS(self::WSSENS, self::WSSEPFX.':Nonce', base64_encode($nonce));
        $token->appendChild($nonceNode);

        $created = $this->soapDoc->createElementNS(self::WSUNS, self::WSUPFX.':Created', $createdate);
        $token->appendChild($created);
    }

    public function addBinaryToken($cert, $isPEMFormat = true, $isDSig = true)
    {
        $security = $this->locateSecurityHeader();
        $data = XMLSecurityDSig::get509XCert($cert, $isPEMFormat);

        $token = $this->soapDoc->createElementNS(self::WSSENS, self::WSSEPFX.':BinarySecurityToken', $data);
        $security->insertBefore($token, $security->firstChild);

        $token->setAttribute(
            'EncodingType',
            'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary'
        );
        $token->setAttributeNS(self::WSUNS, self::WSUPFX.':Id', XMLSecurityDSig::generate_GUID());
        $token->setAttribute(
            'ValueType',
            'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3'
        );

        return $token;
    }

    public function attachTokentoSig($token)
    {
        if (!($token instanceof DOMElement)) {
            throw new Exception('Invalid parameter: BinarySecurityToken element expected');
        }
        $objXMLSecDSig = new XMLSecurityDSig();
        if ($objDSig = $objXMLSecDSig->locateSignature($this->soapDoc)) {
            $tokenURI = '#'.$token->getAttributeNS(self::WSUNS, "Id");
            $this->SOAPXPath->registerNamespace('secdsig', XMLSecurityDSig::XMLDSIGNS);
            $query = "./secdsig:KeyInfo";
            $nodeset = $this->SOAPXPath->query($query, $objDSig);
            $keyInfo = $nodeset->item(0);
            if (!$keyInfo) {
                $keyInfo = $objXMLSecDSig->createNewSignNode('KeyInfo');
                $objDSig->appendChild($keyInfo);
            }

            $tokenRef = $this->soapDoc->createElementNS(self::WSSENS, self::WSSEPFX.':SecurityTokenReference');
            $keyInfo->appendChild($tokenRef);
            $reference = $this->soapDoc->createElementNS(self::WSSENS, self::WSSEPFX.':Reference');
            $reference->setAttribute("URI", $tokenURI);
            $tokenRef->appendChild($reference);
        } else {
            throw new Exception('Unable to locate digital signature');
        }
    }

    public function signSoapDoc($objKey, $options = null)
    {
        $objDSig = new XMLSecurityDSig();
        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $arNodes = array();
        foreach ($this->secNode->childNodes as $node) {
            if ($node->nodeType == XML_ELEMENT_NODE) {
                $arNodes[] = $node;
            }
        }

        if ($this->signAllHeaders) {
            foreach ($this->secNode->parentNode->childNodes as $node) {
                if (($node->nodeType == XML_ELEMENT_NODE) &&
                    ($node->namespaceURI != self::WSSENS)) {
                    $arNodes[] = $node;
                }
            }
        }

        foreach ($this->envelope->childNodes as $node) {
            if ($node->namespaceURI == $this->soapNS && $node->localName == 'Body') {
                $arNodes[] = $node;
                break;
            }
        }

        $algorithm = XMLSecurityDSig::SHA1;
        if (is_array($options) && isset($options["algorithm"])) {
            $algorithm = $options["algorithm"];
        }

        $arOptions = array('prefix' => self::WSUPFX, 'prefix_ns' => self::WSUNS);
        $objDSig->addReferenceList($arNodes, $algorithm, null, $arOptions);

        $objDSig->sign($objKey);

        $insertTop = true;
        if (is_array($options) && isset($options["insertBefore"])) {
            $insertTop = (bool)$options["insertBefore"];
        }
        $objDSig->appendSignature($this->secNode, $insertTop);

        /* New suff */

        if (is_array($options)) {
            if (!empty($options["KeyInfo"])) {
                if (!empty($options["KeyInfo"]["X509SubjectKeyIdentifier"])) {
                    $sigNode = $this->secNode->firstChild->nextSibling;
                    $objDoc = $sigNode->ownerDocument;
                    $keyInfo = $sigNode->ownerDocument->createElementNS(XMLSecurityDSig::XMLDSIGNS, 'ds:KeyInfo');
                    $sigNode->appendChild($keyInfo);
                    $tokenRef = $objDoc->createElementNS(self::WSSENS, self::WSSEPFX.':SecurityTokenReference');
                    $keyInfo->appendChild($tokenRef);
                    $reference = $objDoc->createElementNS(self::WSSENS, self::WSSEPFX.':KeyIdentifier');
                    $reference->setAttribute(
                        "ValueType",
                        "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509SubjectKeyIdentifier"
                    );
                    $reference->setAttribute(
                        "EncodingType",
                        "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary"
                    );
                    $tokenRef->appendChild($reference);
                    $x509 = openssl_x509_parse($objKey->getX509Certificate());
                    $keyid = $x509["extensions"]["subjectKeyIdentifier"];
                    $arkeyid = split(":", $keyid);

                    $data = "";
                    foreach ($arkeyid as $hexchar) {
                        $data .= chr(hexdec($hexchar));
                    }
                    $dataNode = new DOMText(base64_encode($data));
                    $reference->appendChild($dataNode);
                }
            }
        }
    }

    public function addEncryptedKey($node, $key, $token, $options = null)
    {
        if (!$key->encKey) {
            return false;
        }
        $encKey = $key->encKey;
        $security = $this->locateSecurityHeader();
        $doc = $security->ownerDocument;
        if (!$doc->isSameNode($encKey->ownerDocument)) {
            $key->encKey = $security->ownerDocument->importNode($encKey, true);
            $encKey = $key->encKey;
        }
        if (!empty($key->guid)) {
            return true;
        }

        $lastToken = null;
        $findTokens = $security->firstChild;
        while ($findTokens) {
            if ($findTokens->localName == 'BinarySecurityToken') {
                $lastToken = $findTokens;
            }
            $findTokens = $findTokens->nextSibling;
        }
        if ($lastToken) {
            $lastToken = $lastToken->nextSibling;
        }

        $security->insertBefore($encKey, $lastToken);
        $key->guid = XMLSecurityDSig::generate_GUID();
        $encKey->setAttribute('Id', $key->guid);
        $encMethod = $encKey->firstChild;
        while ($encMethod && $encMethod->localName != 'EncryptionMethod') {
            $encMethod = $encMethod->nextChild;
        }
        if ($encMethod) {
            $encMethod = $encMethod->nextSibling;
        }
        $objDoc = $encKey->ownerDocument;
        $keyInfo = $objDoc->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'dsig:KeyInfo');
        $encKey->insertBefore($keyInfo, $encMethod);
        $tokenRef = $objDoc->createElementNS(self::WSSENS, self::WSSEPFX.':SecurityTokenReference');
        $keyInfo->appendChild($tokenRef);
        /* New suff */
        if (is_array($options)) {
            if (!empty($options["KeyInfo"])) {
                if (!empty($options["KeyInfo"]["X509SubjectKeyIdentifier"])) {
                    $reference = $objDoc->createElementNS(self::WSSENS, self::WSSEPFX.':KeyIdentifier');
                    $reference->setAttribute(
                        "ValueType",
                        "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509SubjectKeyIdentifier"
                    );
                    $reference->setAttribute(
                        "EncodingType",
                        "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary"
                    );
                    $tokenRef->appendChild($reference);
                    $x509 = openssl_x509_parse($token->getX509Certificate());
                    $keyid = $x509["extensions"]["subjectKeyIdentifier"];
                    $arkeyid = split(":", $keyid);
                    $data = "";
                    foreach ($arkeyid as $hexchar) {
                        $data .= chr(hexdec($hexchar));
                    }
                    $dataNode = new DOMText(base64_encode($data));
                    $reference->appendChild($dataNode);

                    return true;
                }
            }
        }

        $tokenURI = '#'.$token->getAttributeNS(self::WSUNS, "Id");
        $reference = $objDoc->createElementNS(self::WSSENS, self::WSSEPFX.':Reference');
        $reference->setAttribute("URI", $tokenURI);
        $tokenRef->appendChild($reference);

        return true;
    }

    public function AddReference($baseNode, $guid)
    {
        $refList = null;
        $child = $baseNode->firstChild;
        while ($child) {
            if (($child->namespaceURI == XMLSecEnc::XMLENCNS) && ($child->localName == 'ReferenceList')) {
                $refList = $child;
                break;
            }
            $child = $child->nextSibling;
        }
        $doc = $baseNode->ownerDocument;
        if (is_null($refList)) {
            $refList = $doc->createElementNS(XMLSecEnc::XMLENCNS, 'xenc:ReferenceList');
            $baseNode->appendChild($refList);
        }
        $dataref = $doc->createElementNS(XMLSecEnc::XMLENCNS, 'xenc:DataReference');
        $refList->appendChild($dataref);
        $dataref->setAttribute('URI', '#'.$guid);
    }

    public function EncryptBody($siteKey, $objKey, $token)
    {
        $enc = new XMLSecEnc();
        $node = false;
        foreach ($this->envelope->childNodes as $node) {
            if ($node->namespaceURI == $this->soapNS && $node->localName == 'Body') {
                break;
            }
        }
        $enc->setNode($node);
        /* encrypt the symmetric key */
        $enc->encryptKey($siteKey, $objKey, false);

        $enc->type = XMLSecEnc::Content;
        /* Using the symmetric key to actually encrypt the data */
        $encNode = $enc->encryptNode($objKey);

        $guid = XMLSecurityDSig::generate_GUID();
        $encNode->setAttribute('Id', $guid);

        $refNode = $encNode->firstChild;
        while ($refNode && $refNode->nodeType != XML_ELEMENT_NODE) {
            $refNode = $refNode->nextSibling;
        }
        if ($refNode) {
            $refNode = $refNode->nextSibling;
        }
        if ($this->addEncryptedKey($encNode, $enc, $token)) {
            $this->AddReference($enc->encKey, $guid);
        }
    }

    public function encryptSoapDoc($siteKey, $objKey, $options = null, $encryptSignature = true)
    {
        $enc = new XMLSecEnc();

        $xpath = new DOMXPath($this->envelope->ownerDocument);
        if ($encryptSignature == false) {
            $nodes = $xpath->query('//*[local-name()="Body"]');
        } else {
            $nodes = $xpath->query('//*[local-name()="Signature"] | //*[local-name()="Body"]');
        }

        foreach ($nodes as $node) {
            $type = XMLSecEnc::Element;
            $name = $node->localName;
            if ($name == "Body") {
                $type = XMLSecEnc::Content;
            }
            $enc->addReference($name, $node, $type);
        }

        $enc->encryptReferences($objKey);

        $enc->encryptKey($siteKey, $objKey, false);

        $nodes = $xpath->query('//*[local-name()="Security"]');
        $signode = $nodes->item(0);
        $this->addEncryptedKey($signode, $enc, $siteKey, $options);
    }

    public function decryptSoapDoc($doc, $options)
    {

        $privKey = null;
        $privKey_isFile = false;
        $privKey_isCert = false;

        if (is_array($options)) {
            $privKey = (!empty($options["keys"]["private"]["key"]) ? $options["keys"]["private"]["key"] : null);
            $privKey_isFile = (!empty($options["keys"]["private"]["isFile"]) ? true : false);
            $privKey_isCert = (!empty($options["keys"]["private"]["isCert"]) ? true : false);
        }

        $objenc = new XMLSecEnc();

        $xpath = new DOMXPath($doc);
        $envns = $doc->documentElement->namespaceURI;
        $xpath->registerNamespace("soapns", $envns);
        $xpath->registerNamespace("soapenc", "http://www.w3.org/2001/04/xmlenc#");

        $nodes = $xpath->query('/soapns:Envelope/soapns:Header/*[local-name()="Security"]/soapenc:EncryptedKey');

        $references = array();
        if ($node = $nodes->item(0)) {
            $objenc = new XMLSecEnc();
            $objenc->setNode($node);
            if (!$objKey = $objenc->locateKey()) {
                throw new Exception("Unable to locate algorithm for this Encrypted Key");
            }
            $objKey->isEncrypted = true;
            $objKey->encryptedCtx = $objenc;
            XMLSecEnc::staticLocateKeyInfo($objKey, $node);
            if ($objKey && $objKey->isEncrypted) {
                $objencKey = $objKey->encryptedCtx;
                $objKey->loadKey($privKey, $privKey_isFile, $privKey_isCert);
                $key = $objencKey->decryptKey($objKey);
                $objKey->loadKey($key);
            }

            $refnodes = $xpath->query('./soapenc:ReferenceList/soapenc:DataReference/@URI', $node);
            foreach ($refnodes as $reference) {
                $references[] = $reference->nodeValue;
            }
        }

        foreach ($references as $reference) {
            $arUrl = parse_url($reference);
            $reference = $arUrl['fragment'];
            $query = '//*[@Id="'.$reference.'"]';
            $nodes = $xpath->query($query);
            $encData = $nodes->item(0);

            if ($algo = $xpath->evaluate("string(./soapenc:EncryptionMethod/@Algorithm)", $encData)) {
                $objKey = new XMLSecurityKey($algo);
                $objKey->loadKey($key);
            }

            $objenc->setNode($encData);
            $objenc->type = $encData->getAttribute("Type");
            $decrypt = $objenc->decryptNode($objKey, true);
        }

        return true;
    }

    public function saveXML()
    {
        return $this->soapDoc->saveXML();
    }

    public function save($file)
    {
        return $this->soapDoc->save($file);
    }
}

