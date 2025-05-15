<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Trait Pagination
 * properties for Pagination objects, which are paginated lists of items,
 * retrieved in chunks from the server over one or several API calls, one per page.
 */
trait Pagination
{
    use JsonDeserializableTrait;

    /** @var int The number of pages returned for the request made. */
    public $page_count;

    /** @var int The page number of the current results, counting from 1 */
    public $page_number;

    /** @var int The number of records returned with a single API call. Default 30, max 300. */
    public $page_size;

    /** @var int The total number of all the records available across pages. */
    public $total_records;

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
        $pageCount = 1;
        $pageSize = 300;
        $totalRecords = 0;
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $response = static::fromJson(
                Client::getInstance()->send(
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
