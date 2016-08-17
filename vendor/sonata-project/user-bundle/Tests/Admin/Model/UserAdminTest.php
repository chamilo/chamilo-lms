<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Tests\Admin\Model;

use Sonata\UserBundle\Admin\Model\UserAdmin;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class UserAdminTest extends \PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $admin = new UserAdmin('admin.group', 'Sonata\UserBundle\Model\User', 'SonataAdminBundle:CRUD');

        $this->assertNotEmpty($admin);
    }
}
