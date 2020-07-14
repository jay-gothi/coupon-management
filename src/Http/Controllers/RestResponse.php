<?php

namespace Woohoo\GoapptivCoupon\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as ResponseConstants;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Response;

class RestResponse {

    /**
     * Handle errors and return Bad Request
     *
     * @param $errors
     * @return JsonResponse
     */
    public static function badRequest($errors) {
        return Response::json(
            ['success' => false, 'errors' => $errors],
            ResponseConstants::HTTP_BAD_REQUEST
        );
    }

    /**
     * Success response
     * Also prepare total details for pagination
     *
     * @param $root
     * @param $data
     * @return mixed
     */
    public static function done($root, $data) {
        if ($data instanceof LengthAwarePaginator) {
            return self::successResponse(self::paginationToArray($root, $data));
        } else if (!isset($root) && is_array($data)) {
            return self::successResponse($data);
        }
        return self::successResponse([$root => $data]);
    }

    /**
     * Return pagination to array
     *
     * @param $root
     * @param $data
     * @return array
     */
    public static function paginationToArray($root, LengthAwarePaginator $data) {
        return [
            "$root" => $data->toArray()['data'],
            "total" => $data->total()
        ];
    }

    /**
     * Success response
     *
     * @param $response
     * @return mixed
     */
    public static function successResponse($response) {
        $response['success'] = true;
        return Response::json($response);
    }

    /**
     * No content response
     *
     * @return mixed
     */
    public static function noContent() {
        return Response::json([], ResponseConstants::HTTP_NO_CONTENT);
    }

    /**
     * No route found response
     *
     * @return mixed
     */
    public static function notFound() {
        return Response::json([], ResponseConstants::HTTP_NOT_FOUND);
    }

    /**
     * No Permission Response
     *
     * @return mixed
     */
    public static function noPermission() {
        return Response::json(array("error" => "Permission denied"), ResponseConstants::HTTP_FORBIDDEN);
    }
}
