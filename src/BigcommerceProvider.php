<?php declare(strict_types=1);

namespace Mrself\BigcommerceV3;

use Mrself\Container\Container;
use Mrself\Container\Registry\ContainerRegistry;

class BigcommerceProvider
{
    public function boot()
    {
        $container = Container::make();
        ContainerRegistry::add('Mrself\\BigcommerceV3', $container);
    }
}