<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/validator.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Base\Validator;

use DOMDocument;

use const DIRECTORY_SEPARATOR;
use const LIBXML_NONET;

class CcValidateType
{
    // v1.0
    public const MANIFEST_VALIDATOR1 = 'cclibxml2validator.xsd';
    public const ASSESSMENT_VALIDATOR1 = '/domainProfile_4/ims_qtiasiv1p2_localised.xsd';
    // Kept for backwards compatibility (original typo).
    public const ASSESMENT_VALIDATOR1 = '/domainProfile_4/ims_qtiasiv1p2_localised.xsd';
    public const DISCUSSION_VALIDATOR1 = '/domainProfile_6/imsdt_v1p0_localised.xsd';
    public const WEBLINK_VALIDATOR1 = '/domainProfile_5/imswl_v1p0_localised.xsd';

    // v1.1
    public const MANIFEST_VALIDATOR11 = 'cc11libxml2validator.xsd';
    public const BLTI_VALIDATOR11 = 'imslticc_v1p0p1.xsd';
    public const ASSESSMENT_VALIDATOR11 = 'ccv1p1_qtiasiv1p2p1_v1p0.xsd';
    public const ASSESMENT_VALIDATOR11 = 'ccv1p1_qtiasiv1p2p1_v1p0.xsd'; // BC
    public const DISCUSSION_VALIDATOR11 = 'ccv1p1_imsdt_v1p1.xsd';
    public const WEBLINK_VALIDATOR11 = 'ccv1p1_imswl_v1p1.xsd';

    // v1.3
    public const MANIFEST_VALIDATOR13 = 'cc13libxml2validator.xsd';
    public const BLTI_VALIDATOR13 = 'imslticc_v1p3.xsd';
    public const ASSESSMENT_VALIDATOR13 = 'ccv1p3_qtiasiv1p2p1_v1p0.xsd';
    public const ASSESMENT_VALIDATOR13 = 'ccv1p3_qtiasiv1p2p1_v1p0.xsd'; // BC
    public const DISCUSSION_VALIDATOR13 = 'ccv1p3_imsdt_v1p3.xsd';
    public const WEBLINK_VALIDATOR13 = 'ccv1p3_imswl_v1p3.xsd';

    /**
     * @var string|null Validator type (XSD file name).
     */
    protected ?string $type = null;

    /**
     * @var string|null Base location (directory) containing the XSD files.
     */
    protected ?string $location = null;

    /**
     * @var string|null Last error produced by validation (if any).
     */
    protected ?string $last_error = null;

    public function __construct(string $type, string $location)
    {
        // Constructor is intentionally lightweight; no IO here.
        $this->type = $type;
        $this->location = rtrim($location, DIRECTORY_SEPARATOR);
    }

    /**
     * Validates the given XML file against the configured XSD.
     *
     * @param string $element absolute or relative path to the XML file
     *
     * @return bool True if valid; false otherwise. Detailed errors go to ErrorMessages.
     */
    public function validate(string $element): bool
    {
        $this->last_error = null;

        $celement = realpath($element);

        $candidates = [
            $this->location.DIRECTORY_SEPARATOR.$this->type,
            __DIR__.DIRECTORY_SEPARATOR.$this->location.DIRECTORY_SEPARATOR.$this->type,
            \dirname(__DIR__).DIRECTORY_SEPARATOR.$this->location.DIRECTORY_SEPARATOR.$this->type,
        ];
        $cvalidator = null;
        foreach ($candidates as $cand) {
            if (is_file($cand)) {
                $cvalidator = $cand;

                break;
            }
        }

        if (empty($celement)) {
            ErrorMessages::instance()->add("Validator: XML file not found: {$element}");
            $this->last_error = 'XML file not found';

            return false;
        }
        if (empty($cvalidator)) {
            ErrorMessages::instance()->add("Validator: XSD file not found: {$this->location}/{$this->type}");
            $this->last_error = 'XSD file not found';

            return false;
        }

        libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->validateOnParse = false;

        if (!$doc->load($celement, LIBXML_NONET)) {
            ErrorMessages::instance()->add("Validator: Could not load XML: {$celement}");
            $this->last_error = 'Could not load XML';

            return false;
        }

        $ok = $doc->schemaValidate($cvalidator);
        if (!$ok) {
            ErrorMessages::instance()->add("Validator: Schema validation FAILED for: {$celement} with {$cvalidator}");
            $this->last_error = 'Schema validation failed';

            $errs = libxml_get_errors();
            foreach ($errs as $e) {
                ErrorMessages::instance()->add(
                    \sprintf(
                        'LibXML: [L%d C%d] %s',
                        (int) $e->line,
                        (int) $e->column,
                        trim((string) $e->message)
                    )
                );
            }
            libxml_clear_errors();
        }

        return $ok;
    }

    /**
     * Returns the last error string, if any.
     */
    public function getLastError(): ?string
    {
        return $this->last_error;
    }
}
