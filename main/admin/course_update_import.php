<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
api_protect_admin_script();

/**
 * Generates a CSV model string showing how the CSV file should be structured for course updates.
 */
function generateCsvModel(array $fields): string
{
    $headerCsv = "<strong>Code</strong>;Title;CourseCategory;Language;Visibility;";

    $exampleCsv = "<b>COURSE001</b>;Introduction to Biology;BIO;english;1;";

    foreach ($fields as $field) {
        $fieldType = (int) $field['field_type'];
        switch ($fieldType) {
            case ExtraField::FIELD_TYPE_CHECKBOX:
                $exampleValue = '1'; // 1 for true, 0 for false
                break;
            case ExtraField::FIELD_TYPE_TAG:
                $exampleValue = 'tag1,tag2,tag3'; // Comma separated list of tags
                break;
            default:
                $exampleValue = 'xxx'; // Example value for text fields
        }

        $headerCsv .= "<span style=\"color:red;\">".$field['field_variable']."</span>;";

        $exampleCsv .= "<span style=\"color:red;\">$exampleValue</span>;";
    }

    $modelCsv = $headerCsv."\n".$exampleCsv;

    return $modelCsv;
}

/**
 * Generates an XML model string showing how the XML file should be structured for course updates.
 */
function generateXmlModel(array $fields): string
{
    $modelXml = "&lt;?xml version=\"1.0\" encoding=\"UTF-8\"?&gt;\n";
    $modelXml .= "&lt;Courses&gt;\n";
    $modelXml .= "    &lt;Course&gt;\n";
    $modelXml .= "        <b>&lt;Code&gt;COURSE001&lt;/Code&gt;</b>\n";
    $modelXml .= "        &lt;Title&gt;Introduction to Biology&lt;/Title&gt;\n";
    $modelXml .= "        &lt;CourseCategory&gt;BIO&lt;/CourseCategory&gt;\n";
    $modelXml .= "        &lt;Language&gt;english&lt;/Language&gt;\n";
    $modelXml .= "        &lt;Visibility&gt;1&lt;/Visibility&gt;\n";
    foreach ($fields as $field) {
        switch ($field['field_type']) {
            case ExtraField::FIELD_TYPE_CHECKBOX:
                $exampleValue = '1'; // 1 for true, 0 for false
                break;
            case ExtraField::FIELD_TYPE_TAG:
                $exampleValue = 'tag1,tag2,tag3'; // Comma separated list of tags
                break;
            default:
                $exampleValue = 'xxx'; // Example value for text fields
        }

        $modelXml .= "        <span style=\"color:red;\">&lt;".$field['field_variable']."&gt;$exampleValue&lt;/".$field['field_variable']."&gt;</span>\n";
    }
    $modelXml .= "    &lt;/Course&gt;\n";
    $modelXml .= "&lt;/Courses&gt;";

    return $modelXml;
}

/**
 * Function to validate course data from the CSV/XML file.
 */
function validateCourseData(array $courses): array
{
    $errors = [];
    $courseCodes = [];

    foreach ($courses as $course) {
        if (empty($course['Code'])) {
            $errors[] = get_lang("CodeIsRequired");
        } else {
            $courseId = api_get_course_int_id($course['Code']);
            if (!$courseId) {
                $errors[] = get_lang("CourseCodeDoesNotExist").': '.$course['Code'];
            } elseif (in_array($course['Code'], $courseCodes)) {
                $errors[] = get_lang("DuplicateCode").': '.$course['Code'];
            }

            $courseCodes[] = $course['Code'];
        }
    }

    return $errors;
}

/**
 * Update course data in the database.
 */
function updateCourse(array $courseData, int $courseId): void
{
    $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
    $fieldsMapping = [
        'Title' => 'title',
        'Language' => 'course_language',
        'CourseCategory' => 'category_code',
        'Visibility' => 'visibility',
    ];
    $params = [];
    foreach ($fieldsMapping as $inputField => $dbField) {
        if (isset($courseData[$inputField])) {
            $params[$dbField] = $courseData[$inputField];
        }
    }

    Database::update($courseTable, $params, ['id = ?' => $courseId]);

    if (isset($courseData['extra'])) {
        $courseData['extra']['code'] = $courseData['Code'];
        $courseData['extra']['item_id'] = $courseId;
        $saveOnlyThisFields = [];
        foreach ($courseData['extra'] as $key => $value) {
            $newKey = preg_replace('/^extra_/', '', $key);
            $saveOnlyThisFields[] = $newKey;
        }
        $courseFieldValue = new ExtraFieldValue('course');
        $courseFieldValue->saveFieldValues(
            $courseData['extra'],
            false,
            false,
            $saveOnlyThisFields,
            [],
            true
        );
    }
}

/**
 * Function to update courses from the imported data.
 */
function updateCourses(array $courses): void
{
    foreach ($courses as $course) {
        $courseId = api_get_course_int_id($course['Code']);
        updateCourse($course, $courseId);
    }
}

/**
 * Function to parse CSV data.
 */
function parseCsvCourseData(string $file, array $extraFields): array
{
    $data = Import::csv_reader($file);
    $courses = [];

    foreach ($data as $row) {
        $courseData = [];
        foreach ($row as $key => $value) {
            if (empty($key)) {
                continue;
            }
            if (in_array($key, array_column($extraFields, 'variable'))) {
                $processedValue = processExtraFieldValue($key, $value, $extraFields);
                $courseData['extra']['extra_'.$key] = $processedValue;
            } else {
                $courseData[$key] = $value;
            }
        }

        $courses[] = $courseData;
    }

    return $courses;
}

/**
 * Function to parse XML data.
 */
function parseXmlCourseData(string $file, array $extraFields): array
{
    $xmlContent = Import::xml($file);
    $courses = [];

    foreach ($xmlContent->filter('Courses > Course') as $xmlCourse) {
        $courseData = [];
        foreach ($xmlCourse->childNodes as $node) {
            if ($node->nodeName !== '#text') {
                $key = $node->nodeName;
                if (empty($key)) {
                    continue;
                }
                $value = $node->nodeValue;
                if (in_array($key, array_column($extraFields, 'variable'))) {
                    $processedValue = processExtraFieldValue($key, $value, $extraFields);
                    $courseData['extra']['extra_'.$key] = $processedValue;
                } else {
                    $courseData[$key] = $value;
                }
            }
        }

        if (!empty($courseData)) {
            $courses[] = $courseData;
        }
    }

    return $courses;
}

/**
 * Processes the value of an extra field based on its type.
 *
 * This function takes the name and value of an extra field, along with an array of all extra fields, and processes
 * the value according to the field type. For checkbox fields, it returns an array with the field name as the key
 * and '1' (checked) or '0' (unchecked) as the value. For tag fields, it splits the string by commas into an array.
 * For other types, it returns the value as is.
 */
function processExtraFieldValue(string $fieldName, $value, array $extraFields)
{
    $fieldIndex = array_search($fieldName, array_column($extraFields, 'variable'));
    if ($fieldIndex === false) {
        return $value;
    }

    $fieldType = $extraFields[$fieldIndex]['field_type'];

    switch ($fieldType) {
        case ExtraField::FIELD_TYPE_CHECKBOX:
            $newValue = 0;
            if ($value == '1') {
                $newValue = ['extra_'.$fieldName => '1'];
            }

            return $newValue;
        case ExtraField::FIELD_TYPE_TAG:
            return explode(',', $value);
        default:
            return $value;
    }
}

$toolName = get_lang('UpdateCourseListXMLCSV');
$interbreadcrumb[] = ["url" => 'index.php', "name" => get_lang('PlatformAdmin')];

$form = new FormValidator('course_update_import');
$form->addHeader(get_lang('UpdateCourseListXMLCSV'));
$form->addFile('importFile', get_lang('ImportCSVFileLocation'));

$form->addElement('radio', 'file_type', get_lang('FileType'), get_lang('CSV'), 'csv');
$form->addElement('radio', 'file_type', '', get_lang('XML'), 'xml');

$defaults['file_type'] = 'csv';
$form->setDefaults($defaults);

$form->addButtonImport(get_lang('Import'));

if ($form->validate()) {
    if (!isset($_FILES['importFile']['error']) || is_array($_FILES['importFile']['error'])) {
        Display::addFlash(Display::return_message(get_lang('InvalidFileUpload'), 'error'));
    } else {
        switch ($_FILES['importFile']['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                Display::addFlash(Display::return_message(get_lang('NoFileSent'), 'error'));
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                Display::addFlash(Display::return_message(get_lang('ExceededFileSizeLimit'), 'error'));
                break;
            default:
                Display::addFlash(Display::return_message(get_lang('UnknownErrors'), 'error'));
        }
    }

    $fileType = $_POST['file_type'];
    $fileExt = strtolower(pathinfo($_FILES['importFile']['name'], PATHINFO_EXTENSION));

    if (($fileType === 'csv' && $fileExt !== 'csv') || ($fileType === 'xml' && $fileExt !== 'xml')) {
        Display::addFlash(Display::return_message(get_lang('InvalidFileType'), 'error'));
    } else {
        $file = $_FILES['importFile']['tmp_name'];
        $extraField = new ExtraField('course');
        $allExtraFields = $extraField->get_all();
        $successfulUpdates = [];
        $failedUpdates = [];
        try {
            if ($fileType === 'csv') {
                $courses = parseCsvCourseData($file, $allExtraFields);
            } else {
                $courses = parseXmlCourseData($file, $allExtraFields);
            }

            foreach ($courses as $course) {
                $courseErrors = validateCourseData([$course]);
                if (!empty($courseErrors)) {
                    $failedUpdates[] = $course['Code'].': '.implode(', ', $courseErrors);
                    continue;
                }
                try {
                    updateCourses([$course]);
                    $successfulUpdates[] = $course['Code'];
                } catch (Exception $e) {
                    $failedUpdates[] = $course['Code'].': '.$e->getMessage();
                }
            }

            if (!empty($successfulUpdates)) {
                Display::addFlash(Display::return_message(get_lang('CoursesUpdatedSuccessfully').': '.implode(', ', $successfulUpdates), 'success'));
            }

            if (!empty($failedUpdates)) {
                foreach ($failedUpdates as $error) {
                    Display::addFlash(Display::return_message(get_lang('UpdateFailedForCourses').': '.$error, 'error'));
                }
            }
        } catch (Exception $e) {
            Display::addFlash(Display::return_message($e->getMessage(), 'error'));
        }
    }
}

$htmlHeadXtra[] = "<script>
    $(document).ready(function() {
        function showFileType(type) {
            if (type === 'csv') {
                $('#csv-model').show();
                $('#xml-model').hide();
            } else {
                $('#csv-model').hide();
                $('#xml-model').show();
            }
        }

        showFileType($('input[name=file_type]:checked').val());

        $('input[name=file_type]').on('change', function() {
            showFileType($(this).val());
        });
    });
</script>";

Display::display_header($toolName);

$form->display();

$extraField = new ExtraField('course');
$allExtraFields = $extraField->get_all();

$extraFields = [];
foreach ($allExtraFields as $field) {
    $extraFields[] = [
        'field_variable' => $field['variable'],
        'field_type' => $field['field_type'],
    ];
}

$csvContent = generateCsvModel($extraFields);
$xmlContent = generateXmlModel($extraFields);
echo '<div id="csv-model"><p>'.get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').'):</p>';
echo '<blockquote><pre>'.$csvContent.'</pre></blockquote></div>';
echo '<div id="xml-model" style="display: none;"><p>'.get_lang('XMLMustLookLike').' ('.get_lang('MandatoryFields').'):</p>';
echo '<blockquote><pre>'.$xmlContent.'</pre></blockquote></div>';
echo '<div id="import-details"><p class="text-muted">Visibility: 0=CLOSED, 1=PRIVATE, 2=OPEN, 3=PUBLIC, 4=HIDDEN.</p></div>';

Display::display_footer();
