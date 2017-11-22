<?php
/**
 * soap-server-wsse.php
 *
 * Copyright (c) 2007, Robert Richards <rrichards@ctindustries.net>.
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
 * @copyright  2007 Robert Richards <rrichards@ctindustries.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    1.0.0
 */

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Class WSSESoapServer
 */
class WSSESoapServer
{
    const WSSENS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    const WSSENS_2003 = 'http://schemas.xmlsoap.org/ws/2003/06/secext';
    const WSUNS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
    const WSSEPFX = 'wsse';
    const WSUPFX = 'wsu';
    private $soapNS, $soapPFX;
    private $soapDoc = null;
    private $envelope = null;
    private $SOAPXPath = null;
    private $secNode = null;
    public $signAllHeaders = false;

    private function locateSecurityHeader($setActor = null)
    {
        $wsNamespace = null;
        if ($this->secNode == null) {
            $secnode = null;
            $headers = $this->SOAPXPath->query('//wssoap:Envelope/wssoap:Header');
            if ($header = $headers->item(0)) {
                $secnodes = $this->SOAPXPath->query('./*[local-name()="Security"]', $header);

                foreach ($secnodes as $node) {
                    $nsURI = $node->namespaceURI;
                    if (($nsURI == self::WSSENS) || ($nsURI == self::WSSENS_2003)) {
                        $actor = $node->getAttributeNS($this->soapNS, 'actor');
                        if (empty($actor) || ($actor == $setActor)) {
                            $secnode = $node;
                            $wsNamespace = $nsURI;
                            break;
                        }
                    }
                }
            }
            $this->secNode = $secnode;
        }

        return $wsNamespace;
    }

    public function __construct($doc)
    {
        $this->soapDoc = $doc;
        $this->envelope = $doc->documentElement;

        $this->soapNS = $this->envelope->namespaceURI;
        $this->soapPFX = $this->envelope->prefix;

        $this->SOAPXPath = new DOMXPath($doc);
        $this->SOAPXPath->registerNamespace('wssoap', $this->soapNS);
        $this->SOAPXPath->registerNamespace('wswsu', self::WSUNS);
        $wsNamespace = $this->locateSecurityHeader();
        if (!empty($wsNamespace)) {
            $this->SOAPXPath->registerNamespace('wswsse', $wsNamespace);
        }
    }

    public function processSignature($refNode)
    {
        $objXMLSecDSig = new XMLSecurityDSig();
        $objXMLSecDSig->idKeys[] = 'wswsu:Id';
        $objXMLSecDSig->idNS['wswsu'] = self::WSUNS;
        $objXMLSecDSig->sigNode = $refNode;

        /* Canonicalize the signed info */
        $objXMLSecDSig->canonicalizeSignedInfo();
        $retVal = $objXMLSecDSig->validateReference();

        if (!$retVal) {
            throw new Exception("Validation Failed");
        }

        $key = null;
        $objKey = $objXMLSecDSig->locateKey();

        if ($objKey) {
            if ($objKeyInfo = XMLSecEnc::staticLocateKeyInfo($objKey, $refNode)) {
                /* Handle any additional key processing such as encrypted keys here */
            }
        }

        if (empty($objKey)) {
            throw new Exception("Error loading key to handle Signature");
        }
        do {
            if (empty($objKey->key)) {
                $this->SOAPXPath->registerNamespace('xmlsecdsig', XMLSecurityDSig::XMLDSIGNS);
                $query = "./xmlsecdsig:KeyInfo/wswsse:SecurityTokenReference/wswsse:Reference";
                $nodeset = $this->SOAPXPath->query($query, $refNode);
                if ($encmeth = $nodeset->item(0)) {
                    if ($uri = $encmeth->getAttribute("URI")) {
                        $arUrl = parse_url($uri);

                        if (empty($arUrl['path']) && ($identifier = $arUrl['fragment'])) {
                            $query = '//wswsse:BinarySecurityToken[@wswsu:Id="'.$identifier.'"]';
                            $nodeset = $this->SOAPXPath->query($query);
                            if ($encmeth = $nodeset->item(0)) {
                                $x509cert = $encmeth->textContent;
                                $x509cert = str_replace(array("\r", "\n"), "", $x509cert);
                                $x509cert = "-----BEGIN CERTIFICATE-----\n".chunk_split($x509cert, 64, "\n")."-----END CERTIFICATE-----\n";

                                $objKey->loadKey($x509cert);
                                break;
                            }
                        }
                    }
                }
                throw new Exception("Error loading key to handle Signature");
            }
        } while(0);

        if (! $objXMLSecDSig->verify($objKey)) {
            throw new Exception("Unable to validate Signature");
        }

        return true;
    }

    public function process()
    {
        if (empty($this->secNode)) {
            return;
        }
        $node = $this->secNode->firstChild;
        while ($node) {
            $nextNode = $node->nextSibling;
            switch ($node->localName) {
                case "Signature":
                    if ($this->processSignature($node)) {
                        if ($node->parentNode) {
                            $node->parentNode->removeChild($node);
                        }
                    } else {
                        /* throw fault */
                        return false;
                    }
            }
            $node = $nextNode;
        }
        $this->secNode->parentNode->removeChild($this->secNode);
        $this->secNode = null;

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
