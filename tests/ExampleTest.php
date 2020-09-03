<?php

namespace Skydiver\PocketConnector\Tests;

use Orchestra\Testbench\TestCase;
use Skydiver\PocketConnector\PocketConnectorServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [PocketConnectorServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
