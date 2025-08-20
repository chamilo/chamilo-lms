<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackELoginRecord;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class TrackELoginRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackELoginRecord::class);
    }

    public function addTrackLogin(string $username, string $userIp, bool $success): void
    {
        $trackELoginRecord = new TrackELoginRecord();
        $trackELoginRecord
            ->setUsername($username)
            ->setLoginDate(new DateTime())
            ->setUserIp($userIp)
            ->setSuccess($success)
        ;

        $this->_em->persist($trackELoginRecord);
        $this->_em->flush();
    }

    public function failedByMonth(int $months = 12): array
    {
        $sql = "
      SELECT DATE_FORMAT(login_date, '%Y-%m-01') AS month, COUNT(*) AS failed
      FROM track_e_login_record
      WHERE success = 0 AND login_date >= (CURRENT_DATE - INTERVAL :m MONTH)
      GROUP BY month ORDER BY month ASC
    ";
        return $this->getEntityManager()->getConnection()
            ->executeQuery($sql, ['m' => $months])
            ->fetchAllAssociative();
    }

    public function topUsernames(int $days = 30, int $limit = 5): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare("
        SELECT username, COUNT(*) AS failed
        FROM track_e_login_record
        WHERE success = 0 AND login_date >= (CURRENT_DATE - INTERVAL :d DAY)
        GROUP BY username ORDER BY failed DESC LIMIT :l
    ");
        $stmt->bindValue('d', $days, \PDO::PARAM_INT);
        $stmt->bindValue('l', $limit, \PDO::PARAM_INT);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function topIps(int $days = 30, int $limit = 5): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare("
        SELECT user_ip AS ip, COUNT(*) AS failed
        FROM track_e_login_record
        WHERE success = 0 AND login_date >= (CURRENT_DATE - INTERVAL :d DAY)
        GROUP BY user_ip ORDER BY failed DESC LIMIT :l
    ");
        $stmt->bindValue('d', $days, \PDO::PARAM_INT);
        $stmt->bindValue('l', $limit, \PDO::PARAM_INT);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function findFailedPaginated(int $page, int $pageSize, array $filters = []): array
    {
        $where  = ["success = 0"];
        $params = [];

        if (!empty($filters['username'])) { $where[] = "username = :u";     $params['u']   = $filters['username']; }
        if (!empty($filters['ip']))       { $where[] = "user_ip = :ip";     $params['ip']  = $filters['ip']; }
        if (!empty($filters['from']))     { $where[] = "login_date >= :fr"; $params['fr']  = $filters['from']; }
        if (!empty($filters['to']))       { $where[] = "login_date <= :to"; $params['to']  = $filters['to']; }

        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';
        $offset   = ($page - 1) * $pageSize;
        $conn     = $this->getEntityManager()->getConnection();

        $rows = $conn->executeQuery(
            "SELECT login_date, user_ip, username
           FROM track_e_login_record
           $whereSql
          ORDER BY login_date DESC
          LIMIT :lim OFFSET :off",
            $params + ['lim' => $pageSize, 'off' => $offset],
            ['lim' => \PDO::PARAM_INT, 'off' => \PDO::PARAM_INT]
        )->fetchAllAssociative();

        $total = (int) $conn->executeQuery(
            "SELECT COUNT(*) FROM track_e_login_record $whereSql", $params
        )->fetchOne();

        return ['items' => $rows, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize];
    }

    public function failedByDay(int $days = 7): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare("
        SELECT DATE(login_date) AS day, COUNT(*) AS failed
        FROM track_e_login_record
        WHERE success = 0
          AND login_date >= (CURRENT_DATE - INTERVAL :d DAY)
        GROUP BY day
        ORDER BY day ASC
    ");
        $stmt->bindValue('d', $days, \PDO::PARAM_INT);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function successVsFailedByDay(int $days = 30): array
    {
        $sql = "
      SELECT DATE(login_date) AS day,
             SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) AS success_cnt,
             SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) AS failed_cnt
      FROM track_e_login_record
      WHERE login_date >= (CURRENT_DATE - INTERVAL :d DAY)
      GROUP BY day
      ORDER BY day ASC";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue('d', $days, \PDO::PARAM_INT);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function failedByHourOfDay(int $days = 7): array
    {
        $sql = "
      SELECT HOUR(login_date) AS hour, COUNT(*) AS failed
      FROM track_e_login_record
      WHERE success = 0
        AND login_date >= (CURRENT_DATE - INTERVAL :d DAY)
      GROUP BY hour ORDER BY hour";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue('d', $days, \PDO::PARAM_INT);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function uniqueIpsByDay(int $days = 30): array
    {
        $sql = "
      SELECT DATE(login_date) AS day, COUNT(DISTINCT user_ip) AS unique_ips
      FROM track_e_login_record
      WHERE success = 0
        AND login_date >= (CURRENT_DATE - INTERVAL :d DAY)
      GROUP BY day ORDER BY day";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue('d', $days, \PDO::PARAM_INT);
        return $stmt->executeQuery()->fetchAllAssociative();
    }
}
