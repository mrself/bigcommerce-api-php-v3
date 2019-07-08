<?php declare(strict_types=1);

namespace Mrself\BigcommerceV3;

use BigCommerce\Api\v3\ApiClient;
use BigCommerce\Api\v3\Configuration;
use Mrself\Container\Registry\ContainerRegistry;
use Mrself\Options\WithOptionsTrait;

class Client
{
    use WithOptionsTrait;

    public function setup(string $storeHash, string $clientId, string $apiToken)
    {
        $config = new Configuration();
        $config->setHost("https://api.bigcommerce.com/stores/$storeHash/v3");
        $config->setClientId($clientId);
        $config->setAccessToken($apiToken);
        $client = new ApiClient($config);
        ContainerRegistry::get('Mrself\\BigcommerceV3')
            ->set(ApiClient::class, $client);
    }
}