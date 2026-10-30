<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_admin_script(true);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$query = $_GET['q'] ?? '';
$keyword = is_string($query) ? trim(mb_substr($query, 0, 80)) : '';

if (mb_strlen($keyword) < 2) {
    echo json_encode(['results' => []], JSON_THROW_ON_ERROR);
    exit;
}

$repository = Container::getUserRepository();
$queryBuilder = $repository->createQueryBuilder('u');
$queryBuilder->select('u.id', 'u.firstname', 'u.lastname', 'u.username');

$repository->addActiveAndNotAnonUserQueryBuilder($queryBuilder);
$repository->addRoleListQueryBuilder(
    ['ROLE_TEACHER', 'ROLE_ADMIN', 'ROLE_SESSION_MANAGER', 'ROLE_GLOBAL_ADMIN'],
    $queryBuilder
);

$queryBuilder
    ->andWhere('(u.firstname LIKE :keyword OR u.lastname LIKE :keyword OR u.email LIKE :keyword OR u.username LIKE :keyword)')
    ->setParameter('keyword', '%'.$keyword.'%')
    ->orderBy('u.firstname', 'ASC')
    ->addOrderBy('u.lastname', 'ASC')
    ->setMaxResults(20)
;

$results = [];
foreach ($queryBuilder->getQuery()->getArrayResult() as $user) {
    $username = (string) ($user['username'] ?? '');
    $fullName = trim((string) ($user['firstname'] ?? '').' '.(string) ($user['lastname'] ?? ''));
    $results[] = [
        'id' => (int) $user['id'],
        'text' => ('' !== $fullName ? $fullName : $username).' ('.$username.')',
    ];
}

echo json_encode(['results' => $results], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
