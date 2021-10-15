<?php

namespace Sagor110090\LivewireCrud\Tests;

use Orchestra\Testbench\TestCase;
use Sagor110090\LivewireCrud\LivewireCrudServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [LivewireCrudServiceProvider::class];
    }

    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
