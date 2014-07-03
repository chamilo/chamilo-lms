<?php

namespace FOS\MessageBundle\Search;

/**
 * Gets the search term from the request and prepares it
 */
interface QueryFactoryInterface
{
    /**
     * Gets the search term
     *
     * @return Query the term object
     */
    function createFromRequest();
}
