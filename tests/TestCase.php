<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Los temas de Filament se compilan con Vite; las pruebas no necesitan el manifiesto.
        $this->withoutVite();
    }
}
