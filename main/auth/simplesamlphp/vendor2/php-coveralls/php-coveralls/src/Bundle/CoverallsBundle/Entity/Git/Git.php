<?php

namespace PhpCoveralls\Bundle\CoverallsBundle\Entity\Git;

use PhpCoveralls\Bundle\CoverallsBundle\Entity\Coveralls;

/**
 * Data represents "git" of Coveralls API.
 *
 * "git": {
 *   "head": {
 *     "id": "b31f08d07ae564b08237e5a336e478b24ccc4a65",
 *     "author_name": "Nick Merwin",
 *     "author_email": "...",
 *     "committer_name": "Nick Merwin",
 *     "committer_email": "...",
 *     "message": "version bump"
 *   },
 *   "branch": "master",
 *   "remotes": [
 *     {
 *       "name": "origin",
 *       "url": "git@github.com:lemurheavy/coveralls-ruby.git"
 *     }
 *   ]
 * }
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class Git extends Coveralls
{
    /**
     * Branch name.
     *
     * @var string
     */
    protected $branch;

    /**
     * Head.
     *
     * @var Commit
     */
    protected $head;

    /**
     * Remote.
     *
     * @var Remote[]
     */
    protected $remotes;

    /**
     * Constructor.
     *
     * @param string $branch  branch name
     * @param Commit $head    hEAD commit
     * @param array  $remotes remote repositories
     */
    public function __construct($branch, Commit $head, array $remotes)
    {
        $this->branch = $branch;
        $this->head = $head;
        $this->remotes = $remotes;
    }

    // API

    /**
     * {@inheritdoc}
     *
     * @see \PhpCoveralls\Bundle\CoverallsBundle\Entity\ArrayConvertable::toArray()
     */
    public function toArray()
    {
        $remotes = [];

        foreach ($this->remotes as $remote) {
            $remotes[] = $remote->toArray();
        }

        return [
            'branch' => $this->branch,
            'head' => $this->head->toArray(),
            'remotes' => $remotes,
        ];
    }

    // accessor

    /**
     * Return branch name.
     *
     * @return string
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * Return HEAD commit.
     *
     * @return \PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Commit
     */
    public function getHead()
    {
        return $this->head;
    }

    /**
     * Return remote repositories.
     *
     * @return \PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Remote[]
     */
    public function getRemotes()
    {
        return $this->remotes;
    }
}
