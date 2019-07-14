<?php declare(strict_types=1);

namespace Mrself\BigcommerceV3\Dev;

trait ResourceTrait
{
    protected $bcCategoryIndex = 0;

    protected $bcProductIndex = 0;

    protected $bcHookIndex = 0;

    protected $bcOptionIndex  = 0;

    protected function getBcProductData(array $source = [])
    {
        $this->bcProductIndex++;
        $data = array_merge([
            'id' => $this->bcProductIndex,
            'name' => 'name' . $this->bcProductIndex,
            'price' => $this->bcProductIndex . '.00',
            'retail_price' => ($this->bcProductIndex + 1) . '.00',
            'sale_price' => ($this->bcProductIndex + 2) . '.00',
            'sku' => 'sku' . $this->bcProductIndex,
            'custom_url' => 'url' . $this->bcProductIndex,
            'description' => 'desc' . $this->bcProductIndex,
            'is_visible' => false,
            'search_keywords' => 'keywords' . $this->bcProductIndex,
            'warranty' => 'warranty' . $this->bcProductIndex,
            'date_created' => 'Fri, 21 Sep 2012 02:31:01 +0000',
            'primary_image' => (object) [
                "standard_url" => 'url' . $this->bcProductIndex,
            ],
            'categories' => [],
            'images' => []
        ], $source);
        $this->formatBcProductCategories($data);
        return $data;
    }

    protected function getBcOptionData(array $source = []): array
    {
        $this->bcOptionIndex++;
        return array_merge([
            'id' => $this->bcOptionIndex,
            'product_id' => 1,
            'display_name' => 'display name' . $this->bcOptionIndex,
            'name' => 'name' . $this->bcOptionIndex,
            'type' => 'radio_buttons',
            'option_values' => [
                [
                    'label' => 'label' . rand(),
                    'id' => rand()
                ]
            ]
        ], $source);
    }

    protected function getBcVariantData(array $source = []): array
    {
        return array_merge([
            'sku' => 'sku1'
        ], $source);
    }

    protected function formatBcProductCategories(array &$source)
    {
        $source['categories'] = array_map(function ($category) {
            if (is_object($category)) {
                return $category->id;
            }
            return $category;
        }, $source['categories']);
    }

    protected function getBcHook(array $source = [])
    {
        $this->bcHookIndex++;
        $source = array_merge([
            'id' => $this->bcHookIndex,
            'scope' => 'scope' . $this->bcHookIndex,
            'store_hash' => 'store_hash' . $this->bcHookIndex,
            'destination' => 'url' . $this->bcHookIndex
        ], $source);
        return (object) $source;
    }
}