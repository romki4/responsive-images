<?php

namespace Romki4\ResponsiveImages\Facades;

use Illuminate\Support\Facades\Facade;

class ResponsiveImagesFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'responsive-images';
    }
}
