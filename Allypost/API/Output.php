<?php

namespace Allypost\Api;

use Slim\Http\Response;

/**
 * Class Output
 *
 * @package Allypost\Api
 */
class Output
{
    /**
     * Announces a success message and stops execution
     *
     * @param Response $response Slim's response object
     * @param string   $reason   A success message or name for the success
     * @param array    $data     Data to be supplied with alongside the message
     *
     * @return Response Modified Response
     */
    public static function say(Response $response, string $reason, array $data = []): Response
    {
        return self::output($response, self::sayResponse($reason, $data), 200);
    }

    /**
     * Announces an error and stops execution
     *
     * @param Response $response Slim's response object
     * @param string   $reason   A name or reason for throwing an error
     * @param array    $errors   List of errors
     * @param int      $status   The status code for the error
     *
     * @return Response Modified Response
     */
    public static function err(Response $response, string $reason, array $errors = [], int $status = 403): Response
    {
        return self::output($response, self::errResponse($reason, $errors), $status);
    }

    /**
     * Get a new standard response object
     *
     * @param string $reason A success message or name for the success
     * @param array  $data   Data to be supplied with alongside the message
     *
     * @return array
     */
    public static function sayResponse(string $reason, array $data = []): array
    {
        return self::response(false, $reason, $data);
    }

    /**
     * Get a new error response object
     *
     * @param string $reason A name or reason for throwing an error
     * @param array  $errors List of errors
     *
     * @return array The response object
     */
    public static function errResponse(string $reason, array $errors = []): array
    {
        return self::response(true, $reason, $errors);
    }

    /**
     * Generate integer value from string (used for error codes, about 5% chance of duplicates)
     *
     * @param string $error The error string
     *
     * @return int A integer based on the input string
     */
    private static function getErrorCode(string $error): int
    {
        if (strlen($error) == 1) {
            return ord($error);
        }

        $err = (int) array_reduce(str_split($error), function ($i, $char) {
            return $i ^ ord($char);
        }, 500);

        $err *= strlen($error);
        $err ^= ord(substr($error, 0, 1));
        $err ^= ord(substr($error, -1, 1));
        $err ^= ord(substr($error, (int) strlen($error) / 2, 1));

        return $err;
    }

    /**
     * Add and format errors for response
     *
     * @param array $response The response array
     * @param array $errors   Array (list) of errors
     *
     * @return array Modified response
     */
    private static function responseError(array $response, array $errors = []): array
    {
        if (isset($errors['errors'])) {
            $response['errors'] = $errors['errors'];
            unset($errors['errors']);
            $response['data'] = $errors;
        } else {
            $response['errors'] = $errors;
            $response['data'] = [];
        }

        return $response;
    }

    /**
     * Add and format data for response
     *
     * @param array $response The response array
     * @param array $data     Data array or object
     *
     * @return array Modified response
     */
    private static function responseSuccess(array $response, array $data = []): array
    {
        $response['data'] = $data;

        return $response;
    }

    /**
     * Announces a response message and stops execution
     *
     * @param bool   $isError Whether the response is an error message
     * @param string $reason  A success message or name for the response
     * @param array  $data    Data to be supplied with alongside the message
     *
     * @return array The response array
     */
    private static function response(bool $isError, string $reason, array $data = []): array
    {
        $return = [
            'error' => $isError,
            'responseCode' => self::getErrorCode($reason),
            'reason' => $reason,
            'data' => [],
            'timestamp' => time(),
        ];

        if ($isError) {
            $return = self::responseError($return, $data);
        } else {
            $return = self::responseSuccess($return, $data);
        }

        return $return;
    }

    /**
     * Output the message and halt the app
     *
     * @param Response     $response Slim's response object
     * @param array|object $array    JSON serializable data
     * @param int          $status   The HTTP status code for the response
     *
     * @return Response Modified Response
     */
    private static function output(Response $response, array $array, int $status = 200): Response
    {
        return $response->withJson($array, $status);
    }
}
