<?php

use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;

/**
 * @param $questionEntity
 * @return bool
 */
function isLegacyCalculatedAnswer($questionEntity) : bool
{
    if (!$questionEntity->getType() == CALCULATED_ANSWER) {
        return false;
    }

    $answers = $questionEntity->getAnswers();

    if (count($answers) === 0) {
        return false;
    }

    return preg_match('/[^@]@@[^@]+$/', $answers[0]->getAnswer());
}

/**
 * migrate first version calculated question to new version
 * old version :
 * <p>et 3.67 + 12 et 11.79+19.2 123</p><p>&nbsp;</p> [30.99]@@[a]+[b]
 *
 * new version :
 * <p>[#a]+[#b]=[=c]</p>*@@@=c:a+b:0:percent:2:10;#a:1-10::0;#b:1-10::0;
 * @@@=c:a+b:12:percent:2:10;#a:1-10::3;#b:1-10::4;
 *
 * @param $quizId
 * @return null
 */
function migrateCalculatedAnswer(): void
{
    $em = Database::getManager();

    /** @var CQuizQuestion|null $questionEntity */
    $questionEntities = $em->getRepository(CQuizQuestion::class)->findBy(['type' => CALCULATED_ANSWER]);

    foreach ($questionEntities as $questionEntity) {
        if (!isLegacyCalculatedAnswer($questionEntity)) {
            continue;
        }
        $answers = $questionEntity->getAnswers();

        $lines = [];
        foreach ($answers as $answer) {
            $lines[] = $answer->getAnswer();
        }
        $newWording = getMigratedValue($lines, $questionEntity->getPonderation());

        $answers[0]->setAnswer($newWording);

        for ($i=1; $i < count($answers); $i++) {
            $em->remove($answers[$i]);
        }

        $em->flush();
    }
}

/**
 * @param $lines
 * @param $score
 * @return string
 */
function getMigratedValue($lines, $score = 10): string
{
    $constantFloats = [];

    $parts = parseFullLine($lines[0]);

    if (count($parts) !== 4) {
        return '';
    }
    $wording = sanitizeWording($parts[1]);
    $result = $parts[2];
    $formula = $parts[3];

    // check if number of float = number of variable in formula

    $vars = array_values(getVariables($formula));

    $floatTemplates = getFloat($wording);
    $allFloats = getFloats($lines);
    $equalities = [];

    if (count($vars) !== count($floatTemplates)) {
        // if some floats are the same in all lines of the answers
        $tempEqs = $allFloats[0];

        foreach ($allFloats as $floats) {
            foreach ($floats as $index => $float) {
                if ($float === $allFloats[0][$index] && $tempEqs[$index]) {
                    $tempEqs[$index] = true;
                } else {
                    $tempEqs[$index] = false;
                }
            }
        }

        foreach ($tempEqs as $index => $tempEq) {
            if ($tempEq == 1) {
                $equalities[$index] = $floatTemplates[$index];
            }
        }

        if (count($floatTemplates) - count($equalities) == count($vars)) {
            $constantFloats = $equalities;
        } else if (count($floatTemplates) - count($equalities) < count($vars)) {
            // some equalities are variables..;
            // we cannot know which one is variable, so pick the firsts
            for ($i = 0; $i < count($equalities) - (count($floatTemplates) - count($vars)); $i++) {
                $constantFloats[array_key_first($equalities)] = array_shift($equalities);
            }
        } else {
            // problem... we got more differentes floats than variables
            // should not happened
            // create missing variables in case 1 et 2 [2]@@[a]+1
            $nbvar = count($floatTemplates) - count($vars);

            if ($nbvar > 0) {
                for ($i = 0; $i < $nbvar; $i++) {
                    $addVar = 'z';
                    while (in_array($addVar, $vars)) {
                        $addVar .= 'z';
                    }
                    $vars[] = $addVar;
                }
            }
        }
    }

    $allFloats = getFloatRemovingEqualities($allFloats, $equalities);

    $wording = replaceFloatsInWording($wording, $vars, $constantFloats);


    // replace ###minus### with the correct literal value
    $wording = preg_replace('/###minus ###/', '- ', $wording);

    // replace ###constant...### with value
    foreach ($constantFloats as $constantFloat) {
        $wording = preg_replace_callback(
            '/###constant(\d+)###/',
            function ($matches) use ($constantFloats) {
                return $constantFloats[$matches[1]];
            }, $wording
        );
    }

    // add formula part
    // @@@=Ord:valA*Abs+valB:0:percent:2:1;#Abs:-10-10::0;#valA:-10-10::0;#valB:0-10::0;
    // @@@=
    //     Ord:                formula result name
    //     valA*Abs+valB:      formula to calculate Ord using math and variable
    //     0:                  decimal number for Ord
    //     percent:            tolerance type [percent|digit]
    //     2:                  tolerance value
    //     1                   score for good answer
    //     ;                   variable block separated with ;
    //     #Abs:               variable Abs
    //     -10-10::0;          interval min-max (dash-joined) :: decimal number
    //     #valA:              variable valA
    //     -10-10::0;          interval min-max (dash-joined) :: decimal number
    //     #valB:              variable valB
    //     0-10::0             interval min-max (dash-joined) :: decimal number
    //     ;
    //

    $resName = 'result';
    while (in_array($resName, $vars)) {
        $resName .= 'a';
    }
    $dbString = $wording . ' = [=' . $resName . ']@@@';
    $dbString .= '=' . $resName . ':';

    // formula
    $dbString .= getMigratedFormula($formula, $vars) . ':';
    $dbString .= '0:digit:';
    $dbString .= getMaxDecimalNumber($result, $allFloats) . ':';
    $dbString .= $score . ';';
    $intervals = getIntervals($vars, $allFloats);

    $doneVars = [];

    foreach ($vars as $var) {
        if (in_array($var, $doneVars)) {
            continue;
        }
        $dbString .= '#' . $var . ':';
        $dbString .= $intervals[$var]['min'] . '-' . $intervals[$var]['max'] . '::';
        $dbString .= $intervals[$var]['decimals'] . ';';
        $doneVars[] = $var;
    }

    return $dbString;
}


/**
 * @param $line
 * @return array
 */
function parseFullLine($line): array
{
    preg_match('/(.*)(-?\[\d+\.?\d*\])@@(.*)$/', $line, $parts);
    return $parts;
}



/**
 * "- " with no digit before are not math context, replace it with
 * ###minus ### pour le récupérer plus tard
 * @param $wording
 * @return string
 */
function sanitizeWording($wording): string
{
    // case <p>14.83    + -9.07-78.52</p> plus and minus are math
    $wording = preg_replace('/(\d) *([+-]) *(-?\d)/',
        "$1$2$3",
        $wording);

    // case <p>- 17.23</p> minus for math
    $wording = preg_replace('/(^|\D)( *)- /',
        '$1$2###minus ###',
        $wording);

    // 9-8 is 9 - 8 // 9--8 is 9 - -8
    $wording = preg_replace("/(\d *)-(-?\d)/", "$1 - $2", $wording);

    return $wording;
}

/**
 * @param $value
 * @return array
 */
function getVariables($formula): array
{
    preg_match_all("/\[([^]]+)\]/", $formula, $matches);

    $res = $matches[1];
    // variable name cannot be e nor pi
    foreach ($matches[1] as $index => $var) {
        if ($var === 'e' or $var === 'pi') {
            $newName = $var . 'a';
            while (in_array($newName, $matches[1])) {
                $newName .= 'a';
            }
            $res[$index] = $newName;
        }
    }

    return $res;
}

/**
 * @param $value
 * @return array
 */
function getFloat($wording): array
{
    preg_match_all("/(-?\d+\.?\d*)/", $wording, $matches);

    return $matches[0];
}

/**
 * @param $allFloats
 * @param $equalities
 * @return array
 */
function getFloatRemovingEqualities($allFloats, $equalities): array
{
    $resAllFloats = [];
    foreach ($allFloats as $float) {
        $resFloats = [];
        foreach ($float as $index => $value) {
            if (!array_key_exists($index, $equalities)) {
                $resFloats[] = $value;
            }
        }
        $resAllFloats[] = $resFloats;
    }
    return $resAllFloats;
}

/**
 * @param $lines
 * @return array
 */
function getFloats($lines): array
{
    $res = [];
    foreach ($lines as $line) {
        $parts = parseFullLine($line);
        if (isset($parts[1])) {
            $res[] = getFloat($parts[1]);
        }
    }
    return $res;
}

/**
 * @param $vars
 * @param $lines
 * @return array
 */
function getIntervals($vars, $floats): array
{
    $res = [];

    if (empty($floats[0]) || count($vars) !== count($floats[0])) {
        return $res;
    }

    foreach ($floats as $float) {
        foreach ($float as $index => $value) {

            $res[$vars[$index]]['min'] = min(
                $res[$vars[$index]]['min'] ?? PHP_FLOAT_MAX,
                (float) $value
            );

            $res[$vars[$index]]['max'] = max(
                $res[$vars[$index]]['max'] ?? PHP_FLOAT_MAX * -1,
                (float) $value
            );

            $decimals = 0;
            $stringValue = (string) $value;
            if (strpos($stringValue, ".")) {
                $decimals = strlen(preg_replace("/.+\./", "", $stringValue));
            }

            $res[$vars[$index]]['decimals'] = max(
                $res[$vars[$index]]['decimals'] ?? 0,
                $decimals
            );

        }
    }

    return $res;
}

/**
 * @param $wording
 * @param $vars
 * @param $constants
 * @return string
 */
function replaceFloatsInWording($wording, $vars, $constants): string
{
    $wording = preg_replace_callback(
        "/(-?\d+\.?\d*)/",
        function () use ($vars, $constants) {
            static $index = 0;
            static $varIndex = 0;
            if (array_key_exists($index, $constants)) {
                $res = '###constant' . $index . '###';
            } else {
                $res = '[#' . $vars[$varIndex] . ']';
                $varIndex++;
            }
            $index++;
            return $res;
        },
        $wording
    );

    return $wording;
}

/**
 * @param $formula
 * @param $vars
 * @return string
 */
function getMigratedFormula($formula, $vars): string
{
    foreach ($vars as $var) {
        $formula = preg_replace('/\[' . $var . '\]/', $var, $formula);
    }

    return $formula;
}

/**
 * @param $result
 * @param $allFloats
 * @return int
 */
function getMaxDecimalNumber($result, $allFloats): int
{
    $res = getNumberOfDecimals($result);

    foreach ($allFloats as $floats) {
        foreach ($floats as $float) {
            $res = max($res, getNumberOfDecimals($float));
        }
    }

    return $res;
}

/**
 * @param $float
 * @return int
 */
function getNumberOfDecimals($float): int
{
    if (!strpos($float, '.')) {
        return 0;
    }

    $splited = explode('.', preg_replace('/(\[|\])/', '', $float));

    return strlen($splited[1]);
}
