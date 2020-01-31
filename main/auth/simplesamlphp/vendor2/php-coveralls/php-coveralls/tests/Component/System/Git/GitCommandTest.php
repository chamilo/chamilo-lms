<?php

namespace PhpCoveralls\Tests\Component\System\Git;

use PhpCoveralls\Component\System\Git\GitCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpCoveralls\Component\System\Git\GitCommand
 * @covers \PhpCoveralls\Component\System\SystemCommandExecutor
 * @covers \PhpCoveralls\Component\System\SystemCommandExecutorInterface
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class GitCommandTest extends TestCase
{
    /**
     * @test
     */
    public function shouldReturnBranches()
    {
        $object = new GitCommand();
        $actual = $object->getBranches();

        $this->assertInternalType('array', $actual);
        $this->assertNotEmpty($actual);
    }

    /**
     * @test
     */
    public function shouldReturnHeadCommit()
    {
        $object = new GitCommand();
        $actual = $object->getHeadCommit();

        $this->assertInternalType('array', $actual);
        $this->assertNotEmpty($actual);
        $this->assertCount(6, $actual);
    }

    /**
     * @test
     */
    public function shouldReturnRemotes()
    {
        $object = new GitCommand();
        $actual = $object->getRemotes();

        $this->assertInternalType('array', $actual);
        $this->assertNotEmpty($actual);
    }
}
