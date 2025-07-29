<?php

namespace CbyteDigital\BiDataExport\Tests\Feature;

use CbyteDigital\BiDataExport\BiDataExportServiceProvider;
use CbyteDigital\BiDataExport\Tests\TestCase;

class BasicTest extends TestCase
{
    public function testPackageIsProperlyLoaded()
    {
        // Verify the service provider is registered
        $providers = $this->app->getLoadedProviders();
        $this->assertArrayHasKey(BiDataExportServiceProvider::class, $providers);
        
        // Verify configuration is available
        $this->assertNotNull(config('bi-export'));
        
        // Verify the package namespace is properly autoloaded
        $this->assertTrue(class_exists('CbyteDigital\BiDataExport\BiDataExportServiceProvider'));
        $this->assertTrue(class_exists('CbyteDigital\BiDataExport\Console\ExportBiDataCommand'));
        $this->assertTrue(class_exists('CbyteDigital\BiDataExport\Dto\BiExport'));
        $this->assertTrue(class_exists('CbyteDigital\BiDataExport\Jobs\ExportBiToCsv'));
        $this->assertTrue(trait_exists('CbyteDigital\BiDataExport\Traits\BiExportable'));
    }

    public function testPackageHasCorrectConfiguration()
    {
        $config = config('bi-export');
        
        // Verify all required configuration keys exist
        $requiredKeys = [
            'models',
            'tables', 
            'export_job',
            'export_disk',
            'export_csv_delimiter',
            'default_hidden_text',
            'filename_prefix',
            'filename_suffix'
        ];
        
        foreach ($requiredKeys as $key)
            $this->assertArrayHasKey($key, $config, "Configuration key '$key' is missing");
        
        // Verify default job class is correct
        $this->assertEquals(
            'CbyteDigital\BiDataExport\Jobs\ExportBiToCsv',
            $config['export_job']
        );
    }
}
