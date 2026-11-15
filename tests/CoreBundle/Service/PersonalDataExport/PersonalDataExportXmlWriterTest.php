<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\PersonalDataExport;

use Chamilo\CoreBundle\Service\PersonalDataExport\PersonalDataExportDocumentFactory;
use Chamilo\CoreBundle\Service\PersonalDataExport\PersonalDataExportXmlWriter;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class PersonalDataExportXmlWriterTest extends TestCase
{
    public function testXmlIsWellFormedAndMirrorsTheJsonDocument(): void
    {
        $document = $this->sampleDocument();
        $xml = (new PersonalDataExportXmlWriter())->write($document);
        $dom = $this->loadXml($xml);
        $xpath = $this->xpath($dom);

        $this->assertSame('chamilo-personal-data', $this->stringValue($xpath, '/chamiloPersonalData/format'));
        $this->assertSame('1.0', $this->stringValue($xpath, '/chamiloPersonalData/version'));
        $this->assertSame('2026-11-15T10:00:00+00:00', $this->stringValue($xpath, '/chamiloPersonalData/generated_at'));

        $person = $xpath->query('/chamiloPersonalData/user/schema:Person');
        $this->assertNotFalse($person);
        $this->assertSame(1, $person->length);
        $this->assertInstanceOf(DOMElement::class, $person->item(0));
        $this->assertSame('https://portal.example/user/7', $person->item(0)->getAttribute('id'));
        $this->assertSame('Ada', $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:givenName'));
        $this->assertSame('Lovelace', $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:familyName'));
        $this->assertSame('ada@example.com', $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:email'));
        $this->assertSame('ada', $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:alternateName'));
        $this->assertSame('+3225550000', $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:telephone'));
        $this->assertSame('en-GB', $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:knowsLanguage'));
        $this->assertSame(
            'Rue Example 1',
            $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:address/schema:PostalAddress/schema:streetAddress')
        );
        $this->assertSame(
            'ada',
            $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:identifier/schema:PropertyValue[schema:name="username"]/schema:value')
        );

        $this->assertSame('Agent', $this->stringValue($xpath, '//statement[verb/id[contains(., "completed")]]/actor/objectType'));
        $this->assertSame('ada', $this->stringValue($xpath, '//statement[verb/id[contains(., "completed")]]/actor/account/name'));
        $this->assertSame('https://portal.example', $this->stringValue($xpath, '//statement[verb/id[contains(., "completed")]]/actor/account/homePage'));
        $this->assertSame(
            'http://adlnet.gov/expapi/verbs/completed',
            $this->stringValue($xpath, '//statement[verb/id[contains(., "completed")]]/verb/id')
        );
        $this->assertSame(
            'completed',
            $this->stringValue($xpath, '//statement[verb/id[contains(., "completed")]]/verb/display/value[@xml:lang="en-US"]')
        );
        $this->assertSame(
            'http://adlnet.gov/expapi/activities/assessment',
            $this->stringValue($xpath, '//statement[verb/id[contains(., "completed")]]/object/definition/type')
        );
        $this->assertSame(
            'Export quiz',
            $this->stringValue($xpath, '//statement[verb/id[contains(., "completed")]]/object/definition/name/value[@xml:lang="en-US"]')
        );
        $this->assertSame('16', $this->stringValue($xpath, '//statement[verb/id[contains(., "completed")]]/result/score/raw'));
        $this->assertSame('20', $this->stringValue($xpath, '//statement[verb/id[contains(., "completed")]]/result/score/max'));
        $this->assertSame('0.8', $this->stringValue($xpath, '//statement[verb/id[contains(., "completed")]]/result/score/scaled'));
        $this->assertSame('true', $this->stringValue($xpath, '//statement[verb/id[contains(., "completed")]]/result/completion'));
        $this->assertSame('PT1H', $this->stringValue($xpath, '//statement[verb/id[contains(., "completed")]]/result/duration'));
        $this->assertSame(
            'COURSE1',
            $this->stringValue(
                $xpath,
                '//statement[verb/id[contains(., "completed")]]/context/extensions/extension[@id="'.PersonalDataExportDocumentFactory::EXTENSION_COURSE_CODE.'"]'
            )
        );
        $this->assertSame(
            'http://adlnet.gov/expapi/verbs/registered',
            $this->stringValue($xpath, '//statement[object/id[contains(., "course/")]]/verb/id')
        );
        $this->assertSame('Hello', $this->stringValue($xpath, '/chamiloPersonalData/messages/schema:Message/schema:name'));
        $this->assertSame('legal_accept', $this->stringValue($xpath, '/chamiloPersonalData/consents/schema:AgreeAction/schema:name'));
    }

    public function testXmlEscapesMarkupAndStripsIllegalCharacters(): void
    {
        $rawName = "Ada </schema:givenName><evil>&x=\x01\x00y";
        $document = $this->sampleDocument();
        $document['user']['givenName'] = $rawName;
        $document['user']['description'] = "Bio with <script>alert('xss')</script> and \x0B bell.";
        $document['user']['additionalProperty'] = [[
            '@type' => 'PropertyValue',
            'name' => 'note',
            'propertyID' => 'note',
            'value' => 'A & B < C > "quotes"',
        ]];

        $xml = (new PersonalDataExportXmlWriter())->write($document);
        $this->assertStringNotContainsString('<evil>', $xml);
        $this->assertStringNotContainsString('<script>', $xml);
        $this->assertStringContainsString('&amp;', $xml);
        $this->assertStringContainsString('&lt;', $xml);
        $this->assertStringContainsString('&gt;', $xml);

        $xpath = $this->xpath($this->loadXml($xml));
        $writer = new PersonalDataExportXmlWriter();
        $this->assertSame(
            $writer->sanitizeXmlText($rawName),
            $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:givenName')
        );
        $this->assertSame(
            $writer->sanitizeXmlText("Bio with <script>alert('xss')</script> and \x0B bell."),
            $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:description')
        );
        $this->assertStringNotContainsString("\x00", $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:givenName'));
        $this->assertStringNotContainsString("\x01", $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:givenName'));
        $this->assertSame(
            'A & B < C > "quotes"',
            $this->stringValue($xpath, '/chamiloPersonalData/user/schema:Person/schema:additionalProperty/schema:PropertyValue/schema:value')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleDocument(): array
    {
        return [
            'format' => PersonalDataExportDocumentFactory::FORMAT,
            'version' => PersonalDataExportDocumentFactory::VERSION,
            'generated_at' => '2026-11-15T10:00:00+00:00',
            'user' => [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                '@id' => 'https://portal.example/user/7',
                'identifier' => [
                    [
                        '@type' => 'PropertyValue',
                        'name' => 'chamiloUserId',
                        'propertyID' => 'chamiloUserId',
                        'value' => '7',
                    ],
                    [
                        '@type' => 'PropertyValue',
                        'name' => 'username',
                        'propertyID' => 'username',
                        'value' => 'ada',
                    ],
                ],
                'alternateName' => 'ada',
                'givenName' => 'Ada',
                'familyName' => 'Lovelace',
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
                'telephone' => '+3225550000',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Rue Example 1',
                ],
                'knowsLanguage' => 'en-GB',
            ],
            'learning_activities' => [
                [
                    'id' => 'https://portal.example/xapi/statements/course-registration/1',
                    'actor' => [
                        'objectType' => 'Agent',
                        'name' => 'Ada Lovelace',
                        'account' => [
                            'homePage' => 'https://portal.example',
                            'name' => 'ada',
                        ],
                    ],
                    'verb' => [
                        'id' => 'http://adlnet.gov/expapi/verbs/registered',
                        'display' => ['en-US' => 'registered'],
                    ],
                    'object' => [
                        'objectType' => 'Activity',
                        'id' => 'https://portal.example/course/COURSE1',
                        'definition' => [
                            'name' => ['en-US' => 'Personal Data Course'],
                            'type' => 'http://adlnet.gov/expapi/activities/course',
                        ],
                    ],
                    'context' => [
                        'contextActivities' => [
                            'parent' => [[
                                'objectType' => 'Activity',
                                'id' => 'https://portal.example/course/COURSE1',
                                'definition' => [
                                    'name' => ['en-US' => 'Personal Data Course'],
                                    'type' => 'http://adlnet.gov/expapi/activities/course',
                                ],
                            ]],
                        ],
                        'extensions' => [
                            PersonalDataExportDocumentFactory::EXTENSION_COURSE_CODE => 'COURSE1',
                        ],
                    ],
                    'timestamp' => '2026-11-01T09:00:00+00:00',
                ],
                [
                    'id' => 'https://portal.example/xapi/statements/exercise-attempt/9',
                    'actor' => [
                        'objectType' => 'Agent',
                        'name' => 'Ada Lovelace',
                        'account' => [
                            'homePage' => 'https://portal.example',
                            'name' => 'ada',
                        ],
                    ],
                    'verb' => [
                        'id' => 'http://adlnet.gov/expapi/verbs/completed',
                        'display' => ['en-US' => 'completed'],
                    ],
                    'object' => [
                        'objectType' => 'Activity',
                        'id' => 'https://portal.example/course/COURSE1/exercise/3',
                        'definition' => [
                            'name' => ['en-US' => 'Export quiz'],
                            'type' => 'http://adlnet.gov/expapi/activities/assessment',
                        ],
                    ],
                    'result' => [
                        'completion' => true,
                        'duration' => 'PT1H',
                        'score' => [
                            'raw' => 16.0,
                            'min' => 0,
                            'max' => 20.0,
                            'scaled' => 0.8,
                        ],
                    ],
                    'context' => [
                        'contextActivities' => [
                            'parent' => [[
                                'objectType' => 'Activity',
                                'id' => 'https://portal.example/course/COURSE1',
                                'definition' => [
                                    'name' => ['en-US' => 'Personal Data Course'],
                                    'type' => 'http://adlnet.gov/expapi/activities/course',
                                ],
                            ]],
                        ],
                        'extensions' => [
                            PersonalDataExportDocumentFactory::EXTENSION_COURSE_CODE => 'COURSE1',
                        ],
                    ],
                    'timestamp' => '2026-11-15T09:00:00+00:00',
                ],
            ],
            'messages' => [[
                '@context' => 'https://schema.org',
                '@type' => 'Message',
                '@id' => 'https://portal.example/message/1',
                'name' => 'Hello',
                'text' => 'Hi there',
            ]],
            'consents' => [[
                '@context' => 'https://schema.org',
                '@type' => 'AgreeAction',
                'name' => 'legal_accept',
            ]],
            'logs' => [],
        ];
    }

    private function loadXml(string $xml): DOMDocument
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $loaded = $dom->loadXML($xml);
        $this->assertTrue($loaded, 'The XML export must be well-formed.');
        $this->assertSame('chamiloPersonalData', $dom->documentElement?->tagName);

        return $dom;
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('schema', PersonalDataExportXmlWriter::SCHEMA_NS);

        return $xpath;
    }

    private function stringValue(DOMXPath $xpath, string $query): string
    {
        $value = $xpath->evaluate('string('.$query.')');
        $this->assertIsString($value, $query);

        return $value;
    }
}
