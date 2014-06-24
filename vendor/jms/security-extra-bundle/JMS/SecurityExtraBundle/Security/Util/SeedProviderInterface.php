<?php

namespace JMS\SecurityExtraBundle\Security\Util;

/**
 * Seed Provider Interface.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface SeedProviderInterface
{
    /**
     * Loads the initial seed.
     *
     * Whatever is returned from this method, it should not be guessable.
     *
     * @return array of the format array(string, DateTime) where string is the
     *               initial seed, and DateTime is the last time it was updated
     */
    public function loadSeed();

    /**
     * Updates the seed.
     *
     * @param string $seed
     */
    public function updateSeed($seed);
}
