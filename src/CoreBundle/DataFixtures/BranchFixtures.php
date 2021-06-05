<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\BranchSync;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class BranchFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Original query in data.sql
        // INSERT INTO branch_sync (access_url_id, branch_name, unique_id, ssl_pub_key)
        // VALUES (1, 'localhost', SHA1(UUID()), SHA1(UUID()));

        $url = $this->getReference(AccessUserFixtures::ACCESS_URL_REFERENCE);

        $branch = (new BranchSync())
            ->setBranchName('localhost')
            ->setUniqueId(sha1(Uuid::v1()->toRfc4122()))
            ->setSslPubKey(sha1(Uuid::v1()->toRfc4122()))
            ->setUrl($url)
        ;

        $manager->persist($branch);
        $manager->flush();
    }
}
