<?php

namespace Skydiver\PocketConnector;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Skydiver\PocketConnector\Skeleton\SkeletonClass
 */
class PocketConnectorFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pocket-connector';
    }
}
