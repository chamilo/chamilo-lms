<?php

namespace PhpCoveralls\Tests\Bundle\CoverallsBundle\Entity\Git;

use PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Commit;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Git;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Remote;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Git
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Entity\Coveralls
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class GitTest extends TestCase
{
    /**
     * @var string
     */
    private $branchName;

    /**
     * @var Commit
     */
    private $commit;

    /**
     * @var Remote
     */
    private $remote;

    /**
     * @var Git
     */
    private $object;

    protected function setUp()
    {
        $this->branchName = 'branch_name';
        $this->commit = $this->createCommit();
        $this->remote = $this->createRemote();

        $this->object = new Git($this->branchName, $this->commit, [$this->remote]);
    }

    // getBranch()

    /**
     * @test
     */
    public function shouldHaveBranchNameOnConstruction()
    {
        $this->assertSame($this->branchName, $this->object->getBranch());
    }

    // getHead()

    /**
     * @test
     */
    public function shouldHaveHeadCommitOnConstruction()
    {
        $this->assertSame($this->commit, $this->object->getHead());
    }

    // getRemotes()

    /**
     * @test
     */
    public function shouldHaveRemotesOnConstruction()
    {
        $this->assertSame([$this->remote], $this->object->getRemotes());
    }

    // toArray()

    /**
     * @test
     */
    public function shouldConvertToArray()
    {
        $expected = [
            'branch' => $this->branchName,
            'head' => $this->commit->toArray(),
            'remotes' => [$this->remote->toArray()],
        ];

        $this->assertSame($expected, $this->object->toArray());
        $this->assertSame(json_encode($expected), (string) $this->object);
    }

    /**
     * @param string $name
     * @param string $url
     *
     * @return Remote
     */
    protected function createRemote($name = 'name', $url = 'url')
    {
        $remote = new Remote();

        return $remote
            ->setName($name)
            ->setUrl($url);
    }

    /**
     * @param string $id
     * @param string $authorName
     * @param string $authorEmail
     * @param string $committerName
     * @param string $committerEmail
     * @param string $message
     *
     * @return Commit
     */
    protected function createCommit($id = 'id', $authorName = 'author_name', $authorEmail = 'author_email', $committerName = 'committer_name', $committerEmail = 'committer_email', $message = 'message')
    {
        $commit = new Commit();

        return $commit
            ->setId($id)
            ->setAuthorName($authorName)
            ->setAuthorEmail($authorEmail)
            ->setCommitterName($committerName)
            ->setCommitterEmail($committerEmail)
            ->setMessage($message);
    }
}
