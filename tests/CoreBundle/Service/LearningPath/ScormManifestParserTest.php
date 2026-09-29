<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Service\LearningPath\ArticulateRiseSuspendDataDecoder;
use Chamilo\CoreBundle\Service\LearningPath\ScormManifestParser;
use PHPUnit\Framework\TestCase;

final class ScormManifestParserTest extends TestCase
{
    public function testItDetectsSingleScoArticulateRisePackages(): void
    {
        $manifest = <<<'XML'
<?xml version="1.0"?>
<manifest xmlns="http://www.imsglobal.org/xsd/imscp_v1p1" xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_v1p3">
  <metadata>
    <schemaversion>2004 4th Edition</schemaversion>
  </metadata>
  <organizations default="org1">
    <organization identifier="org1">
      <title>Rise course</title>
      <item identifier="item1" identifierref="resource1">
        <title>Rise course</title>
      </item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="resource1" adlcp:scormType="sco" href="scormdriver/indexAPI.html">
      <file href="scormcontent/index.html" />
      <file href="scormcontent/lib/rise/example.js" />
    </resource>
  </resources>
</manifest>
XML;

        $parsed = (new ScormManifestParser())->parse($manifest);

        self::assertSame(ArticulateRiseSuspendDataDecoder::CONTENT_MAKER, $parsed['contentMaker']);
    }

    public function testItDoesNotMarkMultiScoPackagesAsArticulateRise(): void
    {
        $manifest = <<<'XML'
<?xml version="1.0"?>
<manifest xmlns="http://www.imsglobal.org/xsd/imscp_v1p1" xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_v1p3">
  <metadata>
    <schemaversion>2004 4th Edition</schemaversion>
  </metadata>
  <organizations default="org1">
    <organization identifier="org1">
      <title>Multi SCO</title>
      <item identifier="item1" identifierref="resource1"><title>One</title></item>
      <item identifier="item2" identifierref="resource2"><title>Two</title></item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="resource1" adlcp:scormType="sco" href="scormdriver/indexAPI.html">
      <file href="scormcontent/lib/rise/example.js" />
    </resource>
    <resource identifier="resource2" adlcp:scormType="sco" href="second.html" />
  </resources>
</manifest>
XML;

        $parsed = (new ScormManifestParser())->parse($manifest);

        self::assertNull($parsed['contentMaker']);
    }

    public function testItDoesNotMarkGenericSingleScoPackagesAsArticulateRise(): void
    {
        $manifest = <<<'XML'
<?xml version="1.0"?>
<manifest xmlns="http://www.imsglobal.org/xsd/imscp_v1p1" xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_v1p3">
  <metadata>
    <schemaversion>2004 4th Edition</schemaversion>
  </metadata>
  <organizations default="org1">
    <organization identifier="org1">
      <title>Generic SCORM</title>
      <item identifier="item1" identifierref="resource1"><title>Generic SCORM</title></item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="resource1" adlcp:scormType="sco" href="index.html">
      <file href="app/main.js" />
    </resource>
  </resources>
</manifest>
XML;

        $parsed = (new ScormManifestParser())->parse($manifest);

        self::assertNull($parsed['contentMaker']);
    }
}
