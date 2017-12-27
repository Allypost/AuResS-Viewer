<?php

namespace Allypost\Api;

use RedisClient\RedisClient;

class Room
{
    private const OPTS = [
        'http' => [
            'method' => "GET",
            'header' => "Host: www.auress.org\r\n" .
                        "User-Agent: Auress-Viewer-Bot\r\n" .
                        "Accept: text/html\r\n",
        ],
    ];

    protected const URL = "http://www.auress.org/graf.php?brOdgovora=999&all=%d&selector=%d&soba=%04s";

    /**
     * Fetch data from AuResS and cache result
     *
     * @param string           $roomID - The ID of the room to fetch data from (zero padded integer of length 4)
     * @param string           $type   - Type of data (one of: all, first, last)
     * @param null|RedisClient $redis  - The Redis client
     *
     * @return integer[] - List of integers representing numbers of responses for each option
     */
    public static function get(string $roomID, string $type = 'all', ?RedisClient $redis = null): array
    {
        $roomData = null;

        if ($redis) {
            $roomData = $redis->get("Room:{$roomID}");
            $roomData = json_decode($roomData, true);
        }

        if (!$roomData) {
            $roomData = self::getData($roomID, $type);

            if ($redis) {
                // Push the data to Redis for caching (1s cache)
                $redis->set("Room:{$roomID}", json_encode($roomData), 1);
            }
        }

        return $roomData;
    }

    /**
     * Fetch data from AuResS
     *
     * @param string $roomID - The ID of the room to fetch data from (zero padded integer of length 4)
     * @param string $type   - Type of data (one of: all, first, last)
     *
     * @return integer[] - List of integers representing numbers of responses for each option
     */
    public static function getData(string $roomID, string $type = 'all'): array
    {

        $url = self::getURL($roomID, $type);
        $opts = self::OPTS;

        // Get the raw contents from the remote host (in the form of a comma separated list)
        $roomData = file_get_contents($url, false, stream_context_create($opts)) ?? '';

        return self::processData($roomData);
    }

    /**
     * Get the URL for the room and type of data
     *
     * @param string $roomID - The ID of the room to fetch data from (zero padded integer of length 4)
     * @param string $type   - Type of data (one of: all, first, last)
     *
     * @return string - The URL
     */
    public static function getURL(string $roomID, string $type = 'all'): string
    {
        $params = self::getUrlParamValues($roomID, $type);

        return self::getURLFor($params);
    }

    /**
     * Get the parameter values for the url (room ID, fetch all, fetch type)
     *
     * @param string $roomID - The ID of the room to fetch data from (zero padded integer of length 4)
     * @param string $type   - Type of data (one of: all, first, last)
     *
     * @return array - The params needed to construct the URL
     */
    private static function getUrlParamValues(string $roomID, string $type): array
    {
        $first = false;
        $all = false;

        switch ($type) {
            case 'last':
                break;
            case 'first':
                $first = true;
                break;
            case 'all':
            default:
                $all = true;
                break;
        }

        return compact('all', 'first', 'roomID');
    }

    /**
     * Get the url for the room's data
     *
     * @param array $params - List containing whether to return all data, data selector, and room ID
     *
     * @return string - The URL
     */
    private static function getURLFor(array $params): string
    {
        $url = static::URL;

        $args = [
            $params['all'] ?? 0,
            $params['first'] ?? 0,
            $params['roomID'] ?? '',
        ];

        return sprintf($url, ...$args);
    }

    /**
     * Process the raw data fetched from the remote API
     *
     * @param string $roomData - Raw data fetched from the API (comma separated list)
     *
     * @return integer[] - Processed data
     */
    private static function processData(string $roomData): array
    {
        // Explode the list into an array
        $data = explode(',', $roomData);

        // Cast array elements to integers
        return array_map(function ($el) {
            return (int) $el;
        }, $data);
    }
}
