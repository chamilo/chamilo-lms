<?php

/* For licensing terms, see /license.txt */

use Webit\Util\EvalMath\EvalMath;

/**
 *  Class CalculatedAnswer
 *  Calculated question with random variables and formulas with intermediate results.
 */
class CalculatedAnswer extends Question
{
    public $typePicture = 'calculated_answer.png';
    public $explanationLangVar = 'Calculated question';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = CALCULATED_ANSWER;
        $this->isContent = $this->getIsContent();
    }

    /**
     * Parse l'answer de la BDD pour extraire le wording et les métadonnées
     * Format: "wording@@@=c:a+b:1:digit:2:10;#a:1-10::0;..."
     *
     * @param string $answerText
     * @return array [wording, blankList, formulaList]
     */
    public static function parseCalculatedAnswer(string $answerText): array
    {
        $blankList = [];
        $formulaList = [];
        $parts = explode('@@@', $answerText);
        $wording = $parts[0] ?? '';
        $encodedData = $parts[1] ?? '';

        if (empty($encodedData)) {
            return [$wording, $blankList, $formulaList];
        }

        $items = explode(';', trim($encodedData, ';'));
        foreach ($items as $item) {
            if (empty($item)) {
                continue;
            }

            // Variable: #a:220-230*320-330::0
            if (str_starts_with($item, '#')) {
                $parsed = self::parseBlankData($item);
                if ($parsed) {
                    $blankList[$parsed['name']] = $parsed;
                }
            } elseif (str_starts_with($item, '=')) {
                // Formule: =c:a+b:1:digit:2:10
                $parsed = self::parseFormulaData($item);
                if ($parsed) {
                    $formulaList[$parsed['name']] = $parsed;
                }
            }
        }

        return [$wording, $blankList, $formulaList];
    }

    /**
     * Parse une variable: #a:220-230*320-330::0
     * Format: #nom:intervalles::(vide):decimals
     *
     * @param string $text
     * @return array|null
     */
    private static function parseBlankData(string $text): ?array
    {
        $parts = explode(':', $text);
        if (count($parts) < 4) {
            return null;
        }

        // Backward compatibility with the old "name:min:max:decimals" encoding (parts[2] holds max);
        // the current format leaves parts[2] empty and encodes the whole range in parts[1].
        $intervals = '' !== $parts[2] ? $parts[1].'-'.$parts[2] : $parts[1];

        return [
            'name' => ltrim($parts[0], '#'),
            'intervals' => $intervals, // "220-230*320-330"
            'decimals' => (int) ($parts[3] ?? 0),
        ];
    }

    /**
     * Parse une formule: =c:a+b:1:digit:2:10
     * Format: =nom:formule:tolerance:toleranceType:decimals:score
     *
     * @param string $text
     * @return array|null
     */
    private static function parseFormulaData(string $text): ?array
    {
        $parts = explode(':', $text);
        if (count($parts) < 6) {
            return null;
        }

        return [
            'name' => ltrim($parts[0], '='),
            'formula' => $parts[1],
            'tolerance' => (float) $parts[2],
            'toleranceType' => $parts[3], // 'digit' ou 'percent'
            'decimals' => (int) $parts[4],
            'score' => (float) $parts[5],
        ];
    }

    /**
     * Génère un nombre aléatoire entre min et max avec X décimales
     *
     * @param float $min Valeur minimale
     * @param float $max Valeur maximale
     * @param int $decimals Nombre de décimales
     * @return float
     */
    private static function generateRandomValue(float $min, float $max, int $decimals): float
    {
        // Si pas de décimales, générer un entier
        if (0 === $decimals) {
            return (float) random_int((int) $min, (int) $max);
        }

        $random = lcg_value(); // Retourne float entre 0 et 1
        $value = $min + ($random * ($max - $min));

        return round($value, $decimals);
    }

    /**
     * Retourne l'HTML de correction pour un étudiant
     *
     * @param string $studentAnswerDb
     * @param Answer $objectAnswer
     * @param int $questionId
     * @param bool $resultDisabled
     * @param bool $csv
     * @param bool $showTotalScoreAndUserChoices
     * @return string
     */
    public static function getHtmlCorrectionForStudentAttempt(
        string $studentAnswerDb,
        Answer $objectAnswer,
        int $questionId,
        bool $resultDisabled = false,
        bool $csv = false,
        bool $showTotalScoreAndUserChoices = false
    ): string {
        // $isCorrection = true pour obtenir les corrections
        $wordingAnswer = self::getStudentExamView(
            $studentAnswerDb,
            $objectAnswer,
            $questionId,
            true,
            $resultDisabled,
            $csv,
            $showTotalScoreAndUserChoices
        );

        return self::improveWordingDisplay($wordingAnswer);
    }

    /**
     * Améliore l'affichage du wording (ex: corrige les signes + -)
     *
     * @param string $wording
     * @return string
     */
    public static function improveWordingDisplay(string $wording)
    {
        return preg_replace('/\+ *\-/', '-', $wording);
    }

    /**
     * Parse un intervalle simple : "1-10" ou "-5--2"
     * Retourne [min, max]
     *
     * @param string $interval
     * @return array [float, float]
     * @throws InvalidArgumentException
     */
    private static function parseSimpleInterval(string $interval): array
    {
        $parts = preg_split('/(?<!^)-(?!$)/', trim($interval), 2);
        if (2 !== count($parts)) {
            throw new InvalidArgumentException("Intervalle invalide : $interval");
        }

        return [
            (float) trim($parts[0]),
            (float) trim($parts[1]),
        ];
    }

    /**
     * Génère une valeur aléatoire depuis une définition d'intervalles
     * Format acceptés :
     * - "42" : valeur fixe
     * - "1-10" : intervalle simple
     * - "220|330|440" : choix dans une liste de nombres
     * - "1-10|2" : intervalle avec pas de 2
     * - "1-10*20-30*40-50" : intervalles multiples (stockage interne)
     *
     * @param string $intervals Définition des intervalles
     * @param int $decimals Nombre de décimales
     * @return float Valeur générée
     */
    public static function generateFromIntervals(string $intervals, int $decimals, array $calculatedValues = []): float
    {
        if (is_numeric($intervals)) {
            return round((float) $intervals, $decimals);
        }

        // Si c'est une référence à une variable ou formule déjà calculée
        if (isset($calculatedValues[$intervals])) {
            return round((float) $calculatedValues[$intervals], $decimals);
        }

        $intervalList = explode('*', $intervals);
        $chosenInterval = $intervalList[array_rand($intervalList)];

        if (isset($calculatedValues[$chosenInterval])) {
            return round((float) $calculatedValues[$chosenInterval], $decimals);
        }
        if (is_numeric($chosenInterval)) {
            return round((float) $chosenInterval, $decimals);
        }

        if (str_contains($chosenInterval, '|')) {
            $parts = explode('|', $chosenInterval);
            $allNumeric = true;
            foreach ($parts as $part) {
                if (!is_numeric(trim($part))) {
                    $allNumeric = false;
                    break;
                }
            }

            if ($allNumeric) {
                $randomValue = $parts[array_rand($parts)];

                return round((float) $randomValue, $decimals);
            }

            // Sinon, c'est un intervalle avec pas (ex: 1-10|2)
            [$chosenInterval, $step] = explode('|', $chosenInterval, 2);
            $step = (float) $step;

            [$min, $max] = self::parseSimpleInterval($chosenInterval);
            if ($min > $max) {
                [$min, $max] = [$max, $min];
            }

            if ($step > 0) {
                $factor = 10 ** $decimals;
                $minI = (int) round($min * $factor);
                $maxI = (int) round($max * $factor);
                $stepI = max(1, (int) round($step * $factor));

                $steps = floor(($maxI - $minI) / $stepI);
                $rand = random_int(0, (int) $steps);

                return round(($minI + $rand * $stepI) / $factor, $decimals);
            }

            return self::generateRandomValueWithStep($min, $max, $step);
        }

        [$min, $max] = self::parseSimpleInterval($chosenInterval);
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        return self::generateRandomValue($min, $max, $decimals);
    }

    /**
     * Génère une valeur aléatoire avec un pas défini
     *
     * @param float $min Valeur minimale
     * @param float $max Valeur maximale
     * @param float $step Pas entre les valeurs
     * @return float Valeur générée
     */
    private static function generateRandomValueWithStep(float $min, float $max, float $step): float
    {
        $steps = floor(($max - $min) / $step);
        $randomStep = random_int(0, (int) $steps);

        return $min + ($randomStep * $step);
    }

    /**
     * Return formula result with tolerance
     */
    public static function calculateFormula(
        string $formula,
        float $toleranceValue,
        string $toleranceType,
        int $digitNumber
    ): array {
        $math = new EvalMath();

        // Correction pour le log
        $formula = preg_replace('/log\(([^)]+)\)/', '(ln($1)/ln(10))', $formula);

        // Évaluer la formule
        ob_start();
        $result = $math->evaluate($formula);
        ob_end_clean();

        // Si result = 0, EvalMath retourne false
        if (false === $result) {
            $result = 0;
        }

        if ('' === $result) {
            return [];
        }

        $resultMin = $result;
        $resultMax = $result;

        // Calculer la tolérance
        if ($toleranceValue > 0) {
            if ('digit' == $toleranceType) {
                $resultMin = $result - $toleranceValue;
                $resultMax = $result + $toleranceValue;
            } elseif ('percent' == $toleranceType) {
                $resultMin = $result - (abs($result) * $toleranceValue / 100);
                $resultMax = $result + (abs($result) * $toleranceValue / 100);
            }
        }

        // Ordonner min/max
        if ($resultMin > $resultMax) {
            $temp = $resultMin;
            $resultMin = $resultMax;
            $resultMax = $temp;
        }

        // Vérifier validité
        if (!is_numeric($result) || !is_numeric($resultMin) || !is_numeric($resultMax)) {
            return ['error', 'error', 'error'];
        }

        // Arrondir
        $result = round($result, $digitNumber);
        $resultMin = round($resultMin, $digitNumber);
        $resultMax = round($resultMax, $digitNumber);

        return [$result, $resultMin, $resultMax];
    }

    /**
     * @param FormValidator $form
     * @return void
     * @throws Exception
     */
    public function createAnswersForm($form)
    {
        $defaults = [];

        echo api_get_js('calculated_answer/calculated_answer.js');

        // Si on édite une question existante
        if (!empty($this->id)) {
            $objAnswer = new Answer($this->id);
            $answerText = $objAnswer->selectAnswer(1);

            [$wording, $blankList, $formulaList] = self::parseCalculatedAnswer($answerText);

            $defaults['answer'] = $wording;
            $defaults['weighting'] = $this->weighting;

            // Convertir le format BDD (*) en format utilisateur (;) pour l'affichage
            foreach ($blankList as $name => $data) {
                $blankList[$name]['intervals'] = str_replace('*', '; ', $data['intervals']);
            }

            // Préparer les données pour JavaScript
            echo '<script>';
            echo 'if (typeof CalculatedAnswerManager !== "undefined") {';
            echo '  CalculatedAnswerManager.initData('.json_encode($blankList).', '.json_encode($formulaList).');';
            echo '}';
            echo '</script>';
        }

        // Instructions
        $form->addElement(
            'label',
            null,
            '<div class="alert alert-info">'.
            get_lang("<h4>How to create a calculated question:</h4><ul><li><strong>[#variable]</strong> : Random variable (e.g., [#a], [#price])</li><li><strong>[=formula]</strong> : Result to calculate (e.g., [=result], [=total])</li></ul><p><strong>Ranges:</strong></p><ul><li><code>1-10</code> : simple range from 1 to 10</li><li><code>220|330|440</code> : random choice between 220, 330, or 440</li><li><code>1-10|2</code> : from 1 to 10 with a step of 2</li><li><code>1-10; 20-30; 40-50</code> : selects from one of the 3 ranges</li><li><code>-5--2</code> : negative numbers (from -5 to -2)</li></ul><p><strong>Tolerance:</strong> Accepted margin of error</p><ul><li><code>±</code> (digit) : absolute tolerance (e.g., ±0.5)</li><li><code>%</code> (percent) : percentage tolerance (e.g., 5%)</li></ul>").
            '</div>'
        );

        // Éditeur de texte
        $form->addElement(
            'html_editor',
            'answer',
            '',
            ['id' => 'answer'],
            ['ToolbarSet' => 'TestQuestionDescription', 'Width' => '100%', 'Height' => '350']
        );

        $form->addRule('answer', get_lang('Please type the text'), 'required');

        $form->addElement('html', '<div id="blanks_container"></div>');
        $form->addElement('html', '<div style="margin-top: 30px;"></div>');
        $form->addElement('html', '<div id="formulas_container"></div>');

        // Espacement avant le bouton Formula notation
        $form->addElement('html', '<div style="margin-top: 30px;"></div>');

        $notationButton = Display::url(
            get_lang('Formula notation'),
            api_get_path(WEB_CODE_PATH).'exercise/evalmathnotation.php',
            ['class' => 'btn btn--info ajax', 'data-title' => get_lang('Formula notation'), 'target' => '_blank']
        );
        $form->addElement('label', null, $notationButton);

        $ajaxUrl = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq();
        $form->addElement(
            'label',
            null,
            '<button type="button" class="btn btn--info" onclick="CalculatedAnswerManager.testFormulas(\''.$ajaxUrl.'\')">'
            .get_lang('Test').
            '</button>
                        <div id="testArea"></div>'
        );

        global $text;
        $form->addButtonSave($text, 'submitQuestion');

        if (!empty($this->id)) {
            $form->setDefaults($defaults);
        } elseif (1 == $this->isContent) {
            $form->setDefaults($defaults);
        } else {
            // add a default name to the question
            // if no default name and teacher click on Save Question,
            // page is reloaded and data in fields formula etn variables are deleted
            $form->addHtml('<script>$("#question_admin_form_questionName").val("'.get_lang('Calculated question').'_'.time().'");</script>');
        }
    }

    public function processAnswersCreation($form, $exercise)
    {
        $table = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $submitValues = $form->getSubmitValues();
        $wording = $submitValues['answer'];
        $encodedData = '';
        $totalScore = 0;

        foreach ($submitValues as $key => $value) {
            if (str_starts_with($key, 'formula_expression_')) {
                $name = str_replace('formula_expression_', '', $key);
                $formula = $value;
                $tolerance = (float) ($submitValues["formula_tolerance_$name"] ?? 0);
                $toleranceType = $submitValues["formula_tolerancetype_$name"] ?? 'digit';
                $decimals = (int) ($submitValues["formula_decimals_$name"] ?? 2);
                $score = (float) ($submitValues["formula_score_$name"] ?? 10);
                $encodedData .= "=$name:$formula:$tolerance:$toleranceType:$decimals:$score;";
                $totalScore += $score;
            }
        }

        foreach ($submitValues as $key => $value) {
            if (str_starts_with($key, 'blank_intervals_')) {
                $name = str_replace('blank_intervals_', '', $key);
                $intervals = str_replace(',', '.', $value);
                $intervals = str_replace(';', '*', $intervals);
                $decimals = (int) ($submitValues["blank_decimals_$name"] ?? 0);
                $encodedData .= "#$name:$intervals::$decimals;";
            }
        }

        $finalAnswer = $wording.'@@@'.$encodedData;

        $this->weighting = $totalScore;
        $this->save($exercise);

        Database::delete($table, ['question_id = ?' => [$this->id]]);

        $objAnswer = new Answer($this->id, 0, $exercise, false);
        $objAnswer->createAnswer(
            $finalAnswer,
            1,
            '',
            $this->weighting,
            1
        );
        $objAnswer->save();
    }

    /**
     * @param Exercise $exercise
     * @param $counter
     * @param $score
     * @return string
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->questionTableClass.'"><tr>';
        $header .= '<th>'.get_lang('Answer').'</th>';
        if ($exercise->showExpectedChoice()) {
            $header .= '<th>'.get_lang('Your choice').'</th>';
            if ($exercise->showExpectedChoiceColumn()) {
                $header .= '<th>'.get_lang('Expected choice').'</th>';
            }
            $header .= '<th class="text-center">'.get_lang('Status').'</th>';
        }
        $header .= '</tr>';

        return $header;
    }

    /**
     * @param string $answer raw answer text from BDD
     * @return string wording HTML with variables # and = replaced
     */
    public static function getQuestionWordingForTeacherPreview(string $answer): string
    {
        [$wording, $blanksList, $formulaList] = self::parseCalculatedAnswer($answer);

        // Replace variable [#a], [#b], etc.
        foreach ($blanksList as $blankInfo) {
            $name = $blankInfo['name'];
            $intervals = str_replace('*', '; ', $blankInfo['intervals']);
            $decimals = $blankInfo['decimals'];

            $tooltip = "$name ∈ {$intervals}";
            if ($decimals > 0) {
                $tooltip .= " (déc: $decimals)";
            }

            $wording = api_preg_replace(
                '/\[#'.$name.'\]/',
                "<span class='text-primary calc-tooltip' title=\"".htmlspecialchars($tooltip, ENT_QUOTES)."\">$name</span>",
                $wording
            );
        }

        // Replace formaula [=result], [=total], etc.
        foreach ($formulaList as $formulaInfo) {
            $name = $formulaInfo['name'];
            $formula = $formulaInfo['formula'];
            $tolerance = $formulaInfo['tolerance'];
            $toleranceType = $formulaInfo['toleranceType'];
            $decimals = $formulaInfo['decimals'];
            $score = $formulaInfo['score'];

            $toleranceLabel = self::getToleranceLabel($tolerance, $toleranceType);
            $title = sprintf(get_lang('Tolerance: %s'), $toleranceLabel).' | ';
            $title .= sprintf(get_lang('Decimals: %s'), $decimals).' | ';
            $title .= sprintf(get_lang('Score: %s'), $score);

            $formulaDisplay = self::addJs()."<input type='text' value='$name = $formula' disabled style='background:white; width:auto;'> ".
                '<span class="tooltip_calculated_answer" title="'.$title.'">[?]</span>';

            $wording = api_preg_replace(
                '/\[='.$name.'\]/',
                $formulaDisplay,
                $wording
            );
        }

        return $wording;
    }

    /**
     * @param float $tolerance
     * @param string $toleranceType
     * @return string
     */
    private static function getToleranceLabel(float $tolerance, string $toleranceType): string
    {
        if (0 == $tolerance) {
            return get_lang('None');
        }

        if ('percent' === $toleranceType) {
            return "± {$tolerance}%";
        }

        return "± {$tolerance}";
    }

    /**
     * @param string $answer
     * @return array
     */
    public static function getEditorPart(string $answer): array
    {
        $parts = explode('@@@', $answer);

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    /**
     * @param string $encodedAnswer
     * @return array[]
     */
    public static function parseStudentAnswerData(string $encodedAnswer): array
    {
        $blankStudents = [];
        $formulaStudents = [];

        $items = explode(';', trim($encodedAnswer, ';'));
        foreach ($items as $item) {
            if (empty($item)) {
                continue;
            }

            if (str_starts_with($item, '#')) {
                $parts = explode(':', $item);
                $name = ltrim($parts[0], '#');
                $value = (float) $parts[1];
                $blankStudents[$name] = $value;
            } elseif (str_starts_with($item, '=')) {
                // Formule: =res:9:8:7.5:8.5:1
                $parts = explode(':', $item);
                $name = ltrim($parts[0], '=');
                $formulaStudents[$name] = [
                    'name' => $name,
                    'studentAnswer' => $parts[1] ?? '?',
                    'goodAnswer' => (float) ($parts[2] ?? 0),
                    'min' => (float) ($parts[3] ?? 0),
                    'max' => (float) ($parts[4] ?? 0),
                    'correct' => (int) ($parts[5] ?? 0),
                ];
            }
        }

        return [$blankStudents, $formulaStudents];
    }

    /**
     * @param Answer $answer
     * @param int $questionId
     * @param array $studentAnswers
     * @param array $blanks
     * @return array
     */
    public static function getFirstStudentExamView(Answer $answer, int $questionId, array $studentAnswers = [], array $blanks = []): array
    {
        // Récupérer la définition de la question
        $answerText = $answer->selectAnswer(1);
        [$wording, $blanksList, $formulaList] = self::parseCalculatedAnswer($answerText);

        $unknownValues = [];
        $unknownValuesLow = [];
        $unknownValuesHigh = [];

        // Générer ou récupérer les valeurs aléatoires pour les variables
        foreach ($blanksList as $name => $blankInfo) {
            if (isset($blanks[$name])) {
                // Réutiliser valeur déjà générée
                $randomValue = $blanks[$name];
            } else {
                // Générer nouvelle valeur aléatoire
                $randomValue = self::generateFromIntervals(
                    $blankInfo['intervals'],
                    $blankInfo['decimals']
                );
            }

            $unknownValues[$name] = $randomValue;

            // Remplacer [#a] par la valeur dans le wording
            $wording = api_preg_replace(
                '/\[#'.preg_quote($name, '/').'\]/',
                $randomValue,
                $wording
            );
        }

        // $wording contient maintenant "5 + 3 = [=res]"
        $wordingHtml = $wording;

        // Calculer les formules et créer les inputs
        foreach ($formulaList as $formulaInfo) {
            $formulaName = $formulaInfo['name'];

            // Valeur pré-remplie si fournie
            $prefilledValue = $studentAnswers[$formulaName] ?? '';

            // Créer l'input HTML
            $formulaContentHtml = self::getHtmlFormula($formulaInfo, $questionId, $prefilledValue);

            // Remplacer [=res] par l'input
            $wordingHtml = api_preg_replace(
                '/\[='.preg_quote($formulaName, '/').'\]/',
                $formulaContentHtml,
                $wordingHtml
            );

            // Calculer la formule avec les valeurs générées
            $formulaInstance = ' '.$formulaInfo['formula'].' ';

            foreach ($unknownValues as $unknownName => $unknownValue) {
                // Remplacer 'a' par 5 dans "a+b"
                $formulaInstance = api_preg_replace(
                    '/\b'.preg_quote($unknownName, '/').'\b/',
                    " $unknownValue ",
                    $formulaInstance
                );
            }

            // Calculer avec tolérance
            $results = self::calculateFormula(
                $formulaInstance,
                $formulaInfo['tolerance'],
                $formulaInfo['toleranceType'],
                $formulaInfo['decimals']
            );

            $unknownValues[$formulaName] = $results[0];      // Résultat exact
            $unknownValuesLow[$formulaName] = $results[1];   // Min
            $unknownValuesHigh[$formulaName] = $results[2];  // Max
        }

        // Format: "HTML@@@#a:5;#b:3;=res:?:8:7.5:8.5:0;"
        $studentInstanceData = $wordingHtml.'@@@';

        foreach ($blanksList as $name => $blankInfo) {
            $studentInstanceData .= '#'.$name.':'.$unknownValues[$name].';';
        }

        foreach ($formulaList as $formulaInfo) {
            $studentInstanceData .= '='.$formulaInfo['name'].':';

            if (isset($studentAnswers[$formulaInfo['name']])) {
                $studentInstanceData .= $studentAnswers[$formulaInfo['name']].':';
            } else {
                $studentInstanceData .= '?:';
            }

            $studentInstanceData .= $unknownValues[$formulaInfo['name']].':';
            $studentInstanceData .= $unknownValuesLow[$formulaInfo['name']].':';
            $studentInstanceData .= $unknownValuesHigh[$formulaInfo['name']].':';
            $studentInstanceData .= '0;';
        }

        return [$wordingHtml, $studentInstanceData];
    }

    /**
     * @return string
     */
    public static function addJs(): string
    {
        return '
        <style>
            .tooltip_calculated_answer_css {
                background-color: white;
                border : 1px solid black;
                box-shadow: 0 4px 16px rgba(0,0,0,0.35);
                border-radius: 8px;
                padding: 10px;
                width: auto;
                max-width: none;
                display: inline-block;
            }

        </style>
        <script>
            $(function() {
                $(".tooltip_calculated_answer").tooltip({
                    classes: {
                         "ui-tooltip": "tooltip_calculated_answer_css"
                    },
                    position: { my: "left top", at: "left bottom"}
                })
            });
        </script>';
    }

    /**
     * @param array $formulaInfo
     * @param int $questionId
     * @param string $value
     * @return string
     */
    public static function getHtmlFormula(array $formulaInfo, int $questionId, string $value = ''): string
    {
        $tolerance = $formulaInfo['tolerance'];
        $toleranceType = $formulaInfo['toleranceType'];
        $decimals = $formulaInfo['decimals'];
        $score = $formulaInfo['score'];

        $toleranceLabel = self::getToleranceLabel($tolerance, $toleranceType);
        $input = Display::input(
            'text',
            "choice[$questionId][]",
            $value,
            [
                'class' => 'form-control calculated-answer-input',
                'style' => 'display:inline-block; width:auto; min-width:100px;',
                'onkeyup' => 'checkStudentInput($(this), '.$decimals.');',
            ]
        );

        $title = sprintf(get_lang('Tolerance: %s'), $toleranceLabel).' | ';
        $title .= sprintf(get_lang('Decimals: %s'), $decimals).' | ';
        $title .= sprintf(get_lang('Score: %s'), $score);

        return self::addJs().$input.' '.'<span class="tooltip_calculated_answer" title="'.$title.'">[?]</span>';
    }

    /**
     * @param string $studentAnswerDb
     * @param Answer $objectAnswer
     * @param int $questionId
     * @param bool $isCorrection
     * @param bool $resultDisabled
     * @param bool $csv
     * @param bool $showTotalScoreAndUserChoices
     * @return string
     */
    public static function getStudentExamView(
        string $studentAnswerDb,
        Answer $objectAnswer,
        int $questionId,
        bool $isCorrection = false,
        bool $resultDisabled = false,
        bool $csv = false,
        bool $showTotalScoreAndUserChoices = false
    ): string {
        [, $encodedAnswer] = self::getEditorPart($studentAnswerDb);
        [$blankStudents, $formulaStudents] = self::parseStudentAnswerData($encodedAnswer);

        $answerText = $objectAnswer->selectAnswer(1);
        [$wordingAnswer, , $formulasAnswer] = self::parseCalculatedAnswer($answerText);

        if ($isCorrection) {
            $formulaHtml = [];
            foreach ($formulaStudents as $formulaName => $formulaInfo) {
                $rootStyle = 'border:1px solid black; border-radius:5px; padding:2px; font-weight:bold;';
                $styleCorrect = 'color: green;';
                $answerStyle = $formulaInfo['correct'] ? $styleCorrect : 'color: red; text-decoration: line-through;';

                if ($resultDisabled) {
                    $answerStyle = 'color: black;';
                }

                $tolerance = $formulasAnswer[$formulaName]['tolerance'] > 0
                    ? '( '.sprintf(get_lang('Tolerance: %s'), self::getToleranceLabel(
                        $formulasAnswer[$formulaName]['tolerance'],
                        $formulasAnswer[$formulaName]['toleranceType']
                    ))
                    : '';

                $formulaCorrection = "<span style='$rootStyle'>";
                $formulaCorrection .= "<span style='$answerStyle'>{$formulaInfo['studentAnswer']}</span> / ";
                $formulaCorrection .= "<span style='color: green;'>{$formulaInfo['goodAnswer']}</span>";
                $formulaCorrection .= "<span style='color:black; font-weight: normal;'>$tolerance</span>";
                $formulaCorrection .= '</span>';

                // CSV
                if ($csv) {
                    $formulaCorrection = "={$formulaInfo['studentAnswer']} (correct={$formulaInfo['goodAnswer']}) ";
                }

                $formulaHtml[$formulaName] = $formulaCorrection;
            }
        } else {
            $formulaHtml = [];
            foreach ($formulaStudents as $formulaName => $formulaInfo) {
                $value = '?' !== $formulaInfo['studentAnswer'] ? $formulaInfo['studentAnswer'] : '';
                $formulaHtml[$formulaName] = self::getHtmlFormula($formulasAnswer[$formulaName], $questionId, $value);
            }
        }

        $wordingAnswer = self::replaceBlanksWithValue($wordingAnswer, $blankStudents);
        $wordingAnswer = self::replaceFormulaWithValue($wordingAnswer, $formulaHtml);

        return self::improveWordingDisplay($wordingAnswer);
    }

    /**
     * @param string $text
     * @param array $blanks
     * @return string
     */
    public static function replaceBlanksWithValue(string $text, array $blanks): string
    {
        foreach ($blanks as $blankName => $blankValue) {
            $text = api_preg_replace('/\[#'.$blankName.'\]/', $blankValue, $text);
        }

        return $text;
    }

    /**
     * @param string $text
     * @param array $formulas
     * @return string
     */
    public static function replaceFormulaWithValue(string $text, array $formulas): string
    {
        foreach ($formulas as $formulaName => $formulaValue) {
            $text = api_preg_replace('/\[='.$formulaName.'\]/', $formulaValue, $text);
        }

        return $text;
    }

    /**
     * @param string $studentAnswerDb
     * @return array
     */
    public static function getStudentChoices(string $studentAnswerDb): array
    {
        $result = [];

        [, $encodedAnswer] = self::getEditorPart($studentAnswerDb);
        [, $formulaStudents] = self::parseStudentAnswerData($encodedAnswer);

        // Extraire les réponses des formules
        foreach ($formulaStudents as $formulaInfos) {
            if ('?' != $formulaInfos['studentAnswer'] && '' != $formulaInfos['studentAnswer']) {
                $result[] = $formulaInfos['studentAnswer'];
            } else {
                $result[] = '';
            }
        }

        return $result;
    }

    /**
     * @param int $questionId
     * @param string $answer
     * @return string
     */
    public static function replaceFormuleAfterModification(int $questionId, string $answer)
    {
        // Récupérer la définition actuelle de la question
        $myAnswer = new Answer($questionId);
        $answerText = $myAnswer->selectAnswer(1);
        [$wording, $blanksList, $formulaList] = self::parseCalculatedAnswer($answerText);

        // Extraire les données de la tentative de l'étudiant
        [$wordingHtml, $encodedAnswer] = self::getEditorPart($answer);
        [$blankStudents, $formulaStudents] = self::parseStudentAnswerData($encodedAnswer);

        // Récupérer les anciennes réponses de l'étudiant
        $studentAnswers = [];
        foreach ($formulaStudents as $blankDatas) {
            $studentAnswers[$blankDatas['name']] = $blankDatas['studentAnswer'];
        }

        $unknownValues = [];
        $unknownValuesLow = [];
        $unknownValuesHigh = [];

        // Garder les mêmes valeurs aléatoires pour les variables
        foreach ($blanksList as $name => $blankInfo) {
            if (isset($blankStudents[$name])) {
                $randomValue = $blankStudents[$name];
            } else {
                // Si nouvelle variable, générer une valeur
                $randomValue = self::generateFromIntervals(
                    $blankInfo['intervals'],
                    $blankInfo['decimals']
                );
            }

            $unknownValues[$name] = $randomValue;

            // Remplacer [#a] par la valeur
            $wording = api_preg_replace(
                '/\[#'.preg_quote($name, '/').'\]/',
                $randomValue,
                $wording
            );
        }

        // Recalculer les formules
        foreach ($formulaList as $formulaInfo) {
            $formulaName = $formulaInfo['name'];

            // Créer l'input HTML
            $formulaContentHtml = self::getHtmlFormula($formulaInfo, $questionId);

            // Remplacer [=res] par l'input
            $wordingHtml = api_preg_replace(
                '/\[='.preg_quote($formulaName, '/').'\]/',
                $formulaContentHtml,
                $wordingHtml
            );

            // Calculer la formule
            $formulaInstance = ' '.$formulaInfo['formula'].' ';

            foreach ($unknownValues as $unknownName => $unknownValue) {
                $formulaInstance = api_preg_replace(
                    '/\b'.preg_quote($unknownName, '/').'\b/',
                    " $unknownValue ",
                    $formulaInstance
                );
            }

            $results = self::calculateFormula(
                $formulaInstance,
                $formulaInfo['tolerance'],
                $formulaInfo['toleranceType'],
                $formulaInfo['decimals']
            );

            $unknownValues[$formulaName] = $results[0];
            $unknownValuesLow[$formulaName] = $results[1];
            $unknownValuesHigh[$formulaName] = $results[2];
        }

        // Construire les données à sauvegarder
        $studentInstanceData = $wordingHtml.'@@@';

        foreach ($blanksList as $name => $blankInfo) {
            $studentInstanceData .= '#'.$name.':'.$unknownValues[$name].';';
        }

        foreach ($formulaList as $formulaInfo) {
            $studentInstanceData .= '='.$formulaInfo['name'].':';

            if (isset($studentAnswers[$formulaInfo['name']])) {
                $studentInstanceData .= $studentAnswers[$formulaInfo['name']].':';
            } else {
                $studentInstanceData .= '?:';
            }

            $studentInstanceData .= $unknownValues[$formulaInfo['name']].':';
            $studentInstanceData .= $unknownValuesLow[$formulaInfo['name']].':';
            $studentInstanceData .= $unknownValuesHigh[$formulaInfo['name']].':';
            $studentInstanceData .= '0;';
        }

        return $studentInstanceData;
    }

    /**
     * @param string $studentAnswerDb
     * @param array $choices
     * @return string
     */
    public static function getStudentAnswerFromChoice(string $studentAnswerDb, array $choices): string
    {
        $result = '';
        [$wordingHtml, $encodedAnswer] = self::getEditorPart($studentAnswerDb);

        $datas = explode(';', trim($encodedAnswer, ';'));

        $i = 0;
        foreach ($datas as $oneDataInfo) {
            if (empty($oneDataInfo)) {
                continue;
            }

            // Variables : #a:5
            if (preg_match('/^(#[^:]+:.*)/', $oneDataInfo, $matches)) {
                $result .= $matches[1].';';
            } elseif (preg_match('/^(=[^:]+):[^:]*:([^:]+:[^:]+:[^:]+):[^:]+/', $oneDataInfo, $matches)) {
                // Formules : =res:?:8:7.5:8.5:0
                $choiceForDb = isset($choices[$i]) ? trim($choices[$i]) : '?';

                // Nettoyer la réponse
                if (strlen($choiceForDb) > 0) {
                    // Enlever point final
                    if ('.' === $choiceForDb[strlen($choiceForDb) - 1]) {
                        $choiceForDb = substr($choiceForDb, 0, -1);
                    }
                    // Ajouter 0 devant .
                    if ('.' === $choiceForDb[0]) {
                        $choiceForDb = '0'.$choiceForDb;
                    }
                    // Gérer -.
                    if (strlen($choiceForDb) > 1 && '-' === $choiceForDb[0] && '.' === $choiceForDb[1]) {
                        $choiceForDb = '-0'.substr($choiceForDb, 1);
                    }
                }

                // Vérifier si c'est un nombre
                if (!is_numeric($choiceForDb) && '' !== $choiceForDb) {
                    $choiceForDb = '?';
                }

                // Vérifier si correct
                $isCorrectAnswer = self::isCorrectAnswer($oneDataInfo, $choiceForDb);

                $result .= $matches[1].":$choiceForDb:".$matches[2].":$isCorrectAnswer;";
                $i++;
            }
        }

        return $wordingHtml.'@@@'.$result;
    }

    private static function isCorrectAnswer($oneDataInfo, $studentAnswer)
    {
        if ('?' === $studentAnswer || '' === $studentAnswer) {
            return 0;
        }

        // Parser la ligne : =res:?:8:7.5:8.5:0
        $parts = explode(':', $oneDataInfo);

        if (count($parts) < 5) {
            return 0;
        }

        $min = (float) $parts[3];
        $max = (float) $parts[4];
        $value = (float) $studentAnswer;

        return ($value >= $min && $value <= $max) ? 1 : 0;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isAnswered(): bool
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $result = Database::select(
            'question_id',
            $table,
            ['where' => ['question_id = ?' => [$this->id]]]
        );

        return !empty($result);
    }
}
