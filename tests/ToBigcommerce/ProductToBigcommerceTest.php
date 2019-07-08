<?php declare(strict_types=1);

namespace Mrself\BigcommerceV3\Tests\Functional\ToBigcommerce;

use BigCommerce\Api\v3\ApiClient;
use Mrself\BigcommerceV3\Dev\BigcommerceTrait;
use Mrself\BigcommerceV3\ToBigcommerce\ProductToBigcommerce;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductToBigcommerceTest extends TestCase
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

    public function testBySkuSearchesProduct()
    {
        $this->client->expects($this->exactly(2))
            ->method('callApi')
            ->withConsecutive(
                $this->getApiCallProducts(['sku' => 'sku']),
                $this->getApiCallOptions()
            )
            ->willReturnOnConsecutiveCalls(
                [
                    (object) [
                        'data' => [
                            (object) $this->getBcProductData()
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
                ]
            );
        $service = ProductToBigcommerce::make(['client' => $this->client]);
        $service->bySku(['sku' => 'sku']);
        $this->assertEquals(1, $service->getExistingData()->getId());
    }

    public function testBySkuDoesNotSetExistingDataIfThereNoProductsFound()
    {
        $this->client->expects($this->once())
            ->method('callApi')
            ->withConsecutive(
                $this->getApiCallProducts(['sku' => 'sku'])
            )
            ->willReturnOnConsecutiveCalls(
                [
                    (object) [
                        'data' => [],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ]
            );
        $service = ProductToBigcommerce::make(['client' => $this->client]);
        $service->bySku(['sku' => 'sku']);
        $this->assertNull($service->getExistingData());
    }

    public function testSaveCreatesProductIfThereIsNoOneBySku()
    {
        $option = $this->getBcOptionData();
        $product = $this->getBcProductData([
            'sku' => 'sku',
            'options' => [
                [$option]
            ],
            'variants' => [
                [
                    'sku' => 'sku1',
                    'option_values' => [
                        [
                            'display_name' => $option['display_name'],
                            'label' => $option['option_values'][0]['label']
                        ]
                    ]
                ]
            ]
        ]);
        $responseProduct = $product;
        unset($responseProduct['options'], $responseProduct['variants']);

        $optionResponse = (object) $option;
        $optionResponse->option_values[0] = (object) $optionResponse->option_values[0];

        $this->client->expects($this->exactly(4))
            ->method('callApi')
            ->withConsecutive(
                $this->getApiCallProducts(['sku' => 'sku']),
                $this->getApiCallPostProduct(),
                $this->getApiCallPostOption(),
                $this->getApiCallPostVariant()
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
                        'data' => (object) $responseProduct,
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => $optionResponse,
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => (object) ['sku' => 'sku1'],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ]
            );
        $service = ProductToBigcommerce::make(['client' => $this->client]);

        $service->bySku($product)->save();
    }

    public function testItCreatesProductWithoutOptions()
    {
        $product = $this->getBcProductData([
            'sku' => 'sku',
        ]);
        $responseProduct = $product;

        $this->client->expects($this->exactly(2))
            ->method('callApi')
            ->withConsecutive(
                $this->getApiCallProducts(['sku' => 'sku']),
                $this->getApiCallPostProduct()
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
                        'data' => (object) $responseProduct,
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ]
            );
        $service = ProductToBigcommerce::make(['client' => $this->client]);

        $service->bySku($product)->save();
    }

    public function testSaveUpdatesProductIfThereIsSuchBySku()
    {
        $option = $this->getBcOptionData();
        $product = $this->getBcProductData([
            'sku' => 'sku',
            'options' => [
                $option
            ],
            'variants' => [
                [
                    'id' => 1,
                    'sku' => 'sku1',
                    'option_values' => [
                        [
                            'display_name' => $option['display_name'],
                            'label' => $option['option_values'][0]['label']
                        ]
                    ]
                ]
            ]
        ]);
        $responseProduct = $product;
        unset($responseProduct['options']);
        $responseProduct['variants'][0] = (object) $responseProduct['variants'][0];
        $responseProduct['variants'][0]->option_values[0] = (object) $responseProduct['variants'][0]
            ->option_values[0];

        $optionResponse = (object) $option;
        $optionResponse->option_values[0] = (object) $optionResponse->option_values[0];

        $this->client->expects($this->exactly(4))
            ->method('callApi')
            ->withConsecutive(
                $this->getApiCallProducts(['sku' => 'sku']),
                $this->getApiCallOptions(),
                $this->getApiCallPutProduct(),
                $this->getApiCallPutVariant()
            )
            ->willReturnOnConsecutiveCalls(
                [
                    (object) [
                        'data' => [(object) $responseProduct],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => [
                            $optionResponse
                        ],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => (object) $responseProduct,
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                [
                    (object) [
                        'data' => (object) ['sku' => 'sku1'],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ]
            );
        $service = ProductToBigcommerce::make(['client' => $this->client]);

        $service->bySku($product)->save();
    }

    public function testSaveRecreatesOptions()
    {
        $option = $this->getBcOptionData();
        $product = $this->getBcProductData([
            'sku' => 'sku',
            'options' => [
                $option
            ],
            'variants' => [
                [
                    'id' => 1,
                    'sku' => 'sku1',
                    'option_values' => [
                        [
                            'display_name' => $option['display_name'],
                            'label' => $option['option_values'][0]['label']
                        ]
                    ]
                ]
            ]
        ]);
        $responseProduct = $product;
        unset($responseProduct['options']);
        $responseProduct['variants'][0] = (object) $responseProduct['variants'][0];
        $responseProduct['variants'][0]->option_values[0] = (object) $responseProduct['variants'][0]
            ->option_values[0];

        $optionResponse = (object) $option;
        $optionResponse->option_values[0] = (object) $optionResponse->option_values[0];

        $this->client->expects($this->exactly(6))
            ->method('callApi')
            ->withConsecutive(
                $this->getApiCallProducts(['sku' => 'sku']),
                $this->getApiCallOptions(),
                $this->getApiCallPutProduct(),
                $this->getApiCallDeleteOption(),
                $this->getApiCallPostOption(),
                $this->getApiCallPostVariant()
            )
            ->willReturnOnConsecutiveCalls(
                // get products
                [
                    (object) [
                        'data' => [(object) $responseProduct],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                // get options
                [
                    (object) [
                        'data' => [
                            $optionResponse
                        ],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                // update product
                [
                    (object) [
                        'data' => (object) $responseProduct,
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                // delete option
                [
                    (object) [
                        'data' => null,
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                // create option
                [
                    (object) [
                        'data' => $optionResponse,
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ],
                // create variant
                [
                    (object) [
                        'data' => ['sku' => 'sku1'],
                        'meta' => []
                    ],
                    200,
                    'Content-Type: application/json'
                ]
            );
        $service = ProductToBigcommerce::make(['client' => $this->client]);

        $product['options'][0]['display_name'] = 'option2';
        $service->bySku($product)->save();
    }

    protected function setUp()
    {
        parent::setUp();
        $this->client = $this->getMockBuilder(ApiClient::class)
            ->setMethods(['callApi'])
            ->getMock();
        $this->service = ProductToBigcommerce::make(['client' => $this->client]);
    }
}