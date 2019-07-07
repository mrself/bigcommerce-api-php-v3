<?php declare(strict_types=1);

namespace Mrself\Bigcommerce\Dev;

use BigCommerce\Api\v3\Model\OptionPost;
use BigCommerce\Api\v3\Model\ProductPost;
use BigCommerce\Api\v3\Model\ProductPut;
use BigCommerce\Api\v3\Model\VariantPost;
use BigCommerce\Api\v3\Model\VariantPut;

trait ApiCallsMockTrait
{
    protected function getApiCallProducts($params = [])
    {
        return [
            '/catalog/products',
            'GET',
            $params,
            '',
            $this->getRequestHeaders(),
            '\BigCommerce\Api\v3\Model\ProductCollectionResponse',
            '/catalog/products'
        ];
    }

    protected function getApiCallPostProduct()
    {
        return [
            '/catalog/products',
            'POST',
            [],
            $this->isInstanceOf(ProductPost::class),
            $this->getRequestHeaders(),
            '\BigCommerce\Api\v3\Model\ProductResponse',
            '/catalog/products'
        ];
    }

    protected function getApiCallPutProduct()
    {
        return [
            '/catalog/products/1',
            'PUT',
            [],
            $this->isInstanceOf(ProductPut::class),
            $this->getRequestHeaders(),
            '\BigCommerce\Api\v3\Model\ProductResponse',
            '/catalog/products/{product_id}'
        ];
    }

    protected function getApiCallOptions($params = [])
    {
        return [
            '/catalog/products/1/options',
            'GET',
            $params,
            '',
            $this->getRequestHeaders(),
            '\BigCommerce\Api\v3\Model\OptionCollectionResponse',
            '/catalog/products/{product_id}/options'
        ];
    }

    protected function getApiCallPostOption()
    {
        return [
            '/catalog/products/1/options',
            'POST',
            [],
            $this->isInstanceOf(OptionPost::class),
            $this->getRequestHeaders(),
            '\BigCommerce\Api\v3\Model\OptionResponse',
            '/catalog/products/{product_id}/options'
        ];
    }

    protected function getApiCallDeleteOption()
    {
        return [
            '/catalog/products/1/options/1',
            'DELETE',
            [],
            '',
            $this->getRequestHeaders(),
            null,
            '/catalog/products/{product_id}/options/{option_id}'
        ];
    }

    protected function getApiCallPostVariant()
    {
        return [
            '/catalog/products/1/variants',
            'POST',
            [],
            $this->isInstanceOf(VariantPost::class),
            $this->getRequestHeaders(),
            '\BigCommerce\Api\v3\Model\VariantResponse',
            '/catalog/products/{product_id}/variants'
        ];
    }

    protected function getApiCallPutVariant()
    {
        return [
            '/catalog/products/1/variants/1',
            'PUT',
            [],
            $this->isInstanceOf(VariantPut::class),
            $this->getRequestHeaders(),
            '\BigCommerce\Api\v3\Model\VariantResponse',
            '/catalog/products/{product_id}/variants/{variant_id}'
        ];
    }

    protected function getRequestHeaders()
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }
}