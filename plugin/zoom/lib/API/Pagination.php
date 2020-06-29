<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Trait Pagination
 * properties for Pagination and PaginationToken objects, which are paginated lists of items,
 * retrieved in chunks from the server over one or several API calls, one per page.
 *
 * @package Chamilo\PluginBundle\Zoom\API
 */
trait Pagination
{
    use JsonDeserializableTrait;

    /** @var int */
    public $page_count;

    /** @var int counting from 1 */
    public $page_number;

    /** @var int */
    public $page_size;

    /** @var int */
    public $total_records;

    /** @var string The next page token is used to paginate through large result sets.
     * A next page token will be returned whenever the set of available results exceeds the current page size.
     * The expiration period for this token is 15 minutes.
     */
    public $next_page_token;

    /**
     * Retrieves items from the server.
     *
     * @param string $arrayPropertyName item array property name
     * @param Client $client
     * @param string $relativePath      relative path to pass to Client::send
     * @param array  $parameters        parameter array to pass to Client::send
     *
     * @throws Exception
     *
     * @return array united list of items
     */
    protected static function loadItems($arrayPropertyName, $client, $relativePath, $parameters = [])
    {
        $items = [];
        $pageCount = 1;
        $pageSize = 300;
        $totalRecords = 0;
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $response = static::fromJson(
                $client->send(
                    'GET',
                    $relativePath,
                    array_merge(['page_size' => $pageSize, 'page_number' => $pageNumber], $parameters)
                )
            );
            $items = array_merge($items, $response->$arrayPropertyName);
            if (0 === $totalRecords) {
                $pageCount = $response->page_count;
                $pageSize = $response->page_size;
                $totalRecords = $response->total_records;
            }
        }
        if (count($items) !== $totalRecords) {
            error_log('Zoom announced '.$totalRecords.' records but returned '.count($items));
        }

        return $items;
    }
}
