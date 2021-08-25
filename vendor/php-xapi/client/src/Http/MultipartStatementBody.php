<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Client\Http;

use Xabbuh\XApi\Model\Attachment;

/**
 * HTTP message body containing serialized statements and their attachments.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class MultipartStatementBody
{
    private $boundary;
    private $serializedStatements;
    private $attachments;

    /**
     * @param string       $serializedStatements The JSON encoded statement(s)
     * @param Attachment[] $attachments          The statement attachments that include not only a file URL
     */
    public function __construct($serializedStatements, array $attachments)
    {
        $this->boundary = uniqid();
        $this->serializedStatements = $serializedStatements;
        $this->attachments = $attachments;
    }

    public function getBoundary()
    {
        return $this->boundary;
    }

    public function build()
    {
        $body = '--'.$this->boundary."\r\n";
        $body .= "Content-Type: application/json\r\n";
        $body .= 'Content-Length: '.strlen($this->serializedStatements)."\r\n";
        $body .= "\r\n";
        $body .= $this->serializedStatements."\r\n";

        foreach ($this->attachments as $attachment) {
            $body .= '--'.$this->boundary."\r\n";
            $body .= 'Content-Type: '.$attachment->getContentType()."\r\n";
            $body .= "Content-Transfer-Encoding: binary\r\n";
            $body .= 'Content-Length: '.$attachment->getLength()."\r\n";
            $body .= 'X-Experience-API-Hash: '.$attachment->getSha2()."\r\n";
            $body .= "\r\n";
            $body .= $attachment->getContent()."\r\n";
        }

        $body .= '--'.$this->boundary.'--'."\r\n";

        return $body;
    }
}
