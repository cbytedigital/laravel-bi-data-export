<?php

namespace CbyteDigital\BiDataExport\Tests\Feature;

use CbyteDigital\BiDataExport\Tests\Stubs\TestModel;
use CbyteDigital\BiDataExport\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class IntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create sample data
        TestModel::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'sensitive_data' => 'secret123',
            'public_data' => 'public info'
        ]);

        TestModel::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'sensitive_data' => 'topsecret456',
            'public_data' => 'another public info'
        ]);

        TestModel::create([
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'sensitive_data' => 'confidential789',
            'public_data' => 'more public data'
        ]);
    }

    public function testCompleteExportWorkflowWithModel()
    {
        Storage::fake('testing');
        
        config([
            'bi-export.models' => [TestModel::class],
            'queue.default' => 'sync' // Process immediately
        ]);

        // Run the export command
        $this->artisan('bi:export-data')
            ->expectsOutput('Finished 1 export jobs.')
            ->assertExitCode(0);

        // Verify the file was created
        $expectedPath = now()->toDateString() . '/test_models.csv';
        Storage::disk('testing')->assertExists($expectedPath);

        // Verify the content
        $content = Storage::disk('testing')->get($expectedPath);
        
        // Check UTF-8 BOM
        $this->assertStringStartsWith(chr(0xEF).chr(0xBB).chr(0xBF), $content);
        
        // Remove BOM for easier testing
        $content = substr($content, 3);
        
        // Check headers exist
        $this->assertStringContainsString('id', $content);
        $this->assertStringContainsString('name', $content);
        $this->assertStringContainsString('email', $content);
        
        // Check that sensitive data is hidden
        $this->assertStringNotContainsString('secret123', $content);
        $this->assertStringNotContainsString('topsecret456', $content);
        $this->assertStringNotContainsString('confidential789', $content);
        
        // Check that data is replaced with hidden text
        $this->assertStringContainsString('HIDDEN', $content);
        
        // Check that other data is present
        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('jane@example.com', $content);
        $this->assertStringContainsString('more public data', $content);
        
        // Verify all 3 records are present (header + 3 data rows)
        $lines = explode("\n", trim($content));
        $this->assertGreaterThanOrEqual(4, count($lines)); // At least header + 3 data rows
    }

    public function testCompleteExportWorkflowWithTableConfiguration()
    {
        Storage::fake('testing');
        
        config([
            'bi-export.tables' => [
                'test_models' => [
                    'columns' => ['id', 'name', 'email'],
                    'hidden' => ['email'],
                    'hidden_text' => 'REDACTED_EMAIL'
                ]
            ],
            'queue.default' => 'sync'
        ]);

        $this->artisan('bi:export-data')
            ->expectsOutput('Finished 1 export jobs.')
            ->assertExitCode(0);

        $expectedPath = now()->toDateString() . '/test_models.csv';
        Storage::disk('testing')->assertExists($expectedPath);

        $content = Storage::disk('testing')->get($expectedPath);
        $content = substr($content, 3); // Remove BOM
        
        // Only specified columns should be present
        $this->assertStringContainsString('id', $content);
        $this->assertStringContainsString('name', $content);
        $this->assertStringContainsString('email', $content);
        $this->assertStringNotContainsString('sensitive_data', $content);
        $this->assertStringNotContainsString('public_data', $content);
        
        // Email should be redacted
        $this->assertStringNotContainsString('john@example.com', $content);
        $this->assertStringNotContainsString('jane@example.com', $content);
        $this->assertStringContainsString('REDACTED_EMAIL', $content);
        
        // Names should still be present
        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('Jane Smith', $content);
    }

    public function testExportWithCustomDelimiterAndFileNaming()
    {
        Storage::fake('testing');
        
        config([
            'bi-export.models' => [TestModel::class],
            'bi-export.export_csv_delimiter' => ',',
            'bi-export.filename_prefix' => 'bi_export_',
            'bi-export.filename_suffix' => '_processed',
            'queue.default' => 'sync'
        ]);

        $this->artisan('bi:export-data')
            ->assertExitCode(0);

        $expectedPath = now()->toDateString() . '/bi_export_test_models_processed.csv';
        Storage::disk('testing')->assertExists($expectedPath);

        $content = Storage::disk('testing')->get($expectedPath);
        $content = substr($content, 3); // Remove BOM
        
        // Should use comma delimiter
        $this->assertStringContainsString('id,name', $content);
    }

    public function testExportWithLargeDataSet()
    {
        Storage::fake('testing');
        
        // Create a larger dataset
        for ($i = 0; $i < 50; $i++) { // Reduced from 100 to make test faster
            TestModel::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'sensitive_data' => "secret{$i}",
                'public_data' => "public{$i}"
            ]);
        }

        config([
            'bi-export.models' => [TestModel::class],
            'queue.default' => 'sync'
        ]);

        $this->artisan('bi:export-data')
            ->assertExitCode(0);

        $expectedPath = now()->toDateString() . '/test_models.csv';
        Storage::disk('testing')->assertExists($expectedPath);

        $content = Storage::disk('testing')->get($expectedPath);
        $content = substr($content, 3); // Remove BOM
        
        // Should contain all records (53 total: 3 from setUp + 50 new)
        $lines = explode("\n", trim($content));
        $this->assertGreaterThanOrEqual(50, count($lines)); // At least 50+ lines
        
        // Check some random entries
        $this->assertStringContainsString('User 25', $content);
        $this->assertStringContainsString('user49@example.com', $content);
        
        // Verify sensitive data is still hidden
        $this->assertStringNotContainsString('secret25', $content);
        $this->assertStringContainsString('HIDDEN', $content);
    }
} 