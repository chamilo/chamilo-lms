<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Return the answer letters used in multiple-choice exports.
 */
function test2pdf_get_letters(): array
{
    return [
        'a',
        'b',
        'c',
        'd',
        'e',
        'f',
        'g',
        'h',
        'i',
        'j',
        'k',
        'l',
        'm',
        'n',
        'o',
        'p',
        'q',
        'r',
        's',
        't',
        'u',
        'v',
        'w',
        'x',
        'y',
        'z',
    ];
}

/**
 * Build the session condition for resource_link in Chamilo 2.
 *
 * In session context, include both:
 * - resources linked specifically to the current session
 * - resources linked only to the base course
 *
 * In non-session context, include only base-course resources.
 */
function test2pdf_get_resource_link_session_condition(int $sessionId, string $alias = 'rl'): string
{
    if ($sessionId > 0) {
        return " AND ($alias.session_id = $sessionId OR $alias.session_id IS NULL)";
    }

    return " AND $alias.session_id IS NULL";
}

/**
 * Select every column of an entity table as scalars (the shape of a legacy "SELECT alias.*" row).
 *
 * @return array{0: string[], 1: array<string, string>} select expressions and the column name of each alias
 */
function test2pdf_all_columns(string $entityClass, string $alias): array
{
    $meta = Database::getManager()->getClassMetadata($entityClass);
    $select = [];
    $columns = [];
    $i = 0;
    foreach ($meta->getFieldNames() as $field) {
        $name = 'c'.$i++;
        $select[] = "$alias.$field AS $name";
        $columns[$name] = $meta->getColumnName($field);
    }
    foreach ($meta->getAssociationNames() as $association) {
        if (!$meta->isSingleValuedAssociation($association) || $meta->isAssociationInverseSide($association)) {
            continue;
        }
        $name = 'c'.$i++;
        $select[] = "IDENTITY($alias.$association) AS $name";
        $columns[$name] = $meta->getSingleAssociationJoinColumnName($association);
    }

    return [$select, $columns];
}

/**
 * Turn a scalar row of test2pdf_all_columns() into the column-named row the raw query used to return.
 */
function test2pdf_legacy_row(array $row, array $columns): array
{
    $legacy = [];
    foreach ($columns as $name => $column) {
        $value = $row[$name] ?? null;
        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        } elseif (is_bool($value)) {
            $value = (int) $value;
        } elseif (is_array($value)) {
            // json columns: Doctrine stores json_encode() with the default flags, so this is the stored text.
            $value = json_encode($value);
        }
        $legacy[$column] = $value;
    }

    return $legacy;
}

function test2pdf_quiz_query(int $courseId, int $sessionId): QueryBuilder
{
    $qb = Database::getManager()->createQueryBuilder()
        ->from(CQuiz::class, 'q')
        ->innerJoin(ResourceLink::class, 'rl', Join::WITH, 'rl.resourceNode = q.resourceNode')
        ->where('IDENTITY(rl.course) = :courseId')
        ->andWhere('rl.deletedAt IS NULL')
        ->setParameter('courseId', $courseId);

    if ($sessionId > 0) {
        $qb->andWhere('IDENTITY(rl.session) = :sessionId OR rl.session IS NULL')
            ->setParameter('sessionId', $sessionId);
    } else {
        $qb->andWhere('rl.session IS NULL');
    }

    return $qb;
}

/**
 * List exercises available in the current course/session context.
 *
 * @param int $courseId  Course ID
 * @param int $sessionId Session ID
 *
 * @return array
 */
function showExerciseCourse($courseId, $sessionId = 0)
{
    $courseId = (int) $courseId;
    $sessionId = (int) $sessionId;

    [$select, $columns] = test2pdf_all_columns(CQuiz::class, 'q');
    $rows = test2pdf_quiz_query($courseId, $sessionId)
        ->select($select)
        ->distinct()
        ->orderBy('q.title', 'ASC')
        ->getQuery()
        ->getScalarResult();

    $items = [];
    foreach ($rows as $row) {
        $items[] = test2pdf_legacy_row($row, $columns);
    }

    return $items;
}

/**
 * Get quiz information in the current course/session context.
 *
 * @param int $courseId  Course ID
 * @param int $id        Quiz ID
 * @param int $sessionId Session ID
 *
 * @return array|false
 */
function getInfoQuiz($courseId, $id, $sessionId = 0)
{
    $courseId = (int) $courseId;
    $id = (int) $id;
    $sessionId = (int) $sessionId;

    [$select, $columns] = test2pdf_all_columns(CQuiz::class, 'q');
    $rows = test2pdf_quiz_query($courseId, $sessionId)
        ->select($select)
        ->distinct()
        ->andWhere('q.iid = :id')
        ->setParameter('id', $id)
        ->setMaxResults(1)
        ->getQuery()
        ->getScalarResult();

    return [] === $rows ? false : test2pdf_legacy_row($rows[0], $columns);
}

/**
 * List question IDs for a quiz.
 *
 * @param int $courseId
 * @param int $quizId
 * @param int $sessionId
 *
 * @return array
 */
function getQuestionsFromCourse($courseId, $quizId, $sessionId = 0)
{
    $quizId = (int) $quizId;

    $rows = Database::getManager()->createQueryBuilder()
        ->select('IDENTITY(qr.question) AS question_id')
        ->from(CQuizRelQuestion::class, 'qr')
        ->innerJoin('qr.question', 'q')
        ->where('IDENTITY(qr.quiz) = :quizId')
        ->andWhere('q.type IN (1, 2, 3, 9, 10, 11, 12, 14)')
        ->orderBy('qr.questionOrder', 'ASC')
        ->addOrderBy('qr.iid', 'ASC')
        ->setParameter('quizId', $quizId)
        ->getQuery()
        ->getScalarResult();

    $items = [];
    foreach ($rows as $row) {
        $items[] = (int) $row['question_id'];
    }

    return $items;
}

/**
 * Get question information.
 *
 * @param int $courseId
 * @param int $id
 *
 * @return array|false
 */
function getInfoQuestion($courseId, $id)
{
    $id = (int) $id;

    [$select, $columns] = test2pdf_all_columns(CQuizQuestion::class, 'q');
    $rows = Database::getManager()->createQueryBuilder()
        ->select($select)
        ->from(CQuizQuestion::class, 'q')
        ->where('q.iid = :id')
        ->andWhere('q.type IN (1, 2, 3, 9, 10, 11, 12, 14)')
        ->setParameter('id', $id)
        ->setMaxResults(1)
        ->getQuery()
        ->getScalarResult();

    return [] === $rows ? false : test2pdf_legacy_row($rows[0], $columns);
}

/**
 * List answers by question ID.
 *
 * @param int $courseId
 * @param int $id
 *
 * @return array
 */
function getAnswers($courseId, $id)
{
    $id = (int) $id;

    [$select, $columns] = test2pdf_all_columns(CQuizAnswer::class, 'a');
    $rows = Database::getManager()->createQueryBuilder()
        ->select($select)
        ->from(CQuizAnswer::class, 'a')
        ->where('IDENTITY(a.question) = :id')
        ->orderBy('a.position', 'ASC')
        ->addOrderBy('a.iid', 'ASC')
        ->setParameter('id', $id)
        ->getQuery()
        ->getScalarResult();

    $items = [];
    foreach ($rows as $row) {
        $items[] = test2pdf_legacy_row($row, $columns);
    }

    return $items;
}

/**
 * Remove all HTML tags.
 *
 * @param string $string The string to be stripped of HTML
 *
 * @return string
 */
function removeHtml($string)
{
    $txt = str_replace('<html>', '', $string);
    $txt = str_replace('<head>', '', $txt);
    $txt = str_replace('<title>', '', $txt);
    $txt = str_replace('</title>', '', $txt);
    $txt = str_replace('</head>', '', $txt);
    $txt = str_replace('<body>', '', $txt);
    $txt = str_replace('</body>', '', $txt);
    $txt = str_replace('</html>', '', $txt);
    $txt = strip_tags($txt);
    $txt = str_replace(chr(13).chr(10), '', $txt);
    $txt = str_replace('&nbsp;', ' ', $txt);
    $txt = str_replace('&Aacute;', 'Á', $txt);
    $txt = str_replace('&aacute;', 'á', $txt);
    $txt = str_replace('&Eacute;', 'É', $txt);
    $txt = str_replace('&eacute;', 'é', $txt);
    $txt = str_replace('&Iacute;', 'Í', $txt);
    $txt = str_replace('&iacute;', 'í', $txt);
    $txt = str_replace('&Oacute;', 'Ó', $txt);
    $txt = str_replace('&oacute;', 'ó', $txt);
    $txt = str_replace('&Uacute;', 'Ú', $txt);
    $txt = str_replace('&uacute;', 'ú', $txt);
    $txt = str_replace('&Ntilde;', 'Ñ', $txt);
    $txt = str_replace('&ntilde;', 'ñ', $txt);
    $txt = str_replace('&agrave;', 'à', $txt);
    $txt = str_replace('&Agrave;', 'À', $txt);
    $txt = str_replace('&iexcl;', '¡', $txt);
    $txt = str_replace('&middot;', '·', $txt);
    $txt = str_replace('&Ccedil;', 'Ç', $txt);
    $txt = str_replace('&ccedil;', 'ç', $txt);
    $txt = str_replace('&quot;', '"', $txt);
    $txt = str_replace('&ordf;', 'ª', $txt);
    $txt = str_replace('&ordm;', 'º', $txt);
    $txt = str_replace('&amp;', '&', $txt);
    $txt = str_replace('&bull;', '•', $txt);
    $txt = str_replace('&iquest;', '¿', $txt);
    $txt = str_replace('&euro;', 'EUR', $txt);
    $txt = str_replace('&uuml;', 'ü', $txt);
    $txt = str_replace('&Uuml;', 'Ü', $txt);

    return str_replace('&uml;', '¨', $txt);
}

/**
 * Replace HTML entities while preserving basic HTML.
 *
 * @param string $string The string to normalize
 *
 * @return string
 */
function removeQuotes($string)
{
    $txt = str_replace('&nbsp;', ' ', $string);
    $txt = str_replace('&Aacute;', 'Á', $txt);
    $txt = str_replace('&aacute;', 'á', $txt);
    $txt = str_replace('&Eacute;', 'É', $txt);
    $txt = str_replace('&eacute;', 'é', $txt);
    $txt = str_replace('&Iacute;', 'Í', $txt);
    $txt = str_replace('&iacute;', 'í', $txt);
    $txt = str_replace('&Oacute;', 'Ó', $txt);
    $txt = str_replace('&oacute;', 'ó', $txt);
    $txt = str_replace('&Uacute;', 'Ú', $txt);
    $txt = str_replace('&uacute;', 'ú', $txt);
    $txt = str_replace('&Ntilde;', 'Ñ', $txt);
    $txt = str_replace('&ntilde;', 'ñ', $txt);
    $txt = str_replace('&quot;', '"', $txt);
    $txt = str_replace('&ordf;', 'ª', $txt);
    $txt = str_replace('&ordm;', 'º', $txt);
    $txt = str_replace('&amp;', '&', $txt);
    $txt = str_replace('&bull;', '•', $txt);
    $txt = str_replace('&iquest; &', '¿', $txt);
    $txt = str_replace('&agrave;', 'à', $txt);
    $txt = str_replace('&Agrave;', 'À', $txt);
    $txt = str_replace('&iexcl;', '¡', $txt);
    $txt = str_replace('&middot;', '·', $txt);
    $txt = str_replace('&Ccedil;', 'Ç', $txt);
    $txt = str_replace('&ccedil;', 'ç', $txt);
    $txt = str_replace('&euro;', 'EUR', $txt);
    $txt = str_replace('&uuml;', 'ü', $txt);
    $txt = str_replace('&Uuml;', 'Ü', $txt);

    return str_replace('&uml;', '¨', $txt);
}
