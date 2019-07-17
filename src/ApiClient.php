<?php declare(strict_types=1);

namespace Mrself\BigcommerceV3;

use BigCommerce\Api\v3\ApiException;
use Psr\Log\LoggerInterface;

class ApiClient extends \BigCommerce\Api\v3\ApiClient
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

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

            if ($this->logger) {
                $this->logger->error('Bigcommerce error message: ' . $e->getResponseBody());
            }

            throw $e;
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}