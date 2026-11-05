<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Never let a test reach a real payment provider or other external API.
        \Illuminate\Support\Facades\Http::preventStrayRequests();
    }
}
