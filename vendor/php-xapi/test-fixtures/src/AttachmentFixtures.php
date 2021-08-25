<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\DataFixtures;

use Xabbuh\XApi\Model\Attachment;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\LanguageMap;

/**
 * xAPI statement attachment fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class AttachmentFixtures
{
    public static function getTextAttachment()
    {
        return new Attachment(
            IRI::fromString('http://id.tincanapi.com/attachment/supporting_media'),
            'text/plain',
            18,
            'bd1a58265d96a3d1981710dab8b1e1ed04a8d7557ea53ab0cf7b44c04fd01545',
            LanguageMap::create(array('en-US' => 'Text attachment')),
            null,
            null,
            'some text content'
        );
    }

    public static function getJSONAttachment()
    {
        return new Attachment(
            IRI::fromString('http://id.tincanapi.com/attachment/supporting_media'),
            'application/json',
            60,
            'f4135c31e2710764604195dfe4e225884d8108467cc21670803e384b80df88ee',
            LanguageMap::create(array('en-US' => 'JSON attachment')),
            null,
            null,
            '{"propertyA":"value1","propertyB":"value2","propertyC":true}'
        );
    }

    public static function getFileUrlOnlyAttachment()
    {
        return new Attachment(
            IRI::fromString('http://id.tincanapi.com/attachment/supporting_media'),
            'application/octet-stream',
            65556,
            'd14f1580a2cebb6f8d4a8a2fc0d13c67f970e84f8d15677a93ae95c9080df899',
            LanguageMap::create(array('en-US' => 'FileUrl Only attachment')),
            null,
            IRL::fromString('http://tincanapi.com/conformancetest/attachment/fileUrlOnly')
        );
    }
}
