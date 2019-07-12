<?php declare(strict_types=1);

namespace Mrself\BigcommerceV3\ToBigcommerce;

use BigCommerce\Api\v3\Api\CatalogApi;
use BigCommerce\Api\v3\ApiClient;
use BigCommerce\Api\v3\Model\Option;
use BigCommerce\Api\v3\Model\OptionPost;
use BigCommerce\Api\v3\Model\OptionResponse;
use BigCommerce\Api\v3\Model\OptionValue;
use BigCommerce\Api\v3\Model\Product;
use BigCommerce\Api\v3\Model\ProductPost;
use BigCommerce\Api\v3\Model\ProductPut;
use BigCommerce\Api\v3\Model\Variant;
use BigCommerce\Api\v3\Model\VariantPost;
use BigCommerce\Api\v3\Model\VariantPut;

class ProductToBigcommerce extends AbstractToBigcommerce
{
    /**
     * @var Product
     */
    protected $existingData;

    protected $newData;

    /**
     * @var Option[]
     */
    protected $existingOptions;

    /**
     * @var CatalogApi
     */
    protected $catalog;

    protected $createdOptions = [];

    /**
     * @param array $data
     * @return ProductToBigcommerce
     * @throws \BigCommerce\Api\v3\ApiException
     */
    public function bySku(array $data)
    {
        $products = $this->catalog->getProducts(['sku' => $data['sku']])
            ->getData();
        if (count($products)) {
            $this->existingData = $products[0];
            $this->existingOptions = $this->catalog
                ->getOptions($this->existingData->getId())
                ->getData();
        }
        $this->newData = $data;
        return $this;
    }

    public function getExistingData(): ?Product
    {
        return $this->existingData;
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    public function save()
    {
        if ($this->existingData) {
            $this->update();
        } else {
            $this->create();
        }
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    protected function update()
    {
        $productPut = new ProductPut($this->newData);
        $this->catalog->updateProduct($this->newData['id'], $productPut);
        if ($this->optionsExist()) {
            $this->saveOptions();
        }
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    protected function create()
    {
        $productPost = new ProductPost($this->newData);
        $productPost->setVariants(null);
        $productId = $this->catalog->createProduct($productPost)
            ->getData()
            ->getId();
        if ($this->optionsExist()) {
            $this->createOptions($productId);
            $this->createVariants($productId);
        }
    }

    protected function optionsExist()
    {
        return array_key_exists('options', $this->newData) && count($this->newData['options']);
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    protected function saveOptions()
    {
        if ($this->optionsMatch()) {
            $this->updateVariants();
        } else {
            $this->removeOldOptions();
            $this->createOptions();
            $this->createVariants();
        }
    }

    /**
     * @param null $productId
     * @throws \BigCommerce\Api\v3\ApiException
     */
    protected function createOptions($productId = null)
    {
        $productId = $this->existingData ? $this->existingData->getId() : $productId;
        foreach ($this->newData['options'] as $option) {
            $option['product_id'] = $productId;
            $optionPost = new OptionPost($option);
            $this->createdOptions[] = $this->catalog->createOption($productId, $optionPost);
        }
    }

    /**
     * @param $productId
     * @throws \BigCommerce\Api\v3\ApiException
     */
    public function createVariants($productId = null)
    {
        $productId = $this->existingData ? $this->existingData->getId() : $productId;
        foreach ($this->newData['variants'] as $variant) {
            $variantPost = new VariantPost($variant);
            $variant['option_values'] = $this->
                formatVariantOptionValues($variant['option_values']);
            $this->catalog->createVariant($productId, $variantPost);
        }
    }

    protected function formatVariantOptionValues(array $values)
    {
        return array_map(function ($value) {
            $option = $this->getCreatedOption($value['display_name']);
            $value = $this->getOptionValue($option->getOptionValues(), $value['label']);
            return [
                'option_id' => $option->getId(),
                'id' => $value->getId()
            ];
        }, $values);
    }

    protected function getCreatedOption(string $name): Option
    {
        $result = array_filter($this->createdOptions, function (OptionResponse $option) use ($name) {
            return $option->getData()->getDisplayName() === $name;
        });
        return reset($result)->getData();
    }

    /**
     * @param OptionValue[] $values
     * @param $label
     * @return OptionValue
     */
    protected function getOptionValue($values, $label)
    {
        $result = array_filter($values, function (OptionValue $value) use ($label) {
            return $value->getLabel() === $label;
        });
        return reset($result);
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    protected function updateVariants()
    {
        foreach ($this->newData['variants'] as $variant) {
            $existingVariant = $this->getExistingVariant($variant);
            $variantPut = new VariantPut($variant);
            $this->catalog->updateVariant(
                $this->existingData->getId(),
                $existingVariant->getId(),
                $variantPut
            );
        }
    }

    protected function getExistingVariant($searchVariant): Variant
    {
        $variants = $this->existingData->getVariants();
        $result = array_filter($variants, function (Variant $variant) use ($searchVariant) {
            return $variant->getSku() === $searchVariant['sku'];
        });
        return reset($result);
    }

    protected function optionsMatch()
    {
        foreach ($this->newData['options'] as $option) {
            if (!$this->optionExists($option)) {
                return false;
            }
        }
        return true;
    }

    protected function optionExists($compareOption)
    {
        $result = array_filter($this->existingOptions, function ($option) use ($compareOption) {
            $existingCompareOption = $this->makeCompareOptionFromInstance($option);
            $compareOption = $this->makeCompareOptionFromArray($compareOption);
            return $existingCompareOption === $compareOption;
        });
        return count($result);
    }

    protected function makeCompareOptionFromInstance(Option $option)
    {
        $values = array_map(function (OptionValue $value) {
            return [
                'label' => $value->getLabel()
            ];
        }, $option->getOptionValues());
        return [
            'display_name' => $option->getDisplayName(),
            'option_values' => $values
        ];
    }

    protected function makeCompareOptionFromArray($option)
    {
        $values = array_map(function ($value) {
            return [
                'label' => $value['label']
            ];
        }, $option['option_values']);
        return [
            'display_name' => $option['display_name'],
            'option_values' => $values
        ];
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    protected function removeOldOptions()
    {
        foreach ($this->existingOptions as $option) {
            $this->catalog->deleteOptionById($this->existingData->getId(), $option->getId());
        }
    }

    protected function onOptionsResolve()
    {
        $this->catalog = new CatalogApi($this->client);
    }
}