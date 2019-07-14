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
            'sku' => 'sku',
            'images' => [['image_url' => 'url']]
        ]);

        $productResponse = (object) $product;
        $productResponse->images[0] = (object) $productResponse->images[0];

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
                            $productResponse
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
        $service->bySku($product)->save();
    }

    public function testImageIsDeletedIfItDoesNotExistInNewData()
    {
        $product = $this->getBcProductData([
            'sku' => 'sku',
            'images' => [['image_url' => 'url', 'id' => 1]]
        ]);

        $productResponse = (object) $product;
        $productResponse->images[0] = (object) $productResponse->images[0];

        $this->client->expects($this->exactly(4))
            ->method('callApi')
            ->withConsecutive(
                $this->getApiCallProducts(['sku' => 'sku']),
                $this->getApiCallOptions(),
                $this->getApiCallPutProduct(),
                $this->getApiCallDeleteImage()
            )
            ->willReturnOnConsecutiveCalls(
                [
                    (object) [
                        'data' => [
                            $productResponse
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
                    null,
                    200,
                    'Content-Type: application/json'
                ]
            );
        $service = ProductToBigcommerce::make(['client' => $this->client]);
        $product['images'] = [];
        $service->bySku($product)->save();
    }
}