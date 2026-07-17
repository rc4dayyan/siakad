<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_home_route_is_registered(): void
    {
        $route = Route::getRoutes()->match(Request::create('/', 'GET'));

        $this->assertSame('App\\Http\\Controllers\\Root\\HomeController@index', $route->getActionName());
    }
}
