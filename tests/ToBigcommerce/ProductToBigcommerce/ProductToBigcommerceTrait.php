<?php declare(strict_types=1);

namespace Mrself\BigcommerceV3\Tests\ToBigcommerce\ProductToBigcommerce;

use BigCommerce\Api\v3\ApiClient;
use Mrself\BigcommerceV3\Dev\BigcommerceTrait;
use Mrself\BigcommerceV3\ToBigcommerce\ProductToBigcommerce;
use PHPUnit\Framework\MockObject\MockObject;

trait ProductToBigcommerceTrait
{
    use BigcommerceTrait;

    /**
     * @var ProductToBigcommerce
     */
    private $service;

    /**
     * @var ApiClient|MockObject
     */
    private $client;

    protected function setUp()
    {
        parent::setUp();
        $this->client = $this->getMockBuilder(ApiClient::class)
            ->setMethods(['callApi'])
            ->getMock();
        $this->service = ProductToBigcommerce::make(['client' => $this->client]);
    }
}