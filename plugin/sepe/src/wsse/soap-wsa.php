<?php
/**
 * soap-wsa.php
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

/**
 * Class WSASoap
 */
class WSASoap
{
    const WSANS = 'http://schemas.xmlsoap.org/ws/2004/08/addressing';
    const WSAPFX = 'wsa';
    private $soapNS, $soapPFX;
    private $soapDoc = null;
    private $envelope = null;
    private $SOAPXPath = null;
    private $header = null;
    private $messageID = null;

    private function locateHeader()
    {
        if ($this->header == null) {
            $headers = $this->SOAPXPath->query('//wssoap:Envelope/wssoap:Header');
            $header = $headers->item(0);
            if (!$header) {
                $header = $this->soapDoc->createElementNS($this->soapNS, $this->soapPFX.':Header');
                $this->envelope->insertBefore($header, $this->envelope->firstChild);
            }
            $this->header = $header;
        }

        return $this->header;
    }

    public function __construct($doc)
    {
        $this->soapDoc = $doc;
        $this->envelope = $doc->documentElement;
        $this->soapNS = $this->envelope->namespaceURI;
        $this->soapPFX = $this->envelope->prefix;
        $this->SOAPXPath = new DOMXPath($doc);
        $this->SOAPXPath->registerNamespace('wssoap', $this->soapNS);
        $this->SOAPXPath->registerNamespace('wswsa', self::WSANS);

        $this->envelope->setAttributeNS("http://www.w3.org/2000/xmlns/", 'xmlns:'.self::WSAPFX, self::WSANS);
        $this->locateHeader();
    }

    public function addAction($action)
    {
        /* Add the WSA Action */
        $header = $this->locateHeader();

        $nodeAction = $this->soapDoc->createElementNS(self::WSANS, self::SAPFX.':Action', $action);
        $header->appendChild($nodeAction);
    }

    public function addTo($location)
    {
        /* Add the WSA To */
        $header = $this->locateHeader();

        $nodeTo = $this->soapDoc->createElementNS(WSASoap::WSANS, WSASoap::WSAPFX.':To', $location);
        $header->appendChild($nodeTo);
    }

    private function createID()
    {
        $uuid = md5(uniqid(rand(), true));
        $guid = 'uudi:'.substr($uuid, 0, 8)."-".
            substr($uuid, 8, 4)."-".
            substr($uuid, 12, 4)."-".
            substr($uuid, 16, 4)."-".
            substr($uuid, 20, 12);

        return $guid;
    }

    public function addMessageID($id = null)
    {
        /* Add the WSA MessageID or return existing ID */
        if (!is_null($this->messageID)) {
            return $this->messageID;
        }

        if (empty($id)) {
            $id = $this->createID();
        }

        $header = $this->locateHeader();

        $nodeID = $this->soapDoc->createElementNS(self::WSANS, self::WSAPFX.':MessageID', $id);
        $header->appendChild($nodeID);
        $this->messageID = $id;
    }

    public function addReplyTo($address = null)
    {
        /* Create Message ID is not already added - required for ReplyTo */
        if (is_null($this->messageID)) {
            $this->addMessageID();
        }
        /* Add the WSA ReplyTo */
        $header = $this->locateHeader();

        $nodeReply = $this->soapDoc->createElementNS(self::WSANS, self::WSAPFX.':ReplyTo');
        $header->appendChild($nodeReply);

        if (empty($address)) {
            $address = 'http://schemas.xmlsoap.org/ws/2004/08/addressing/role/anonymous';
        }
        $nodeAddress = $this->soapDoc->createElementNS(self::WSANS, self::WSAPFX.':Address', $address);
        $nodeReply->appendChild($nodeAddress);
    }

    public function getDoc()
    {
        return $this->soapDoc;
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

