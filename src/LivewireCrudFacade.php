<?php

namespace Sagor110090\LivewireCrud;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Georgechitechi\LivewireCrud\Skeleton\SkeletonClass
 */
class LivewireCrudFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'livewire-crud';
    }
}
