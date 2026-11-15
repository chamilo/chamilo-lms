<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ThirdPartyDataExchange;
use Chamilo\CoreBundle\Entity\ThirdPartyDataExchangeUser;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Service\PersonalDataExport\DnsHostResolver;
use Chamilo\CoreBundle\Service\PersonalDataExport\PersonalDataExportDocumentFactory;
use Chamilo\CoreBundle\Service\PersonalDataExport\PersonalDataExportPusher;
use Chamilo\CoreBundle\Service\PersonalDataExport\PersonalDataExportSigner;
use Chamilo\CoreBundle\Service\PersonalDataExport\PersonalDataExportUrlValidator;
use Chamilo\CoreBundle\Service\PersonalDataExport\PersonalDataExportXmlWriter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMXPath;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

use const ENT_XML1;
use const JSON_THROW_ON_ERROR;

final class PersonalDataExportControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    private const string HMAC_SECRET = 'test-personal-data-export-secret';

    public function testExportDocumentUsesPersonJsonLdAndXapiStatements(): void
    {
        $client = self::createClient();
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        $alice = $this->createUser('pd_alice_'.$suffix, 'AliceSecret1', 'alice-'.$suffix.'@example.com');
        $alice->setPhone('+3225550000');
        $alice->setAddress('Rue Example 1');
        $alice->setLocale('fr_FR');
        self::getContainer()->get(UserRepository::class)->updateUser($alice);

        $course = $this->createCourse('Personal Data Course '.$suffix);
        $course->addUserAsStudent($alice);
        self::getContainer()->get(CourseRepository::class)->update($course);

        $quiz = (new CQuiz())
            ->setTitle('Export quiz '.$suffix)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CQuizRepository::class)->create($quiz);

        $attempt = (new TrackEExercise())
            ->setQuiz($quiz)
            ->setCourse($course)
            ->setUser($alice)
            ->setUserIp('203.0.113.10')
            ->setScore(16.0)
            ->setMaxScore(20.0)
            ->setStatus('completed')
            ->setDataTracking('')
            ->setStartDate(new DateTime('-2 hours'))
            ->setExeDate(new DateTime('-1 hour'))
            ->setExeDuration(3600)
            ->setStepsCounter(1)
            ->setOrigLpId(0)
            ->setOrigLpItemId(0)
            ->setOrigLpItemViewId(0)
            ->setQuestionsToCheck('')
        ;
        $em = $this->getEntityManager();
        $em->persist($attempt);
        $em->flush();

        $document = self::getContainer()->get(PersonalDataExportDocumentFactory::class)
            ->buildExportDocument($alice, new DateTimeImmutable('2026-11-15T10:00:00Z'))
        ;

        $this->assertSame(PersonalDataExportDocumentFactory::FORMAT, $document['format']);
        $this->assertSame(PersonalDataExportDocumentFactory::VERSION, $document['version']);
        $this->assertSame('2026-11-15T10:00:00+00:00', $document['generated_at']);

        $person = $document['user'];
        $this->assertSame('https://schema.org', $person['@context']);
        $this->assertSame('Person', $person['@type']);
        $this->assertSame($alice->getFirstname(), $person['givenName']);
        $this->assertSame($alice->getLastname(), $person['familyName']);
        $this->assertSame('alice-'.$suffix.'@example.com', $person['email']);
        $this->assertSame('pd_alice_'.$suffix, $person['alternateName']);
        $this->assertSame('+3225550000', $person['telephone']);
        $this->assertSame('PostalAddress', $person['address']['@type']);
        $this->assertSame('Rue Example 1', $person['address']['streetAddress']);
        $this->assertSame('fr-FR', $person['knowsLanguage']);
        $this->assertIsArray($person['identifier']);
        $this->assertSame('PropertyValue', $person['identifier'][0]['@type']);

        $verbs = [];
        $exerciseStatement = null;
        $registration = null;
        foreach ($document['learning_activities'] as $statement) {
            $this->assertSame('Agent', $statement['actor']['objectType']);
            $this->assertArrayHasKey('account', $statement['actor']);
            $this->assertSame('pd_alice_'.$suffix, $statement['actor']['account']['name']);
            $this->assertNotSame('', $statement['actor']['account']['homePage']);
            $this->assertStringStartsWith('http://adlnet.gov/expapi/verbs/', $statement['verb']['id']);
            $this->assertArrayHasKey('en-US', $statement['verb']['display']);
            $this->assertSame('Activity', $statement['object']['objectType']);
            $this->assertNotSame('', $statement['object']['id']);
            $this->assertArrayHasKey('type', $statement['object']['definition']);
            $this->assertArrayHasKey('name', $statement['object']['definition']);
            $verbs[] = $statement['verb']['id'];
            if (str_ends_with($statement['verb']['id'], '/completed') && str_contains((string) $statement['object']['id'], 'exercise')) {
                $exerciseStatement = $statement;
            }
            if (str_ends_with($statement['verb']['id'], '/registered') && str_contains((string) $statement['object']['id'], 'course/')) {
                $registration = $statement;
            }
        }

        $this->assertNotNull($registration, 'Course subscription should be an xAPI registered statement.');
        $this->assertSame('http://adlnet.gov/expapi/activities/course', $registration['object']['definition']['type']);
        $this->assertSame($course->getCode(), $registration['context']['extensions'][PersonalDataExportDocumentFactory::EXTENSION_COURSE_CODE]);

        $this->assertNotNull($exerciseStatement, 'The completed quiz attempt should be an xAPI completed statement.');
        $this->assertSame('http://adlnet.gov/expapi/activities/assessment', $exerciseStatement['object']['definition']['type']);
        $this->assertSame(16.0, $exerciseStatement['result']['score']['raw']);
        $this->assertSame(20.0, $exerciseStatement['result']['score']['max']);
        $this->assertSame(0.8, $exerciseStatement['result']['score']['scaled']);
        $this->assertTrue($exerciseStatement['result']['completion']);
        $this->assertArrayNotHasKey('success', $exerciseStatement['result']);
        $this->assertSame('PT1H', $exerciseStatement['result']['duration']);
        $this->assertArrayHasKey('timestamp', $exerciseStatement);
        $this->assertSame($course->getCode(), $exerciseStatement['context']['contextActivities']['parent'][0]['definition']['name']['en-US'] === $course->getTitle() ? $exerciseStatement['context']['extensions'][PersonalDataExportDocumentFactory::EXTENSION_COURSE_CODE] : $course->getCode());

        $this->assertContains('http://adlnet.gov/expapi/verbs/registered', $verbs);
        $this->assertContains('http://adlnet.gov/expapi/verbs/completed', $verbs);
        $this->assertArrayHasKey('messages', $document);
        $this->assertArrayHasKey('consents', $document);
        $this->assertArrayHasKey('logs', $document);
    }

    public function testExerciseSuccessUsesQuizPassMark(): void
    {
        $client = self::createClient();
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        $alice = $this->createUser('pd_pass_'.$suffix, 'AliceSecret1', 'pass-'.$suffix.'@example.com');
        $course = $this->createCourse('Pass Mark Course '.$suffix);
        $course->addUserAsStudent($alice);
        self::getContainer()->get(CourseRepository::class)->update($course);

        $reached = $this->createQuizWithAttempt(
            $course,
            $alice,
            'Pass reached '.$suffix,
            16.0,
            20.0,
            70
        );
        $missed = $this->createQuizWithAttempt(
            $course,
            $alice,
            'Pass missed '.$suffix,
            16.0,
            20.0,
            85
        );
        $noMark = $this->createQuizWithAttempt(
            $course,
            $alice,
            'No pass mark '.$suffix,
            16.0,
            20.0,
            null
        );
        $perfect = $this->createQuizWithAttempt(
            $course,
            $alice,
            'Perfect no mark '.$suffix,
            20.0,
            20.0,
            null
        );

        $document = self::getContainer()->get(PersonalDataExportDocumentFactory::class)
            ->buildExportDocument($alice)
        ;

        $byTitle = [];
        foreach ($document['learning_activities'] as $statement) {
            if (!str_contains((string) $statement['object']['id'], 'exercise')) {
                continue;
            }
            $title = $statement['object']['definition']['name']['en-US'] ?? '';
            $byTitle[$title] = $statement['result'] ?? [];
        }

        $this->assertTrue($byTitle[$reached->getTitle()]['success']);
        $this->assertFalse($byTitle[$missed->getTitle()]['success']);
        $this->assertArrayNotHasKey('success', $byTitle[$noMark->getTitle()]);
        $this->assertTrue($byTitle[$perfect->getTitle()]['success']);

        $xml = self::getContainer()->get(PersonalDataExportDocumentFactory::class)
            ->buildExportXml($alice)
        ;
        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'The XML export must be well-formed.');
        $xpath = new DOMXPath($dom);

        $this->assertSame('true', $xpath->evaluate(
            'string(//statement[object/definition/name/value="'.htmlspecialchars($reached->getTitle(), ENT_XML1).'"]/result/success)'
        ));
        $this->assertSame('false', $xpath->evaluate(
            'string(//statement[object/definition/name/value="'.htmlspecialchars($missed->getTitle(), ENT_XML1).'"]/result/success)'
        ));
        $this->assertSame(0, (int) $xpath->evaluate(
            'count(//statement[object/definition/name/value="'.htmlspecialchars($noMark->getTitle(), ENT_XML1).'"]/result/success)'
        ));
        $this->assertSame('true', $xpath->evaluate(
            'string(//statement[object/definition/name/value="'.htmlspecialchars($perfect->getTitle(), ENT_XML1).'"]/result/success)'
        ));
    }

    public function testDownloadRequiresPasswordAndReturnsOnlyTheCurrentUser(): void
    {
        $client = self::createClient();
        $this->enableGdpr();
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        $alice = $this->createUser('pd_dl_a_'.$suffix, 'AliceSecret1', 'alice-dl-'.$suffix.'@example.com');
        $bob = $this->createUser('pd_dl_b_'.$suffix, 'BobSecret1', 'bob-dl-'.$suffix.'@example.com');

        $client->loginUser($alice);
        $token = $this->exportCsrfToken($client);

        $client->request(
            'POST',
            '/social-network/personal-data/export-download',
            [],
            [],
            $this->jsonHeaders(),
            json_encode(['password' => 'wrong-password', 'csrf_token' => $token], JSON_THROW_ON_ERROR)
        );
        $this->assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        $this->assertStringNotContainsString($alice->getEmail(), (string) $client->getResponse()->getContent());

        $client->request(
            'POST',
            '/social-network/personal-data/export-download',
            [],
            [],
            $this->jsonHeaders(),
            json_encode(['password' => 'AliceSecret1', 'csrf_token' => $token], JSON_THROW_ON_ERROR)
        );
        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('attachment', (string) $client->getResponse()->headers->get('Content-Disposition'));

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('chamilo-personal-data', $payload['format']);
        $this->assertSame('Person', $payload['user']['@type']);
        $this->assertSame($alice->getEmail(), $payload['user']['email']);
        $this->assertSame($alice->getUsername(), $payload['user']['alternateName']);
        $this->assertStringNotContainsString($bob->getEmail(), (string) $client->getResponse()->getContent());
        $this->assertStringNotContainsString($bob->getUsername(), (string) $client->getResponse()->getContent());
        $this->assertArrayNotHasKey('ApiToken', $payload['user']);
        $this->assertStringNotContainsString('password', strtolower((string) $client->getResponse()->getContent()));
    }

    public function testXmlDownloadIsWellFormedAndMatchesJsonLd(): void
    {
        $client = self::createClient();
        $this->enableGdpr();
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        $alice = $this->createUser('pd_xml_a_'.$suffix, 'AliceSecret1', 'alice-xml-'.$suffix.'@example.com');
        $alice->setFirstname('Ada </schema:givenName><evil>&');
        $alice->setLastname('Lovelace');
        if (method_exists($alice, 'setBiography')) {
            $alice->setBiography('Line 1 & more <details>');
        }
        self::getContainer()->get(UserRepository::class)->updateUser($alice);
        $bob = $this->createUser('pd_xml_b_'.$suffix, 'BobSecret1', 'bob-xml-'.$suffix.'@example.com');

        $client->loginUser($alice);
        $token = $this->exportCsrfToken($client);

        $client->request(
            'POST',
            '/social-network/personal-data/export-download',
            [],
            [],
            $this->jsonHeaders(),
            json_encode(['password' => 'wrong-password', 'csrf_token' => $token, 'format' => 'xml'], JSON_THROW_ON_ERROR)
        );
        $this->assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());

        $client->request(
            'POST',
            '/social-network/personal-data/export-download',
            [],
            [],
            $this->jsonHeaders(),
            json_encode(['password' => 'AliceSecret1', 'csrf_token' => $token, 'format' => 'yaml'], JSON_THROW_ON_ERROR)
        );
        $this->assertSame(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());

        $client->request(
            'POST',
            '/social-network/personal-data/export-download',
            [],
            [],
            $this->jsonHeaders(),
            json_encode(['password' => 'AliceSecret1', 'csrf_token' => $token, 'format' => 'json-ld'], JSON_THROW_ON_ERROR)
        );
        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $json = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $client->request(
            'POST',
            '/social-network/personal-data/export-download',
            [],
            [],
            $this->jsonHeaders(),
            json_encode(['password' => 'AliceSecret1', 'csrf_token' => $token, 'format' => 'xml'], JSON_THROW_ON_ERROR)
        );
        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $this->assertStringContainsString('application/xml', (string) $client->getResponse()->headers->get('Content-Type'));
        $this->assertStringContainsString('.xml', (string) $client->getResponse()->headers->get('Content-Disposition'));

        $xmlBody = (string) $client->getResponse()->getContent();
        $this->assertStringNotContainsString('<evil>', $xmlBody);
        $this->assertStringNotContainsString($bob->getEmail(), $xmlBody);
        $this->assertStringNotContainsString($bob->getUsername(), $xmlBody);

        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($xmlBody), 'The downloaded XML must be well-formed.');
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('schema', PersonalDataExportXmlWriter::SCHEMA_NS);

        $this->assertSame($json['format'], $xpath->evaluate('string(/chamiloPersonalData/format)'));
        $this->assertSame($json['version'], $xpath->evaluate('string(/chamiloPersonalData/version)'));
        $this->assertNotSame('', $xpath->evaluate('string(/chamiloPersonalData/generated_at)'));
        $this->assertSame($json['user']['givenName'], $xpath->evaluate('string(/chamiloPersonalData/user/schema:Person/schema:givenName)'));
        $this->assertSame($json['user']['familyName'], $xpath->evaluate('string(/chamiloPersonalData/user/schema:Person/schema:familyName)'));
        $this->assertSame($json['user']['email'], $xpath->evaluate('string(/chamiloPersonalData/user/schema:Person/schema:email)'));
        $this->assertSame($json['user']['alternateName'], $xpath->evaluate('string(/chamiloPersonalData/user/schema:Person/schema:alternateName)'));
        $this->assertSame($alice->getEmail(), $xpath->evaluate('string(/chamiloPersonalData/user/schema:Person/schema:email)'));
        if (isset($json['user']['description'])) {
            $this->assertSame($json['user']['description'], $xpath->evaluate('string(/chamiloPersonalData/user/schema:Person/schema:description)'));
        }

        $jsonVerbs = [];
        foreach ($json['learning_activities'] as $statement) {
            $jsonVerbs[] = $statement['verb']['id'];
        }
        $xmlVerbs = [];
        foreach ($xpath->query('/chamiloPersonalData/learning_activities/statement/verb/id') as $node) {
            $xmlVerbs[] = $node->textContent;
        }
        $this->assertSame($jsonVerbs, $xmlVerbs);
    }

    public function testSsrfDestinationsAreRejected(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        self::getContainer()->set(DnsHostResolver::class, $this->resolver([
            'internal.example.org' => ['10.0.0.8'],
        ]));
        $this->enableGdpr();
        $this->enablePush();
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        $user = $this->createUser('pd_ssrf_'.$suffix, 'SsrfSecret1', 'ssrf-'.$suffix.'@example.com');
        $client->loginUser($user);
        $token = $this->exportCsrfToken($client);

        $blocked = [
            'http://example.org/export',
            'https://127.0.0.1/export',
            'https://10.1.2.3/export',
            'https://169.254.169.254/latest/meta-data',
            'https://[::1]/export',
            'https://internal.example.org/export',
        ];
        foreach ($blocked as $url) {
            $client->request(
                'POST',
                '/social-network/personal-data/export-to-url',
                [],
                [],
                $this->jsonHeaders(),
                json_encode(['password' => 'SsrfSecret1', 'csrf_token' => $token, 'url' => $url], JSON_THROW_ON_ERROR)
            );
            $this->assertSame(
                Response::HTTP_BAD_REQUEST,
                $client->getResponse()->getStatusCode(),
                'Expected SSRF rejection for '.$url.': '.$client->getResponse()->getContent()
            );
        }
    }

    public function testSuccessfulPushIsSignedAndLogged(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $captured = [];
        $mock = new MockHttpClient(
            static function (string $method, string $url, array $options) use (&$captured): MockResponse {
                $captured = [
                    'method' => $method,
                    'url' => $url,
                    'options' => $options,
                ];

                return new MockResponse('{"ok":true}', ['http_code' => 200]);
            }
        );
        $container = self::getContainer();
        $validator = new PersonalDataExportUrlValidator($this->resolver([
            'example.org' => ['93.184.216.34'],
        ]));
        $pusher = new PersonalDataExportPusher(
            $validator,
            $container->get(PersonalDataExportSigner::class),
            $mock,
            $container->get(EntityManagerInterface::class),
            $container->get(TranslatorInterface::class),
        );
        $container->set(PersonalDataExportUrlValidator::class, $validator);
        $container->set(PersonalDataExportPusher::class, $pusher);
        $container->set('chamilo.personal_data_export.http_client', $mock);

        $this->enableGdpr();
        $this->enablePush();
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        $user = $this->createUser('pd_push_'.$suffix, 'PushSecret1', 'push-'.$suffix.'@example.com');
        $client->loginUser($user);
        $token = $this->exportCsrfToken($client);

        $client->request(
            'POST',
            '/social-network/personal-data/export-to-url',
            [],
            [],
            $this->jsonHeaders(),
            json_encode([
                'password' => 'PushSecret1',
                'csrf_token' => $token,
                'url' => 'https://example.org/gdpr-import',
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($payload['success']);
        $this->assertNotEmpty($payload['third_party_data_exchange_id']);

        $this->assertSame('POST', $captured['method']);
        $this->assertSame('https://example.org/gdpr-import', $captured['url']);
        $headers = $this->normalizeHeaders($captured['options']['headers'] ?? []);
        $this->assertArrayHasKey(strtolower(PersonalDataExportSigner::HEADER_SIGNATURE), $headers);
        $this->assertArrayHasKey(strtolower(PersonalDataExportSigner::HEADER_TIMESTAMP), $headers);
        $this->assertArrayHasKey(strtolower(PersonalDataExportSigner::HEADER_NONCE), $headers);

        $body = $captured['options']['body'] ?? '';
        $this->assertStringContainsString('"format": "chamilo-personal-data"', $body);
        $timestamp = (int) $headers[strtolower(PersonalDataExportSigner::HEADER_TIMESTAMP)];
        $nonce = $headers[strtolower(PersonalDataExportSigner::HEADER_NONCE)];
        $signature = $headers[strtolower(PersonalDataExportSigner::HEADER_SIGNATURE)];
        $this->assertStringStartsWith('sha256=', $signature);
        $expected = hash_hmac(
            'sha256',
            $timestamp."\n".$nonce."\n".hash('sha256', $body),
            self::HMAC_SECRET
        );
        $this->assertTrue(hash_equals($expected, substr($signature, 7)));

        $exchangeId = (int) $payload['third_party_data_exchange_id'];
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $exchange = $em->find(ThirdPartyDataExchange::class, $exchangeId);
        $this->assertInstanceOf(ThirdPartyDataExchange::class, $exchange);
        $this->assertStringContainsString('https://example.org/gdpr-import', (string) $exchange->getDescription());

        $links = $em->getRepository(ThirdPartyDataExchangeUser::class)->findBy(['dataExchange' => $exchange]);
        $this->assertCount(1, $links);
        $this->assertSame($user->getId(), $links[0]->getUser()->getId());
    }

    public function testPushIsDisabledByDefault(): void
    {
        $client = self::createClient();
        $this->enableGdpr();
        $this->setSetting('privacy.allow_personal_data_export_to_url', 'false');
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        $user = $this->createUser('pd_off_'.$suffix, 'OffSecret1', 'off-'.$suffix.'@example.com');
        $client->loginUser($user);
        $token = $this->exportCsrfToken($client);

        $client->request(
            'POST',
            '/social-network/personal-data/export-to-url',
            [],
            [],
            $this->jsonHeaders(),
            json_encode([
                'password' => 'OffSecret1',
                'csrf_token' => $token,
                'url' => 'https://example.org/gdpr-import',
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    private function createQuizWithAttempt(
        Course $course,
        User $user,
        string $title,
        float $score,
        float $maxScore,
        ?int $passPercentage,
    ): CQuiz {
        $quiz = (new CQuiz())
            ->setTitle($title)
            ->setCreator($this->getAdmin())
            ->setParent($course)
            ->addCourseLink($course)
        ;
        if (null !== $passPercentage) {
            $quiz->setPassPercentage($passPercentage);
        }
        self::getContainer()->get(CQuizRepository::class)->create($quiz);

        $attempt = (new TrackEExercise())
            ->setQuiz($quiz)
            ->setCourse($course)
            ->setUser($user)
            ->setUserIp('203.0.113.10')
            ->setScore($score)
            ->setMaxScore($maxScore)
            ->setStatus('completed')
            ->setDataTracking('')
            ->setStartDate(new DateTime('-2 hours'))
            ->setExeDate(new DateTime('-1 hour'))
            ->setExeDuration(60)
            ->setStepsCounter(1)
            ->setOrigLpId(0)
            ->setOrigLpItemId(0)
            ->setOrigLpItemViewId(0)
            ->setQuestionsToCheck('')
        ;
        $em = $this->getEntityManager();
        $em->persist($attempt);
        $em->flush();

        return $quiz;
    }

    private function enableGdpr(): void
    {
        $this->setSetting('privacy.disable_gdpr', 'false');
    }

    private function enablePush(): void
    {
        $this->setSetting('privacy.allow_personal_data_export_to_url', 'true');
        $this->setSetting('privacy.personal_data_export_hmac_secret', self::HMAC_SECRET);
    }

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
    }

    private function exportCsrfToken(KernelBrowser $client): string
    {
        $client->request('GET', '/social-network/personal-data/export-options');
        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return (string) $data['csrf_token'];
    }

    /**
     * @return array<string, string>
     */
    private function jsonHeaders(): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SEC_FETCH_SITE' => 'same-origin',
        ];
    }

    /**
     * @param array<string, list<string>> $records
     */
    private function resolver(array $records): DnsHostResolver
    {
        return new class($records) implements DnsHostResolver {
            /**
             * @param array<string, list<string>> $records
             */
            public function __construct(
                private readonly array $records
            ) {}

            public function resolve(string $host): array
            {
                return $this->records[$host] ?? [];
            }
        };
    }

    /**
     * @param array<int|string, mixed> $headers
     *
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            if (\is_int($key) && \is_string($value) && str_contains($value, ':')) {
                [$name, $headerValue] = explode(':', $value, 2);
                $normalized[strtolower(trim($name))] = trim($headerValue);

                continue;
            }
            if (\is_string($key)) {
                $normalized[strtolower($key)] = \is_array($value) ? (string) reset($value) : (string) $value;
            }
        }

        return $normalized;
    }
}
