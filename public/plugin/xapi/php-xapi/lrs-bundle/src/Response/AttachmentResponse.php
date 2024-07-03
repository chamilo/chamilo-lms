<?php

declare(strict_types=1);

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\LrsBundle\Response;

use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Xabbuh\XApi\Model\Attachment;

/**
 * @author Jérôme Parmentier <jerome.parmentier@acensi.fr>
 */
class AttachmentResponse extends Response
{
    protected $attachment;

    public function __construct(Attachment $attachment)
    {
        parent::__construct(null);

        $this->attachment = $attachment;
    }

    public function prepare(Request $request): void
    {
        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', $this->attachment->getContentType());
        }

        $this->headers->set('Content-Transfer-Encoding', 'binary');
        $this->headers->set('X-Experience-API-Hash', $this->attachment->getSha2());
    }

    /**
     * @throws LogicException
     */
    public function sendContent(): void
    {
        throw new LogicException('An AttachmentResponse is only meant to be part of a multipart Response.');
    }

    /**
     * @throws LogicException when the content is not null
     */
    public function setContent($content): void
    {
        if (null !== $content) {
            throw new LogicException('The content cannot be set on an AttachmentResponse instance.');
        }
    }

    /**
     * @return string|null
     */
    public function getContent()
    {
        return $this->attachment->getContent();
    }
}
