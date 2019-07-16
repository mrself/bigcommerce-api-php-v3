<?php declare(strict_types=1);

namespace Mrself\BigcommerceV3;

use BigCommerce\Api\v3\ApiException;

class ApiClient extends \BigCommerce\Api\v3\ApiClient
{
    public function callApi($resourcePath, $method, $queryParams, $postData, $headerParams, $responseType = null, $endpointPath = null)
    {
        try {
            return parent::callApi(
                $resourcePath,
                $method,
                $queryParams,
                $postData,
                $headerParams,
                $responseType,
                $endpointPath
            );
        } catch (ApiException $e) {
            // if response is 'Too many requests'
            if ($e->getCode() === 429) {
                /** @var string[] $headers */
                $headers = $e->getResponseHeaders();
                $timeout = $headers['X-Rate-Limit-Time-Reset-Ms'];
                $timeout = (int) ceil($timeout / 60);
                sleep($timeout);

                return parent::callApi(
                    $resourcePath,
                    $method,
                    $queryParams,
                    $postData,
                    $headerParams,
                    $responseType,
                    $endpointPath
                );
            }
            throw $e;
        }
    }
}