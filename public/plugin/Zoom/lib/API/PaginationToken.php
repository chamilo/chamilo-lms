<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Trait PaginationToken
 * properties for PaginationToken objects, which are paginated lists of items,
 * retrieved in chunks from the server over one or several API calls, one per page.
 */
trait PaginationToken
{
    use JsonDeserializableTrait;

    /** @var int The number of pages returned for the request made. */
    public $page_count;

    /** @var int The number of records returned within a single API call. Default 30, max 300. */
    public $page_size;

    /** @var int The number of all records available across pages. */
    public $total_records;

    /** @var string The next page token is used to paginate through large result sets.
     * A next page token will be returned whenever the set of available results exceeds the current page size.
     * The expiration period for this token is 15 minutes.
     */
    public $next_page_token;

    /**
     * Retrieves all items from the server, possibly generating several API calls.
     *
     * @param string $arrayPropertyName item array property name
     * @param string $relativePath      relative path to pass to Client::send
     * @param array  $parameters        parameter array to pass to Client::send
     *
     * @throws Exception
     *
     * @return array united list of items
     */
    protected static function loadItems($arrayPropertyName, $relativePath, $parameters = [])
    {
        $items = [];
        $pageSize = 300;
        $totalRecords = 0;
        $nextPageToken = '';
        do {
            $response = static::fromJson(
                Client::getInstance()->send(
                    'GET',
                    $relativePath,
                    array_merge(['page_size' => $pageSize, 'next_page_token' => $nextPageToken], $parameters)
                )
            );
            $items = array_merge($items, $response->$arrayPropertyName);
            $nextPageToken = $response->next_page_token;
            if (0 === $totalRecords) {
                $pageSize = $response->page_size;
                $totalRecords = $response->total_records;
            }
        } while (!empty($nextPagetoken));
        if (count($items) !== $totalRecords) {
            error_log('Zoom announced '.$totalRecords.' records but returned '.count($items));
        }

        return $items;
    }
}
