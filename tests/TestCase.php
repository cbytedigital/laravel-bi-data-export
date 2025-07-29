<?php

namespace CbyteDigital\BiDataExport\Tests;

use CbyteDigital\BiDataExport\BiDataExportServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpDatabase();
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            BiDataExportServiceProvider::class,
        ];
    }

    /**
     * Set up the environment.
     *
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup test database
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup filesystems
        $app['config']->set('filesystems.disks.testing', [
            'driver' => 'local',
            'root' => storage_path('app/testing'),
        ]);

        // Setup queue for testing
        $app['config']->set('queue.default', 'sync');

        // Setup package configuration
        $app['config']->set('bi-export.export_disk', 'testing');
        $app['config']->set('bi-export.export_csv_delimiter', ';');
        $app['config']->set('bi-export.default_hidden_text', 'REDACTED');
        $app['config']->set('bi-export.models', []);
        $app['config']->set('bi-export.tables', []);
        $app['config']->set('bi-export.export_job', \CbyteDigital\BiDataExport\Jobs\ExportBiToCsv::class);
        $app['config']->set('bi-export.filename_prefix', null);
        $app['config']->set('bi-export.filename_suffix', null);
    }

    /**
     * Setup the test database.
     */
    protected function setUpDatabase(): void
    {
        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('sensitive_data');
            $table->string('public_data');
            $table->timestamps();
        });
    }

    /**
     * Clean up the testing environment before the next test.
     */
    protected function tearDown(): void
    {
        Schema::dropIfExists('test_models');
        
        parent::tearDown();
    }
}
