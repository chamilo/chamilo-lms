<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionEditor;
use InvalidArgumentException;

final class ExerciseRegressionFixtureQuestionFactory
{
    public const UNIQUE_ANSWER = 1;
    public const MULTIPLE_ANSWER = 2;
    public const FILL_IN_BLANKS = 3;
    public const MATCHING = 4;
    public const FREE_ANSWER = 5;
    public const HOT_SPOT = 6;
    public const HOT_SPOT_DELINEATION = 8;
    public const MULTIPLE_ANSWER_COMBINATION = 9;
    public const UNIQUE_ANSWER_NO_OPTION = 10;
    public const MULTIPLE_ANSWER_TRUE_FALSE = 11;
    public const MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE = 12;
    public const ORAL_EXPRESSION = 13;
    public const GLOBAL_MULTIPLE_ANSWER = 14;
    public const MEDIA_QUESTION = 15;
    public const CALCULATED_ANSWER = 16;
    public const UNIQUE_ANSWER_IMAGE = 17;
    public const DRAGGABLE = 18;
    public const MATCHING_DRAGGABLE = 19;
    public const ANNOTATION = 20;
    public const READING_COMPREHENSION = 21;
    public const MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY = 22;
    public const UPLOAD_ANSWER = 23;
    public const MATCHING_COMBINATION = 24;
    public const MATCHING_DRAGGABLE_COMBINATION = 25;
    public const HOT_SPOT_COMBINATION = 26;
    public const FILL_IN_BLANKS_COMBINATION = 27;
    public const MULTIPLE_ANSWER_DROPDOWN_COMBINATION = 28;
    public const MULTIPLE_ANSWER_DROPDOWN = 29;
    public const ANSWER_IN_OFFICE_DOC = 30;
    public const PAGE_BREAK = 31;

    /**
     * Type 7 (legacy HOT_SPOT_ORDER) is intentionally absent: it is not
     * exposed by the current Vue question selector or supported by the modern
     * ExerciseQuestionEditorProcessor.
     *
     * @return list<int>
     */
    public function supportedTypes(): array
    {
        return [
            self::UNIQUE_ANSWER,
            self::MULTIPLE_ANSWER,
            self::FILL_IN_BLANKS,
            self::MATCHING,
            self::FREE_ANSWER,
            self::HOT_SPOT,
            self::HOT_SPOT_DELINEATION,
            self::MULTIPLE_ANSWER_COMBINATION,
            self::UNIQUE_ANSWER_NO_OPTION,
            self::MULTIPLE_ANSWER_TRUE_FALSE,
            self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE,
            self::ORAL_EXPRESSION,
            self::GLOBAL_MULTIPLE_ANSWER,
            self::MEDIA_QUESTION,
            self::CALCULATED_ANSWER,
            self::UNIQUE_ANSWER_IMAGE,
            self::DRAGGABLE,
            self::MATCHING_DRAGGABLE,
            self::ANNOTATION,
            self::READING_COMPREHENSION,
            self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY,
            self::UPLOAD_ANSWER,
            self::MATCHING_COMBINATION,
            self::MATCHING_DRAGGABLE_COMBINATION,
            self::HOT_SPOT_COMBINATION,
            self::FILL_IN_BLANKS_COMBINATION,
            self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION,
            self::MULTIPLE_ANSWER_DROPDOWN,
            self::ANSWER_IN_OFFICE_DOC,
            self::PAGE_BREAK,
        ];
    }

    /**
     * Standard-feedback exercise types. Hotspot delineation is excluded
     * because Chamilo only allows it in immediate-feedback adaptive exercises.
     *
     * @return list<int>
     */
    public function standardTypes(): array
    {
        $types = array_values(array_filter(
            $this->supportedTypes(),
            static fn (int $type): bool => !\in_array($type, [self::HOT_SPOT_DELINEATION, self::PAGE_BREAK], true),
        ));

        $position = array_search(self::GLOBAL_MULTIPLE_ANSWER, $types, true);
        if (false === $position) {
            $types[] = self::PAGE_BREAK;

            return $types;
        }

        array_splice($types, $position + 1, 0, [self::PAGE_BREAK]);

        return $types;
    }

    /**
     * @return list<int>
     */
    public function adaptiveTypes(): array
    {
        return [self::HOT_SPOT_DELINEATION];
    }

    public function create(int $type): ExerciseQuestionEditor
    {
        if (!\in_array($type, $this->supportedTypes(), true)) {
            throw new InvalidArgumentException('Unsupported regression fixture question type: '.$type);
        }

        $data = new ExerciseQuestionEditor();
        $data->type = $type;
        $data->title = \sprintf('[QA T%02d] %s', $type, $this->label($type));
        $data->description = '<p>Deterministic MCP regression fixture for Chamilo Exercises.</p>';
        $data->feedback = '<p>Regression fixture feedback.</p>';
        $data->score = 10.0;
        $data->globalScore = 10.0;
        $data->correctScore = 2.0;
        $data->wrongScore = -1.0;
        $data->unknownScore = 0.0;
        $data->noNegativeScore = self::GLOBAL_MULTIPLE_ANSWER === $type;
        $data->difficulty = 1;
        $data->mandatory = false;
        $data->duration = null;
        $data->categoryId = 0;
        $data->parentMediaId = 0;
        $data->matchingOrientation = 'h';

        switch ($type) {
            case self::UNIQUE_ANSWER:
                $data->answers = $this->choiceAnswers(['Lima', 'Cusco', 'Arequipa', 'Piura'], [0], 10.0);

                break;

            case self::MULTIPLE_ANSWER:
                $data->answers = $this->choiceAnswers(['2', '3', '4', '6'], [0, 1], 5.0);

                break;

            case self::FILL_IN_BLANKS:
                $this->configureFillBlanks($data, false);

                break;

            case self::MATCHING:
            case self::MATCHING_DRAGGABLE:
                $this->configureMatching($data, false);

                break;

            case self::FREE_ANSWER:
                $data->description = '<p>Write a short explanation of why regression testing matters.</p>';

                break;

            case self::HOT_SPOT:
                $this->configureHotspot($data, false, false);

                break;

            case self::HOT_SPOT_DELINEATION:
                $this->configureHotspot($data, false, true);

                break;

            case self::MULTIPLE_ANSWER_COMBINATION:
                $data->answers = $this->choiceAnswers(['Mercury', 'Venus', 'Earth', 'Pluto'], [1, 2], 0.0);

                break;

            case self::UNIQUE_ANSWER_NO_OPTION:
                $data->answers = $this->choiceAnswers(['4', '5', '6'], [0], 10.0);
                $data->answers[] = [
                    'answer' => 'Don\'t know',
                    'correct' => false,
                    'correctChoice' => 0,
                    'comment' => '',
                    'score' => 0.0,
                    'position' => 666,
                    'isUnknown' => true,
                ];

                break;

            case self::MULTIPLE_ANSWER_TRUE_FALSE:
                $this->configureTrueFalse($data);

                break;

            case self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
                $this->configureTrueFalse($data);

                break;

            case self::ORAL_EXPRESSION:
                $data->description = '<p>Record a short spoken answer.</p>';

                break;

            case self::GLOBAL_MULTIPLE_ANSWER:
                $data->answers = $this->choiceAnswers(['PHP', 'JavaScript', 'HTML', 'SQL'], [0, 1, 3], 0.0);

                break;

            case self::MEDIA_QUESTION:
                $data->description = '<p><strong>Media block:</strong> use this content as context for nearby questions.</p>';
                $data->score = 0.0;
                $data->globalScore = 0.0;

                break;

            case self::CALCULATED_ANSWER:
                $data->calculatedText = '<p>Calculate [x] + [y]. Result: []</p>';
                $data->calculatedFormula = '[x] + [y]';
                $data->calculatedRanges = [
                    ['token' => '[x]', 'low' => '1', 'high' => '5', 'position' => 1],
                    ['token' => '[y]', 'low' => '6', 'high' => '10', 'position' => 2],
                ];
                $data->calculatedVariations = 2;
                $data->calculatedComment = 'The result is x + y.';

                break;

            case self::UNIQUE_ANSWER_IMAGE:
                $image = $this->imageDataUri();
                $data->answers = [
                    $this->answer('<p><img src="'.$image.'" alt="Correct regression image"></p>', true, 10.0, 1),
                    $this->answer('<p><img src="'.$image.'" alt="Alternative regression image"></p>', false, 0.0, 2),
                ];

                break;

            case self::DRAGGABLE:
                $data->draggableItems = [
                    ['answer' => 'First', 'targetPosition' => 1, 'score' => 5.0, 'position' => 1],
                    ['answer' => 'Second', 'targetPosition' => 2, 'score' => 5.0, 'position' => 2],
                ];

                break;

            case self::ANNOTATION:
                $data->description = '<p>Annotate the supplied regression image.</p>';
                $data->annotationImageData = $this->imageDataUri();
                $data->annotationImageName = 'chamilo-regression-annotation.png';
                $data->annotationImageMimeType = 'image/png';

                break;

            case self::READING_COMPREHENSION:
                $data->description = '<p>Chamilo is an open-source learning management system. Read this passage before continuing.</p>';
                $data->score = 0.0;
                $data->globalScore = 0.0;

                break;

            case self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY:
                $this->configureTrueFalse($data);
                $data->unknownScore = 0.0;

                break;

            case self::UPLOAD_ANSWER:
                $data->description = '<p>Upload a small file as the learner answer.</p>';

                break;

            case self::MATCHING_COMBINATION:
            case self::MATCHING_DRAGGABLE_COMBINATION:
                $this->configureMatching($data, true);

                break;

            case self::HOT_SPOT_COMBINATION:
                $this->configureHotspot($data, true, false);

                break;

            case self::FILL_IN_BLANKS_COMBINATION:
                $this->configureFillBlanks($data, true);

                break;

            case self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION:
                $data->answers = $this->choiceAnswers(['Red', 'Green', 'Blue'], [0, 2], 0.0);
                $data->dropdownListText = "Red\nGreen\nBlue";

                break;

            case self::MULTIPLE_ANSWER_DROPDOWN:
                $data->answers = $this->choiceAnswers(['Spring', 'Summer', 'Winter'], [1], 10.0);
                $data->dropdownListText = "Spring\nSummer\nWinter";

                break;

            case self::ANSWER_IN_OFFICE_DOC:
                $data->description = '<p>Edit the Office document and submit it as the answer.</p>';
                $data->onlyofficeTemplateName = 'chamilo-regression-answer.docx';
                $data->onlyofficeTemplateMimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                $data->onlyofficeTemplateData = 'data:application/vnd.openxmlformats-officedocument.wordprocessingml.document;base64,'.$this->officeTemplateBase64();

                break;

            case self::PAGE_BREAK:
                $data->description = '<p>Regression page break.</p>';
                $data->score = 0.0;
                $data->globalScore = 0.0;

                break;
        }

        return $data;
    }

    public function label(int $type): string
    {
        return match ($type) {
            self::UNIQUE_ANSWER => 'Unique answer',
            self::MULTIPLE_ANSWER => 'Multiple answer',
            self::FILL_IN_BLANKS => 'Fill in blanks',
            self::MATCHING => 'Matching',
            self::FREE_ANSWER => 'Open question',
            self::HOT_SPOT => 'Hotspot',
            self::HOT_SPOT_DELINEATION => 'Hotspot delineation',
            self::MULTIPLE_ANSWER_COMBINATION => 'Exact selection',
            self::UNIQUE_ANSWER_NO_OPTION => 'Unique answer with unknown',
            self::MULTIPLE_ANSWER_TRUE_FALSE => 'Multiple answer true/false',
            self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE => 'Combination true/false',
            self::ORAL_EXPRESSION => 'Oral expression',
            self::GLOBAL_MULTIPLE_ANSWER => 'Global multiple answer',
            self::MEDIA_QUESTION => 'Media question',
            self::CALCULATED_ANSWER => 'Calculated answer',
            self::UNIQUE_ANSWER_IMAGE => 'Unique answer with images',
            self::DRAGGABLE => 'Sequence ordering',
            self::MATCHING_DRAGGABLE => 'Matching draggable',
            self::ANNOTATION => 'Annotation',
            self::READING_COMPREHENSION => 'Reading comprehension',
            self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY => 'True/false with degree of certainty',
            self::UPLOAD_ANSWER => 'Upload answer',
            self::MATCHING_COMBINATION => 'Matching combination',
            self::MATCHING_DRAGGABLE_COMBINATION => 'Matching draggable combination',
            self::HOT_SPOT_COMBINATION => 'Hotspot combination',
            self::FILL_IN_BLANKS_COMBINATION => 'Fill in blanks combination',
            self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION => 'Multiple answer dropdown combination',
            self::MULTIPLE_ANSWER_DROPDOWN => 'Multiple answer dropdown',
            self::ANSWER_IN_OFFICE_DOC => 'Answer in Office document',
            self::PAGE_BREAK => 'Page break',
            default => throw new InvalidArgumentException('Unknown regression fixture question type: '.$type),
        };
    }

    private function configureFillBlanks(ExerciseQuestionEditor $data, bool $combination): void
    {
        $data->fillBlanksText = 'The capital of Peru is [Lima] and 2 + 2 is [4].';
        $data->fillBlankItems = [
            ['answer' => 'Lima', 'score' => $combination ? 0.0 : 5.0, 'inputSize' => 200, 'position' => 1],
            ['answer' => '4', 'score' => $combination ? 0.0 : 5.0, 'inputSize' => 120, 'position' => 2],
        ];
        $data->fillBlanksSeparator = 0;
        $data->fillBlanksSwitchable = false;
        $data->fillBlanksCaseInsensitive = true;
        $data->fillBlanksComment = 'Both blanks must be completed.';
        $data->score = $combination ? 0.0 : 10.0;
        $data->globalScore = $combination ? 10.0 : 0.0;
    }

    private function configureMatching(ExerciseQuestionEditor $data, bool $combination): void
    {
        $data->matchingOptions = [
            ['localId' => 'option-1', 'answer' => 'Lima', 'position' => 1],
            ['localId' => 'option-2', 'answer' => 'Paris', 'position' => 2],
        ];
        $data->matchingPairs = [
            ['answer' => 'Peru', 'optionLocalId' => 'option-1', 'comment' => '', 'score' => $combination ? 0.0 : 5.0, 'position' => 1],
            ['answer' => 'France', 'optionLocalId' => 'option-2', 'comment' => '', 'score' => $combination ? 0.0 : 5.0, 'position' => 2],
        ];
        $data->score = $combination ? 0.0 : 10.0;
        $data->globalScore = $combination ? 10.0 : 0.0;
    }

    private function configureTrueFalse(ExerciseQuestionEditor $data): void
    {
        $data->answers = [
            [
                'answer' => 'The Earth orbits the Sun.',
                'correct' => false,
                'correctChoice' => 1,
                'comment' => '',
                'score' => 0.0,
                'position' => 1,
                'isUnknown' => false,
            ],
            [
                'answer' => 'The Sun orbits the Earth.',
                'correct' => false,
                'correctChoice' => 2,
                'comment' => '',
                'score' => 0.0,
                'position' => 2,
                'isUnknown' => false,
            ],
        ];
    }

    private function configureHotspot(ExerciseQuestionEditor $data, bool $combination, bool $delineation): void
    {
        $data->hotspotImageData = $this->imageDataUri();
        $data->hotspotImageName = 'chamilo-regression-hotspot.png';
        $data->hotspotImageMimeType = 'image/png';
        $data->score = $combination ? 0.0 : 10.0;
        $data->globalScore = $combination ? 10.0 : 0.0;

        if ($delineation) {
            $data->hotspotItems = [
                [
                    'answer' => 'Regression delineation',
                    'comment' => '',
                    'score' => 10.0,
                    'position' => 1,
                    'hotspotType' => 'delineation',
                    'coordinates' => '40;30|260;30|260;140|40;140',
                    'minOverlap' => 60,
                    'maxExcess' => 20,
                    'maxMissing' => 20,
                ],
            ];

            return;
        }

        $data->hotspotItems = [
            [
                'answer' => 'Regression target',
                'comment' => '',
                'score' => $combination ? 0.0 : 10.0,
                'position' => 1,
                'hotspotType' => 'square',
                'coordinates' => '60;40|120|60',
                'minOverlap' => 1,
                'maxExcess' => 100,
                'maxMissing' => 100,
            ],
        ];
    }

    /**
     * @param list<string> $labels
     * @param list<int>    $correctIndexes
     *
     * @return list<array<string, mixed>>
     */
    private function choiceAnswers(array $labels, array $correctIndexes, float $correctScore): array
    {
        $answers = [];
        foreach ($labels as $index => $label) {
            $isCorrect = \in_array($index, $correctIndexes, true);
            $answers[] = $this->answer(
                $label,
                $isCorrect,
                $isCorrect ? $correctScore : 0.0,
                $index + 1,
            );
        }

        return $answers;
    }

    /**
     * @return array<string, mixed>
     */
    private function answer(string $text, bool $correct, float $score, int $position): array
    {
        return [
            'answer' => $text,
            'correct' => $correct,
            'correctChoice' => 0,
            'comment' => '',
            'score' => $score,
            'position' => $position,
            'isUnknown' => false,
        ];
    }

    private function imageDataUri(): string
    {
        return 'data:image/png;base64,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';
    }

    private function officeTemplateBase64(): string
    {
        return 'UEsDBBQAAAAIAOUGGl3XeYTq8QAAALgBAAATAAAAW0NvbnRlbnRfVHlwZXNdLnhtbH2QzU7DMBCE730Ky9cqccoBIZSkB36OwKE8wMreJFb9J69b2rdn00KREOVozXwz62nXB+/EHjPZGDq5qhspMOhobBg7+b55ru6koALBgIsBO3lEkut+0W6OCUkwHKiTUynpXinSE3qgOiYMrAwxeyj8zKNKoLcworppmlulYygYSlXmDNkvhGgfcYCdK+LpwMr5loyOpHg4e+e6TkJKzmoorKt9ML+Kqq+SmsmThyabaMkGqa6VzOL1jh/0lSfK1qB4g1xewLNRfcRslIl65xmu/0/649o4DFbjhZ/TUo4aiXh77+qL4sGG71+06jR8/wlQSwMEFAAAAAgA5QYaXSAbhuqyAAAALgEAAAsAAABfcmVscy8ucmVsc43Puw6CMBQG4J2naM4uBQdjDIXFmLAafICmPZRGeklbL7y9HRzEODie23fyN93TzOSOIWpnGdRlBQStcFJbxeAynDZ7IDFxK/nsLDJYMELXFs0ZZ57yTZy0jyQjNjKYUvIHSqOY0PBYOo82T0YXDE+5DIp6Lq5cId1W1Y6GTwPagpAVS3rJIPSyBjIsHv/h3ThqgUcnbgZt+vHlayPLPChMDB4uSCrf7TKzQHNKuorZvgBQSwMEFAAAAAgA5QYaXf9YAa3SAAAAJQEAABEAAAB3b3JkL2RvY3VtZW50LnhtbD2PwWrEMAxE7/sVwvfGaQ+lhCR7WOi5lPYDXEebGGzLyEqT/H3twPbymEFoRuqve/Dwi5wdxUE9N60CjJYmF+dBfX+9P70pyGLiZDxFHNSBWV3HS791E9k1YBQoCTF326AWkdRpne2CweSGEsYyuxMHI8XyrDfiKTFZzLkUBK9f2vZVB+OiGi8AJfWHpqPK06SxgCtkvC0mOE/AOHNdpwh3t8vK2MAnJm8sgiwug+AusDlZ4KCVwcS8ITe9riGVfDL9l2S08sH67NePA6p6PDj+AVBLAQIUAxQAAAAIAOUGGl3XeYTq8QAAALgBAAATAAAAAAAAAAAAAACAAQAAAABbQ29udGVudF9UeXBlc10ueG1sUEsBAhQDFAAAAAgA5QYaXSAbhuqyAAAALgEAAAsAAAAAAAAAAAAAAIABIgEAAF9yZWxzLy5yZWxzUEsBAhQDFAAAAAgA5QYaXf9YAa3SAAAAJQEAABEAAAAAAAAAAAAAAIAB/QEAAHdvcmQvZG9jdW1lbnQueG1sUEsFBgAAAAADAAMAuQAAAP4CAAAAAA==';
    }
}
