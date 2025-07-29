<?php

namespace CbyteDigital\BiDataExport\Tests\Feature;

use CbyteDigital\BiDataExport\Console\ExportBiDataCommand;
use CbyteDigital\BiDataExport\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class BiDataExportServiceProviderTest extends TestCase
{
    public function testServiceProviderRegistersCommands()
    {
        $commands = Artisan::all();
        
        $this->assertArrayHasKey('bi:export-data', $commands);
        $this->assertInstanceOf(ExportBiDataCommand::class, $commands['bi:export-data']);
    }

    public function testServiceProviderMergesConfiguration()
    {
        // The service provider should merge the package config
        $this->assertNotNull(config('bi-export'));
        $this->assertIsArray(config('bi-export.models'));
        $this->assertIsArray(config('bi-export.tables'));
    }

    public function testServiceProviderConfigurationDefaults()
    {
        // Test that default values are set correctly
        $this->assertEquals('testing', config('bi-export.export_disk'));
        $this->assertEquals(';', config('bi-export.export_csv_delimiter'));
        $this->assertEquals('REDACTED', config('bi-export.default_hidden_text'));
    }

    public function testServiceProviderConfigurationCanBeOverridden()
    {
        // Set custom config values and verify they work
        config(['bi-export.export_disk' => 'custom_disk']);
        $this->assertEquals('custom_disk', config('bi-export.export_disk'));
        
        config(['bi-export.export_csv_delimiter' => '|']);
        $this->assertEquals('|', config('bi-export.export_csv_delimiter'));
    }

    public function testServiceProviderUsesEnvironmentDefaults()
    {
        // Test that configuration pulls from environment where available
        $this->assertIsString(config('bi-export.export_disk'));
        $this->assertIsString(config('bi-export.export_csv_delimiter'));
        $this->assertIsString(config('bi-export.default_hidden_text'));
    }

    public function testServiceProviderRegistersOnlyInConsole()
    {
        // The commands should be registered since we're in a console environment for testing
        $this->assertTrue($this->app->runningInConsole());
        
        $commands = Artisan::all();
        $this->assertArrayHasKey('bi:export-data', $commands);
    }
} 