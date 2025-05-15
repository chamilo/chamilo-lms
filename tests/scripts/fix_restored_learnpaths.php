<?php
/* For licensing terms, see /license.txt */
die('Remove the "die()" statement on line '.__LINE__.' to execute this script'.PHP_EOL);
use Chamilo\CourseBundle\Entity\CLp;
use Doctrine\ORM\Query\Expr\Join;
use Chamilo\CourseBundle\Entity\CTool;

require_once __DIR__.'/../../public/main/inc/global.inc.php';

$em = Database::getManager();

$qb1 = $em->createQueryBuilder();
$result1 = $qb1
    ->select('lp')
    ->from(CLp::class, 'lp')
    ->innerJoin(CTool::class, 't', JOIN::WITH, 'lp.cId = t.cId AND lp.name = t.name')
    ->where(
        $qb1->expr()->eq('t.link', ':link')
    )
    ->setParameter('link', 'lp/lp_controller.php?action=view&lp_id=$new_lp_id&id_session=0')
    ->getQuery()
    ->getResult();

/** @var CLp $lp */
foreach ($result1 as $i => $lp) {
    echo ($i + 1)." LP {$lp->getId()}: {$lp->getTitle()}".PHP_EOL;

    $qb2 = $em->createQueryBuilder();

    /** @var CTool $tool */
    $tool = $qb2
        ->select('t')
        ->from(CTool::class, 't')
        ->where(
            $qb2->expr()->andX(
                $qb2->expr()->eq('t.link', ':link'),
                $qb2->expr()->eq('t.name', ':name'),
                $qb2->expr()->eq('t.cId', ':cid')
            )
        )
        ->setParameters([
            'link' => 'lp/lp_controller.php?action=view&lp_id=$new_lp_id&id_session=0',
            'name' => $lp->getTitle(),
            'cid' => $lp->getCId()
        ])
        ->getQuery()
        ->getOneOrNullResult();

    $tool->setLink("lp/lp_controller.php?action=view&lp_id={$lp->getId()}&id_session=0");

    $em->persist($tool);
    $em->flush();

    echo "\tTool: {$tool->getId()}: {$tool->getTitle()}".PHP_EOL;
    echo "\tNew link: {$tool->getLink()}".PHP_EOL;
}
