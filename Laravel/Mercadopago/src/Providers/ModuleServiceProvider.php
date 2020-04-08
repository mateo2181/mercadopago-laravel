<?php

namespace Laravel\Mercadopago\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Laravel\Mercadopago\Models\OrderMP::class
    ];
}