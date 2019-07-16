<?php declare(strict_types=1);

namespace Mrself\BigcommerceV3\ToBigcommerce;

use BigCommerce\Api\v3\Api\CatalogApi;
use BigCommerce\Api\v3\ApiClient;
use BigCommerce\Api\v3\Model\Option;
use BigCommerce\Api\v3\Model\OptionPost;
use BigCommerce\Api\v3\Model\OptionResponse;
use BigCommerce\Api\v3\Model\OptionValue;
use BigCommerce\Api\v3\Model\Product;
use BigCommerce\Api\v3\Model\ProductImage;
use BigCommerce\Api\v3\Model\ProductImagePost;
use BigCommerce\Api\v3\Model\ProductImagePut;
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

    public function setNewData(array $data)
    {
        $this->newData = $data;
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
    public function update()
    {
        $productPut = new ProductPut($this->newData);
        $productPut->setVariants(null);
        $this->catalog->updateProduct($this->existingData->getId(), $productPut);
        if ($this->optionsExist()) {
            $this->saveOptions();
        }
        $this->updateImages();
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    public function create()
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
        $this->createImages($productId);
    }

    protected function createImages($productId)
    {
        if (!array_key_exists('images', $this->newData)) {
            return;
        }

        foreach ($this->newData['images'] as $image) {
            $imagePost = new ProductImagePost($image);
            $this->catalog->createProductImage($productId, $imagePost);
        }
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    protected function updateImages()
    {
        if (!array_key_exists('images', $this->newData)) {
            return;
        }
        if ($this->hasExtraImages()) {
            $this->removeImages();
            $this->createAllImages();
        } else {
            $this->createNewExtraImages();
        }
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    protected function hasExtraImages(): bool
    {
        if (!array_key_exists('oldImages', $this->newData)) {
            return false;
        }

        foreach ($this->newData['oldImages'] as $image) {
            if (!$this->isNewImage($image['image_url'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    protected function removeImages()
    {
        $images = $this->catalog
            ->getProductImages($this->existingData->getId())
            ->getData();
        foreach ($images as $image) {
            $this->catalog->deleteProductImage($this->existingData->getId(), $image->getId());
        }
    }

    protected function isNewImage(string $url): bool
    {
        $result = array_filter($this->newData['images'], function ($image) use ($url) {
            return $image['image_url'] === $url;
        });
        return !!count($result);
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    protected function createNewExtraImages()
    {
        foreach ($this->newData['images'] as $image) {
            if (!$this->isExistingImage($image['image_url'])) {
                $imagePost = new ProductImagePost($image);
                $this->catalog->createProductImage($this->existingData->getId(), $imagePost);
            }
        }
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    protected function createAllImages()
    {
        foreach ($this->newData['images'] as $image) {
            $imagePost = new ProductImagePost($image);
            $this->catalog->createProductImage($this->existingData->getId(), $imagePost);
        }
    }

    protected function isExistingImage(string $url): bool
    {
        if (!array_key_exists('oldImages', $this->newData)) {
            return false;
        }

        $images = $this->newData['oldImages'];
        $result = array_filter($images, function ($image) use ($url) {
            return $image['image_url'] === $url;
        });
        return !!count($result);
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
            if ($this->existingData->getVariants()) {
                $this->updateVariants();
            } else {
                $this->recreateOptions();
            }
        } else {
            $this->recreateOptions();
        }
    }

    /**
     * @throws \BigCommerce\Api\v3\ApiException
     */
    protected function recreateOptions()
    {
        $this->removeOldOptions();
        $this->createOptions();
        $this->createVariants();
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
            $variant['option_values'] = $this->
                formatVariantOptionValues($variant['option_values']);
            $variantPost = new VariantPost($variant);
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