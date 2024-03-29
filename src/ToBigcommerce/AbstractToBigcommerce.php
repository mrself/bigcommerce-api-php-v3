<?php declare(strict_types=1);

namespace Mrself\BigcommerceV3\ToBigcommerce;

use Mrself\BigcommerceV3\ApiClient;
use Mrself\Options\Annotation\Option;
use Mrself\Options\WithOptionsTrait;

class AbstractToBigcommerce
{
    use WithOptionsTrait;

    /**
     * @Option()
     * @var ApiClient
     */
    protected $client;
}