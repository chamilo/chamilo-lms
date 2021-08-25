<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Serializer;

use Xabbuh\XApi\Model\DocumentData;

/**
 * Serialize and deserialize {@link DocumentData document data}.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
interface DocumentDataSerializerInterface
{
    /**
     * Serializes document data into a JSON encoded string.
     *
     * @param DocumentData $data The document data to serialize
     *
     * @return string The serialized document data
     */
    public function serializeDocumentData(DocumentData $data);

    /**
     * Parses serialized document data.
     *
     * @param string $data The serialized document data
     *
     * @return DocumentData The parsed document data
     */
    public function deserializeDocumentData($data);
}
