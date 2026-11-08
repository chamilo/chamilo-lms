<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use CustomCertificatePlugin;

/**
 * Characterization of public/plugin/CustomCertificate/src/CustomCertificatePlugin.php.
 *
 * LIVE: getInfoCertificate, getInfoCertificateDefault, getActiveCertificateTemplate, isEnabledForCourse,
 * generateHtmlCertificateForUser (Certificate::generateCustomCertificatePluginHtml in certificate.lib.php),
 * getCertificateData / redirectCheck (gradebook certificate page), sanitizeStoredImagePath, the image storage
 * helpers (index.php, file.php, print_certificate.php), isLegacyItemPropertyHidden (rear page). install(),
 * update() (one-shot import of the pre-2.0 gradebook_certificate_alternative table, needs DDL and files) and the
 * exit/header based redirectCheck() / outputStoredFile() are not exercised.
 *
 * The plugin is enabled for the test by inserting its plugin / access_url_rel_plugin rows and the course settings
 * of course 1 (all rolled back). Fixture tag: cu_.
 */
final class CustomCertificatePluginCharacterizationTest extends PluginCharacterizationTestCase
{
    private const TABLE = 'plugin_customcertificate';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::ensurePluginTables(
            [self::TABLE],
            'CustomCertificate',
            'CustomCertificate/src/CustomCertificatePlugin.php',
            'CustomCertificatePlugin',
            'database.php'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        require_once api_get_path(SYS_PLUGIN_PATH).'CustomCertificate/src/CustomCertificatePlugin.php';
        $this->conn->executeStatement('DELETE FROM '.self::TABLE);
    }

    // ---------------------------------------------------------------- fixtures

    private function enablePlugin(): void
    {
        $this->conn->executeStatement(
            "INSERT INTO plugin (title, installed, installed_version, source) VALUES ('CustomCertificate', 1, '1.1', 'third_party')"
        );
        $this->conn->executeStatement(
            'INSERT INTO access_url_rel_plugin (plugin_id, url_id, active, configuration) VALUES (?, 1, 1, NULL)',
            [(int) $this->conn->lastInsertId()]
        );
    }

    private function courseSettings(int $courseId, ?string $enable, ?string $useDefault): void
    {
        $this->conn->executeStatement(
            "DELETE FROM c_course_setting WHERE c_id = ? AND variable IN ('customcertificate_course_enable', 'use_certificate_default')",
            [$courseId]
        );
        foreach (['customcertificate_course_enable' => $enable, 'use_certificate_default' => $useDefault] as $variable => $value) {
            if (null !== $value) {
                $this->conn->executeStatement(
                    "INSERT INTO c_course_setting (c_id, variable, type, value, title) VALUES (?, ?, 'checkbox', ?, ?)",
                    [$courseId, $variable, $value, $variable]
                );
            }
        }
        // api_get_course_setting() keeps a static copy of the settings per course: force a reload.
        api_get_course_setting('customcertificate_course_enable', ['real_id' => $courseId], true);
    }

    private function template(array $values = []): int
    {
        $row = $values + [
            'access_url_id' => 1,
            'c_id' => 1,
            'session_id' => 0,
            'content_course' => 'cu_body ((user_firstname)) ((course_title))',
            'contents_type' => 3,
            'contents' => '',
            'date_change' => 2,
            'date_start' => '2026-01-01 00:00:00',
            'date_end' => '2026-02-01 00:00:00',
            'type_date_expediction' => 3,
            'place' => 'cu_Lima',
            'day' => null,
            'month' => null,
            'year' => null,
            'logo_left' => '',
            'logo_center' => '',
            'logo_right' => '',
            'seal' => '',
            'signature1' => '',
            'signature2' => '',
            'signature3' => '',
            'signature4' => '',
            'signature_text1' => '',
            'signature_text2' => '',
            'signature_text3' => '',
            'signature_text4' => '',
            'background' => '',
            'margin_left' => 10,
            'margin_right' => 20,
            'certificate_default' => 0,
        ];
        $this->conn->insert(self::TABLE, $row);

        return (int) $this->conn->lastInsertId();
    }

    private function category(int $courseId, int $sessionId): int
    {
        $this->conn->executeStatement(
            'INSERT INTO gradebook_category (user_id, c_id, session_id, title, weight, visible, locked, generate_certificates, is_requirement, calculation_mode)
             VALUES (1, ?, ?, ?, 100, 1, 0, 1, 0, ?)',
            [$courseId, $sessionId ?: null, 'cu_category', 'weighted_average']
        );

        return (int) $this->conn->lastInsertId();
    }

    private function certificate(int $userId, int $categoryId, float $score = 77.5): int
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_certificate (cat_id, user_id, score_certificate, created_at, path_certificate, publish)
             VALUES (?, ?, ?, '2026-03-01 10:00:00', 'cu_hash.html', 0)",
            [$categoryId, $userId, $score]
        );

        return (int) $this->conn->lastInsertId();
    }

    // ---------------------------------------------------------------- stored image paths

    /**
     * @dataProvider imagePathProvider
     */
    public function testSanitizeStoredImagePath(string $input, string $expected): void
    {
        $this->assertSame($expected, CustomCertificatePlugin::sanitizeStoredImagePath($input));
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function imagePathProvider(): array
    {
        return [
            'plain relative path' => ['12/logo.png', '12/logo.png'],
            'trimmed' => ["  12/logo.png \n", '12/logo.png'],
            'leading slashes are removed' => ['//12/logo.png', '12/logo.png'],
            'backslashes become slashes' => ['12\\logo.png', '12/logo.png'],
            'empty' => ['', ''],
            'parent traversal' => ['12/../../secret.png', ''],
            'only parent' => ['..', ''],
            'trailing parent' => ['12/..', ''],
            'current directory segment' => ['12/./logo.png', ''],
            'empty segment' => ['12//logo.png', ''],
            'url scheme' => ['https://evil.test/x.png', ''],
            'stream wrapper' => ['php://filter/x', ''],
            'null byte' => ["12/logo.png\0.php", ''],
            'dots inside a name are fine' => ['12/a..b.png', '12/a..b.png'],
        ];
    }

    public function testStoragePathsLiveUnderThePluginDirectory(): void
    {
        $this->assertSame('CustomCertificate', CustomCertificatePlugin::getStoragePath(''));
        $this->assertSame('CustomCertificate/certificates/3/a.png', CustomCertificatePlugin::getStoragePath('certificates/3/a.png'));
        $this->assertSame('CustomCertificate/certificates/3/a.png', CustomCertificatePlugin::getCertificateImageStoragePath('3/a.png'));
        $this->assertSame('', CustomCertificatePlugin::getCertificateImageStoragePath('../3/a.png'));
        // A traversing path is reduced to the plugin root.
        $this->assertSame('CustomCertificate', CustomCertificatePlugin::getStoragePath('../outside'));
    }

    public function testMissingImagesHaveNoSourceUrlOrDeletion(): void
    {
        $this->assertSame('', CustomCertificatePlugin::getCertificateImageSource('99999/cu_missing.png'));
        $this->assertSame('', CustomCertificatePlugin::getCertificateImageSource('../x.png'));
        $this->assertSame('', CustomCertificatePlugin::getCertificateImageUrl('99999/cu_missing.png'));
        $this->assertFalse(CustomCertificatePlugin::deleteUploadedCertificateFile('99999/cu_missing.png'));
        $this->assertFalse(CustomCertificatePlugin::deleteUploadedCertificateFile('../../etc/passwd'));
        $this->assertNull(CustomCertificatePlugin::storeLocalCertificateImage('/no/such/file.png', 5, 'file.png'));
        $this->assertNull(CustomCertificatePlugin::storeUploadedCertificateImage(['tmp_name' => '', 'error' => 0], 5, 'seal'));
    }

    // ---------------------------------------------------------------- templates

    public function testInfoCertificateIsScopedByCourseSessionAndAccessUrl(): void
    {
        // The session template comes first in the table: a query that did not match the session exactly
        // would answer with it for the base course.
        $this->template(['session_id' => 7, 'content_course' => 'cu_course 1 session 7']);
        $this->template(['content_course' => 'cu_course 1 base']);
        $this->template(['c_id' => 2, 'content_course' => 'cu_course 2 base']);
        $this->template(['access_url_id' => 2, 'content_course' => 'cu_other url']);

        $this->assertSame('cu_course 1 base', CustomCertificatePlugin::getInfoCertificate(1, 0, 1)['content_course']);
        $this->assertSame('cu_course 1 session 7', CustomCertificatePlugin::getInfoCertificate(1, 7, 1)['content_course']);
        $this->assertSame('cu_course 2 base', CustomCertificatePlugin::getInfoCertificate(2, 0, 1)['content_course']);
        $this->assertSame('cu_other url', CustomCertificatePlugin::getInfoCertificate(1, 0, 2)['content_course']);
        $this->assertSame([], CustomCertificatePlugin::getInfoCertificate(1, 8, 1));
        // A course that only has a session template has no base template.
        $this->template(['c_id' => 3, 'session_id' => 4, 'content_course' => 'cu_course 3 session 4']);
        $this->assertSame([], CustomCertificatePlugin::getInfoCertificate(3, 0, 1));
        // PINNED: an empty access URL means URL 1.
        $this->assertSame('cu_course 1 base', CustomCertificatePlugin::getInfoCertificate(1, 0, 0)['content_course']);
        $this->assertSame('cu_course 1 base', CustomCertificatePlugin::getInfoCertificate('1', '0', '1')['content_course']);
    }

    public function testDefaultTemplateIsTheFlaggedRowOfTheAccessUrl(): void
    {
        $this->template(['content_course' => 'cu_not default']);
        $this->template(['c_id' => 0, 'certificate_default' => 1, 'content_course' => 'cu_default url 1']);
        $this->template(['c_id' => 0, 'access_url_id' => 2, 'certificate_default' => 1, 'content_course' => 'cu_default url 2']);

        $this->assertSame('cu_default url 1', CustomCertificatePlugin::getInfoCertificateDefault(1)['content_course']);
        $this->assertSame('cu_default url 1', CustomCertificatePlugin::getInfoCertificateDefault(0)['content_course']);
        $this->assertSame('cu_default url 2', CustomCertificatePlugin::getInfoCertificateDefault(2)['content_course']);
        $this->assertSame([], CustomCertificatePlugin::getInfoCertificateDefault(3));
    }

    public function testPluginIsNotEnabledForACourseUnlessEnabledAndConfigured(): void
    {
        $course = ['real_id' => 1];

        $this->courseSettings(1, '1', null);
        $this->assertFalse(CustomCertificatePlugin::isEnabledForCourse($course), 'plugin not enabled on the access URL');

        $this->enablePlugin();
        $this->assertTrue(CustomCertificatePlugin::isEnabledForCourse($course));
        $this->courseSettings(1, null, '1');
        $this->assertTrue(CustomCertificatePlugin::isEnabledForCourse($course));
        $this->courseSettings(1, '0', '0');
        $this->assertFalse(CustomCertificatePlugin::isEnabledForCourse($course));
        $this->assertFalse(CustomCertificatePlugin::isEnabledForCourse([]));
        $this->assertFalse(CustomCertificatePlugin::isEnabledForCourse('AIACT'));
    }

    public function testActiveTemplatePrefersTheCourseOneOnlyWhenTheCourseHasItsOwnEnabled(): void
    {
        $this->enablePlugin();
        $course = ['real_id' => 1];
        $this->template(['content_course' => 'cu_course template']);
        $this->template(['c_id' => 0, 'certificate_default' => 1, 'content_course' => 'cu_default template']);

        $this->courseSettings(1, '1', null);
        $this->assertSame('cu_course template', CustomCertificatePlugin::getActiveCertificateTemplate(1, 0, 1, $course)['content_course']);
        // The course is enabled but has no template for that session: the default one is used.
        $this->assertSame('cu_default template', CustomCertificatePlugin::getActiveCertificateTemplate(1, 9, 1, $course)['content_course']);

        // PINNED: "use the default certificate" alone ignores the course template.
        $this->courseSettings(1, null, '1');
        $this->assertSame('cu_default template', CustomCertificatePlugin::getActiveCertificateTemplate(1, 0, 1, $course)['content_course']);

        $this->courseSettings(1, '0', '0');
        $this->assertSame([], CustomCertificatePlugin::getActiveCertificateTemplate(1, 0, 1, $course));
    }

    public function testActiveTemplateIsEmptyWhenNeitherTheCourseNorTheDefaultHasOne(): void
    {
        $this->enablePlugin();
        $this->courseSettings(1, '1', '1');

        $this->assertSame([], CustomCertificatePlugin::getActiveCertificateTemplate(1, 0, 1, ['real_id' => 1]));
    }

    // ---------------------------------------------------------------- generation

    public function testNothingIsGeneratedForInvalidIdsOrWithoutAnActiveTemplate(): void
    {
        $this->enablePlugin();
        $this->courseSettings(1, '1', null);
        $student = $this->rawUser('cu_student');

        $this->assertSame('', CustomCertificatePlugin::generateHtmlCertificateForUser(0, 'AIACT', 0, $student));
        $this->assertSame('', CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, 0));
        $this->assertSame('', CustomCertificatePlugin::generateHtmlCertificateForUser(987654, 'NOPE', 0, $student));
        // No template row at all.
        $this->assertSame('', CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, $student));
    }

    public function testNothingIsGeneratedWhenThePluginIsDisabled(): void
    {
        $this->courseSettings(1, '1', null);
        $this->template();

        $this->assertSame('', CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, $this->rawUser('cu_dis')));
    }

    public function testGeneratedCertificateReplacesTheUserTagsAndStripsForbiddenMarkup(): void
    {
        $this->enablePlugin();
        $this->courseSettings(1, '1', null);
        $student = $this->rawUser('cu_gen');
        $this->template([
            'content_course' => '<p>((user_firstname)) finished ((course_code)) <b>well</b></p><script>alert(1)</script><iframe src="x"></iframe>',
        ]);

        $html = CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, $student);

        $this->assertStringStartsWith('<html>', $html);
        $this->assertStringEndsWith('</body></html>', $html);
        $this->assertStringContainsString('Firstcu_gen finished AIACT <b>well</b>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('((user_firstname))', $html);
        // margin 10 + 20 on an A4 landscape page.
        $this->assertStringContainsString('width:267mm', $html);
        $this->assertStringContainsString('margin-left:10mm', $html);
        $this->assertStringNotContainsString('caraB', $html, 'no rear page for contents_type 3');
    }

    public function testGeneratedCertificateWithoutWrapIsJustTheFragment(): void
    {
        $this->enablePlugin();
        $this->courseSettings(1, '1', null);
        $this->template();

        $html = CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, $this->rawUser('cu_nw'), 0, 0, false);

        $this->assertStringStartsWith('<div class="caraA"', $html);
        $this->assertStringNotContainsString('<html>', $html);
    }

    public function testGeneratedCertificateHasSignaturesAndAFreeTextRearPageWhenConfigured(): void
    {
        $this->enablePlugin();
        $this->courseSettings(1, '1', null);
        $this->template([
            'signature_text1' => 'cu_Director',
            'contents_type' => 2,
            'contents' => '<p>cu_rear</p><script>x</script>',
        ]);

        $html = CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, $this->rawUser('cu_sig'));

        $this->assertStringContainsString('class="seals"', $html);
        $this->assertStringContainsString('cu_Director', $html);
        $this->assertStringContainsString('<div class="caraB"', $html);
        $this->assertStringContainsString('<p>cu_rear</p>x', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function testGeneratedCertificateExpeditionLineFollowsTheConfiguredDateType(): void
    {
        $this->enablePlugin();
        $this->courseSettings(1, '1', null);
        $student = $this->rawUser('cu_exp');

        $this->template(['type_date_expediction' => 3, 'content_course' => '((date_expediction))']);
        $this->assertStringNotContainsString('cu_Lima', CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, $student));

        $this->conn->executeStatement('DELETE FROM '.self::TABLE);
        $this->template(['type_date_expediction' => 2, 'day' => '5', 'month' => 'May', 'year' => '2026', 'content_course' => '((date_expediction))']);
        $fixed = CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, $student);
        $this->assertStringContainsString('cu_Lima', $fixed);
        $this->assertStringContainsString('5', $fixed);
        $this->assertStringContainsString('2026', $fixed);
    }

    public function testGeneratedCertificateDateRangeComesFromTheTemplateOrIsEmpty(): void
    {
        $this->enablePlugin();
        $this->courseSettings(1, '1', null);
        $student = $this->rawUser('cu_dates');

        $this->template(['date_change' => 1, 'content_course' => '[((start_date))|((end_date))]']);
        $this->assertStringContainsString('[01/01/2026|01/02/2026]', CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, $student));

        $this->conn->executeStatement('DELETE FROM '.self::TABLE);
        $this->template(['date_change' => 2, 'content_course' => '[((start_date))|((end_date))]']);
        $this->assertStringContainsString('[|]', CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, $student));
    }

    public function testGradeTagShowsTheGradebookCertificateScoreOfTheStudent(): void
    {
        $this->enablePlugin();
        $this->courseSettings(1, '1', null);
        $student = $this->rawUser('cu_grade');
        $other = $this->rawUser('cu_grade_other');
        $category = $this->category(1, 0);
        $this->certificate($student, $category, 77.5);
        $this->template(['content_course' => '[((gradebook_grade))]']);

        $withCategory = CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, $student, 0, $category);
        $withoutCategory = CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, $student);
        $someoneElse = CustomCertificatePlugin::generateHtmlCertificateForUser(1, 'AIACT', 0, $other, 0, $category);

        // PINNED: the rounded percentage (score / category weight) as ScoreDisplay prints it.
        $this->assertStringContainsString('[78 % (78 / 100)]', $withCategory);
        $this->assertStringContainsString('[78 % (78 / 100)]', $withoutCategory);
        // The certificate rows of other students never leak into this one.
        $this->assertStringContainsString('[]', $someoneElse);
    }

    // ---------------------------------------------------------------- certificate lookup

    public function testCertificateDataOnlyAnswersForTheOwnerOfTheCertificate(): void
    {
        $this->enablePlugin();
        $this->courseSettings(1, '1', null);
        $owner = $this->rawUser('cu_owner');
        $other = $this->rawUser('cu_other');
        $category = $this->category(1, 0);
        $certificate = $this->certificate($owner, $category);

        $data = CustomCertificatePlugin::getCertificateData($certificate, $owner);

        $this->assertSame(
            ['course_id' => 1, 'course_code' => 'AIACT', 'session_id' => 0, 'user_id' => $owner, 'cat_id' => $category],
            $data
        );
        $this->assertSame([], CustomCertificatePlugin::getCertificateData($certificate, $other));
        $this->assertSame([], CustomCertificatePlugin::getCertificateData(0, $owner));
        $this->assertSame([], CustomCertificatePlugin::getCertificateData($certificate, 0));
        $this->assertSame([], CustomCertificatePlugin::getCertificateData(987654321, $owner));
    }

    public function testCertificateDataIsEmptyWhenTheCourseDoesNotUseTheCustomCertificate(): void
    {
        $this->enablePlugin();
        $this->courseSettings(1, '0', '0');
        $owner = $this->rawUser('cu_off');
        $certificate = $this->certificate($owner, $this->category(1, 0));

        $this->assertSame([], CustomCertificatePlugin::getCertificateData($certificate, $owner));
    }

    public function testLegacyItemPropertyHiddenCheckIsSkippedWhenTheLegacyTableIsGone(): void
    {
        $this->assertFalse(CustomCertificatePlugin::isLegacyItemPropertyHidden('learnpath', 1, 0));
        $this->assertFalse(CustomCertificatePlugin::isLegacyItemPropertyHidden('', 1, 0));
        $this->assertFalse(CustomCertificatePlugin::isLegacyItemPropertyHidden('learnpath', -1, 0));
    }
}
