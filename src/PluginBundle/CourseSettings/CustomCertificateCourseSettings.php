<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\CourseSettings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\State\CourseSettings\AbstractCourseSettingsPlugin;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsPluginStore;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class CustomCertificateCourseSettings extends AbstractCourseSettingsPlugin
{
    public function __construct(
        PluginHelper $pluginHelper,
        CourseSettingsPluginStore $store,
        private readonly Connection $connection,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {
        parent::__construct($pluginHelper, $store);
    }

    protected function pluginName(): string
    {
        return 'CustomCertificate';
    }

    public function getIntegrationKey(): string
    {
        return 'customCertificate';
    }

    public function getIntegration(Course $course, ?Session $session): array
    {
        return ['enabled' => $this->pluginHelper->isPluginEnabled($this->pluginName())
            && $this->tableExists('plugin_customcertificate')];
    }

    public function getSection(Course $course, ?Session $session, array $values): ?array
    {
        $certificateDateOptions = [
            ['label' => 'Custom', 'value' => 1],
            ['label' => 'None', 'value' => 2],
        ];
        $certificateIssueDateOptions = [
            ['label' => 'Use certificate download date', 'value' => 1],
            ['label' => 'Use custom date', 'value' => 2],
            ['label' => 'None', 'value' => 3],
            ['label' => 'Use certificate generation date', 'value' => 4],
        ];
        if (null !== $session && (int) $session->getId() > 0) {
            array_unshift($certificateDateOptions, ['label' => 'Use session access dates', 'value' => 0]);
            array_unshift($certificateIssueDateOptions, ['label' => 'Use session access end date', 'value' => 0]);
        }

        return $this->section('custom_certificate', 'Custom certificate', 'gradebook', [
            $this->field('customcertificate_mode', 'Certificate mode', 'radio', options: [
                ['label' => 'Disabled', 'value' => 'disabled'],
                ['label' => 'Course certificate', 'value' => 'course'],
                ['label' => 'Default certificate', 'value' => 'default'],
            ]),
            $this->field('customcertificate_content_course', 'Student and course information', 'editor'),
            $this->field('customcertificate_contents_type', 'Contents to show', 'select', options: [
                ['label' => 'Course description', 'value' => 0],
                ['label' => 'Learning path index', 'value' => 1],
                ['label' => 'Custom', 'value' => 2],
                ['label' => 'Hide', 'value' => 3],
            ]),
            $this->field('customcertificate_contents', 'Contents', 'editor'),
            $this->field('customcertificate_date_change', 'Course delivery dates', 'select', options: $certificateDateOptions),
            $this->field('customcertificate_date_start', 'From', 'date'),
            $this->field('customcertificate_date_end', 'Until', 'date'),
            $this->field('customcertificate_place', 'Place', 'text'),
            $this->field('customcertificate_type_date_expediction', 'Issue date type', 'radio', options: $certificateIssueDateOptions),
            $this->field('customcertificate_day', 'Day', 'text'),
            $this->field('customcertificate_month', 'Month', 'text'),
            $this->field('customcertificate_year', 'Year', 'text'),
            $this->field('customcertificate_signature_text1', 'Signature text 1', 'text'),
            $this->field('customcertificate_signature_text2', 'Signature text 2', 'text'),
            $this->field('customcertificate_signature_text3', 'Signature text 3', 'text'),
            $this->field('customcertificate_signature_text4', 'Signature text 4', 'text'),
            $this->field('customcertificate_margin_left', 'Left margin', 'number', min: 0),
            $this->field('customcertificate_margin_right', 'Right margin', 'number', min: 0),
        ], collapsed: true);
    }

    public function getValues(Course $course, ?Session $session): array
    {
        $data = $this->getCustomCertificateData(
            (int) $course->getId(),
            (int) ($session?->getId() ?? 0),
            (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0),
        );
        $values = [];
        foreach ($data as $key => $value) {
            $values['customcertificate_'.$key] = $value;
        }

        return $values;
    }

    public function managesCourseSetting(string $variable): bool
    {
        return \in_array($variable, ['customcertificate_course_enable', 'use_certificate_default'], true);
    }

    public function saveValues(Course $course, ?Session $session, array $values): void
    {
        if (!$this->pluginHelper->isPluginEnabled($this->pluginName()) || !$this->tableExists('plugin_customcertificate')) {
            return;
        }
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $accessUrlId = (int) ($this->accessUrlHelper->getCurrent()?->getId() ?? 0);
        $existing = $this->getCustomCertificateData($courseId, $sessionId, $accessUrlId);
        $certificateMode = (string) ($values['customcertificate_mode'] ?? 'disabled');
        if ('course' !== $certificateMode && empty($existing['id'])) {
            return;
        }

        $payload = [
            'access_url_id' => $accessUrlId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'content_course' => (string) ($values['customcertificate_content_course'] ?? ''),
            'contents_type' => (int) ($values['customcertificate_contents_type'] ?? 0),
            'contents' => (string) ($values['customcertificate_contents'] ?? ''),
            'date_change' => (int) ($values['customcertificate_date_change'] ?? 0),
            'date_start' => $this->normalizeDate((string) ($values['customcertificate_date_start'] ?? '')),
            'date_end' => $this->normalizeDate((string) ($values['customcertificate_date_end'] ?? '')),
            'type_date_expediction' => (int) ($values['customcertificate_type_date_expediction'] ?? 0),
            'place' => trim((string) ($values['customcertificate_place'] ?? '')),
            'day' => trim((string) ($values['customcertificate_day'] ?? '')),
            'month' => trim((string) ($values['customcertificate_month'] ?? '')),
            'year' => trim((string) ($values['customcertificate_year'] ?? '')),
            'signature_text1' => trim((string) ($values['customcertificate_signature_text1'] ?? '')),
            'signature_text2' => trim((string) ($values['customcertificate_signature_text2'] ?? '')),
            'signature_text3' => trim((string) ($values['customcertificate_signature_text3'] ?? '')),
            'signature_text4' => trim((string) ($values['customcertificate_signature_text4'] ?? '')),
            'margin_left' => max(0, (int) ($values['customcertificate_margin_left'] ?? 0)),
            'margin_right' => max(0, (int) ($values['customcertificate_margin_right'] ?? 0)),
            'certificate_default' => 0,
        ];

        foreach (['logo_left', 'logo_center', 'logo_right', 'seal', 'signature1', 'signature2', 'signature3', 'signature4', 'background'] as $field) {
            $payload[$field] = (string) ($existing[$field] ?? '');
        }

        $types = [
            'access_url_id' => Types::INTEGER,
            'c_id' => Types::INTEGER,
            'session_id' => Types::INTEGER,
            'content_course' => Types::TEXT,
            'contents_type' => Types::INTEGER,
            'contents' => Types::TEXT,
            'date_change' => Types::INTEGER,
            'date_start' => Types::STRING,
            'date_end' => Types::STRING,
            'type_date_expediction' => Types::INTEGER,
            'place' => Types::STRING,
            'day' => Types::STRING,
            'month' => Types::STRING,
            'year' => Types::STRING,
            'signature_text1' => Types::STRING,
            'signature_text2' => Types::STRING,
            'signature_text3' => Types::STRING,
            'signature_text4' => Types::STRING,
            'margin_left' => Types::INTEGER,
            'margin_right' => Types::INTEGER,
            'certificate_default' => Types::INTEGER,
            'logo_left' => Types::STRING,
            'logo_center' => Types::STRING,
            'logo_right' => Types::STRING,
            'seal' => Types::STRING,
            'signature1' => Types::STRING,
            'signature2' => Types::STRING,
            'signature3' => Types::STRING,
            'signature4' => Types::STRING,
            'background' => Types::STRING,
        ];

        if (!empty($existing['id'])) {
            $this->connection->update(
                'plugin_customcertificate',
                $payload,
                ['id' => (int) $existing['id']],
                $types,
                ['id' => Types::INTEGER],
            );

            return;
        }
        $this->connection->insert('plugin_customcertificate', $payload, $types);
    }

    private function getCustomCertificateData(int $courseId, int $sessionId, int $accessUrlId): array
    {
        $defaults = [
            'id' => 0,
            'content_course' => '',
            'contents_type' => 0,
            'contents' => '',
            'date_change' => 0,
            'date_start' => '',
            'date_end' => '',
            'type_date_expediction' => 0,
            'place' => '',
            'day' => '',
            'month' => '',
            'year' => '',
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
            'margin_left' => 0,
            'margin_right' => 0,
        ];
        if (!$this->tableExists('plugin_customcertificate')) {
            return $defaults;
        }
        $result = $this->connection->fetchAssociative(
            'SELECT * FROM plugin_customcertificate WHERE c_id = :courseId AND session_id = :sessionId AND access_url_id = :accessUrlId LIMIT 1',
            ['courseId' => $courseId, 'sessionId' => $sessionId, 'accessUrlId' => $accessUrlId],
            ['courseId' => Types::INTEGER, 'sessionId' => Types::INTEGER, 'accessUrlId' => Types::INTEGER],
        );

        return false === $result ? $defaults : array_replace($defaults, $result);
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ('' === $value) {
            return '1970-01-01 00:00:00';
        }

        return date('Y-m-d 00:00:00', strtotime($value) ?: 0);
    }

    private function tableExists(string $name): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$name]);
    }
}
