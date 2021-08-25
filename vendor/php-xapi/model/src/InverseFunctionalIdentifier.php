<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model;

/**
 * The inverse functional identifier of an {@link Actor}.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class InverseFunctionalIdentifier
{
    private $mbox;
    private $mboxSha1Sum;
    private $openId;
    private $account;

    /**
     * Use one of the with*() factory methods to obtain an InverseFunctionalIdentifier
     * instance.
     */
    private function __construct()
    {
    }

    public static function withMbox(IRI $mbox): self
    {
        $iri = new InverseFunctionalIdentifier();
        $iri->mbox = $mbox;

        return $iri;
    }

    public static function withMboxSha1Sum(string $mboxSha1Sum): self
    {
        $iri = new InverseFunctionalIdentifier();
        $iri->mboxSha1Sum = $mboxSha1Sum;

        return $iri;
    }

    public static function withOpenId(string $openId): self
    {
        $iri = new InverseFunctionalIdentifier();
        $iri->openId = $openId;

        return $iri;
    }

    public static function withAccount(Account $account): self
    {
        $iri = new InverseFunctionalIdentifier();
        $iri->account = $account;

        return $iri;
    }

    /**
     * Returns the mailto IRI.
     */
    public function getMbox(): ?IRI
    {
        return $this->mbox;
    }

    /**
     * Returns the SHA1 hash of a mailto IRI.
     */
    public function getMboxSha1Sum(): ?string
    {
        return $this->mboxSha1Sum;
    }

    /**
     * Returns the openID.
     */
    public function getOpenId(): ?string
    {
        return $this->openId;
    }

    /**
     * Returns the user account of an existing system.
     */
    public function getAccount(): ?Account
    {
        return $this->account;
    }

    /**
     * Checks if another IRI is equal.
     *
     * Two inverse functional identifiers are equal if and only if all of their
     * properties are equal.
     */
    public function equals(InverseFunctionalIdentifier $iri): bool
    {
        if (null !== $this->mbox && null !== $iri->mbox && !$this->mbox->equals($iri->mbox)) {
            return false;
        }

        if ($this->mboxSha1Sum !== $iri->mboxSha1Sum) {
            return false;
        }

        if ($this->openId !== $iri->openId) {
            return false;
        }

        if (null === $this->account && null !== $iri->account) {
            return false;
        }

        if (null !== $this->account && null === $iri->account) {
            return false;
        }

        if (null !== $this->account && !$this->account->equals($iri->account)) {
            return false;
        }

        return true;
    }

    public function __toString(): string
    {
        if (null !== $this->mbox) {
            return $this->mbox->getValue();
        }

        if (null !== $this->mboxSha1Sum) {
            return $this->mboxSha1Sum;
        }

        if (null !== $this->openId) {
            return $this->openId;
        }

        return sprintf('%s (%s)', $this->account->getName(), $this->account->getHomePage()->getValue());
    }
}
