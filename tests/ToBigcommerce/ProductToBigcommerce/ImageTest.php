<?php declare(strict_types=1);

namespace Mrself\BigcommerceV3\Tests\ToBigcommerce\ProductToBigcommerce;

use Mrself\BigcommerceV3\ToBigcommerce\ProductToBigcommerce;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    use ProductToBigcommerceTrait;

    public function testImageIsCreatedIfItDoesNotExist()
    {
        $product = $this->getBcProductData([
            'sku' => 'sku'
        ]);

        $this->client->expects($this->exactly(4))
            ->method('callApi')
            ->withConsecutive(
                $this->getApiCallProducts(['sku' => 'sku']),
                $this->getApiCallOptions(),
                $this->getApiCallPutProduct(),
                $this->getApiCallPostImage()
            )
            ->willReturnOnConsecutiveCalls(
                [
                    (object) [
                        'data' => [
                            (object) $product
                        ],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => [
                            (object) $this->getBcOptionData()
                        ],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => (object) $product,
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => [
                            (object) ['image_url' => 'url']
                        ],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ]
            );
        $service = ProductToBigcommerce::make(['client' => $this->client]);
        $product['images'] = [['image_url' => 'url']];
        $service->bySku($product)->save();
    }

    public function testImageIsNotCreatedIfItExists()
    {
        $product = $this->getBcProductData([
            'sku' => 'sku'
        ]);

        $this->client->expects($this->exactly(3))
            ->method('callApi')
            ->withConsecutive(
                $this->getApiCallProducts(['sku' => 'sku']),
                $this->getApiCallOptions(),
                $this->getApiCallPutProduct()
            )
            ->willReturnOnConsecutiveCalls(
                [
                    (object) [
                        'data' => [
                            (object) $product
                        ],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => [
                            (object) $this->getBcOptionData()
                        ],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => (object) $product,
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ]
            );
        $service = ProductToBigcommerce::make(['client' => $this->client]);
        $product['oldImages'] = [['image_url' => 'url']];
        $product['images'] = [['image_url' => 'url']];
        $service->bySku($product)->save();
    }

    public function testImageIsDeletedIfItDoesNotExistInNewData()
    {
        $product = $this->getBcProductData([
            'sku' => 'sku'
        ]);

        $this->client->expects($this->exactly(5))
            ->method('callApi')
            ->withConsecutive(
                $this->getApiCallProducts(['sku' => 'sku']),
                $this->getApiCallOptions(),
                $this->getApiCallPutProduct(),
                $this->getApiCallImages(),
                $this->getApiCallDeleteImage()
            )
            ->willReturnOnConsecutiveCalls(
                [
                    (object) [
                        'data' => [
                            (object) $product
                        ],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => [
                            (object) $this->getBcOptionData()
                        ],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => (object) $product,
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => [
                            (object) ['image_url' => 'url', 'id' => 1]
                        ],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    null,
                    200,
                    'Content-Type: application/json'
                ]
            );
        $service = ProductToBigcommerce::make(['client' => $this->client]);
        $product['images'] = [];
        $product['oldImages'] = [['image_url' => 'url']];
        $service->bySku($product)->save();
    }

    public function testImageIsCreatedForNewProduct()
    {
        $product = $this->getBcProductData([
            'sku' => 'sku'
        ]);

        $this->client->expects($this->exactly(3))
            ->method('callApi')
            ->withConsecutive(
                $this->getApiCallProducts(['sku' => 'sku']),
                $this->getApiCallPostProduct(),
                $this->getApiCallPostImage()
            )
            ->willReturnOnConsecutiveCalls(
                [
                    (object) [
                        'data' => [],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => (object) $product,
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => [
                            (object) ['image_url' => 'url', 'id' => 1]
                        ],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ]
            );
        $service = ProductToBigcommerce::make(['client' => $this->client]);
        $product['images'] = [['image_url' => 'url']];
        $service->bySku($product)->save();
    }
}