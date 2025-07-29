<?php

namespace CbyteDigital\BiDataExport\Tests\Unit;

use CbyteDigital\BiDataExport\Dto\BiExport;
use CbyteDigital\BiDataExport\Jobs\ExportBiToCsv;
use CbyteDigital\BiDataExport\Tests\Stubs\TestModel;
use CbyteDigital\BiDataExport\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class ExportBiToCsvJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
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
    }

    public function testJobConstructorSetsPropertiesFromConfig()
    {
        $biExport = BiExport::from('test_models', [
            'columns' => ['id', 'name', 'email']
        ]);

        $job = new ExportBiToCsv($biExport);

        $this->assertEquals($biExport, $job->biExport);
        $this->assertEquals(';', $job->delimiter);
        $this->assertEquals('testing', $job->disk);
    }

    public function testGetTargetFileNameGeneratesCorrectPath()
    {
        $biExport = BiExport::from('test_models', []);
        $job = new ExportBiToCsv($biExport);

        $fileName = $job->getTargetFileName();
        
        $expectedDate = now()->toDateString();
        $this->assertStringContainsString($expectedDate, $fileName);
        $this->assertStringContainsString('test_models.csv', $fileName);
    }

    public function testGetTargetFileNameWithPrefixAndSuffix()
    {
        config(['bi-export.filename_prefix' => 'export_']);
        config(['bi-export.filename_suffix' => '_final']);

        $biExport = BiExport::from('users', []);
        $job = new ExportBiToCsv($biExport);

        $fileName = $job->getTargetFileName();
        
        $this->assertStringContainsString('export_users_final.csv', $fileName);
    }

    public function testHandleExportsAllColumnsWhenNotSpecified()
    {
        Storage::fake('testing');

        $biExport = BiExport::from('test_models', []);
        $job = new ExportBiToCsv($biExport);

        $job->handle();

        $fileName = $job->getTargetFileName();
        Storage::disk('testing')->assertExists($fileName);

        $content = Storage::disk('testing')->get($fileName);
        
        // Check headers (columns may be quoted or not)
        $this->assertStringContainsString('id', $content);
        $this->assertStringContainsString('name', $content);
        $this->assertStringContainsString('email', $content);
        $this->assertStringContainsString('sensitive_data', $content);
        $this->assertStringContainsString('public_data', $content);
        
        // Check data - account for possible quotes
        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('john@example.com', $content);
        $this->assertStringContainsString('Jane Smith', $content);
        $this->assertStringContainsString('jane@example.com', $content);
    }

    public function testHandleExportsSpecificColumns()
    {
        Storage::fake('testing');

        $biExport = BiExport::from('test_models', [
            'columns' => ['id', 'name', 'email']
        ]);
        $job = new ExportBiToCsv($biExport);

        $job->handle();

        $fileName = $job->getTargetFileName();
        $content = Storage::disk('testing')->get($fileName);
        
        // Should only contain specified columns
        $this->assertStringContainsString('id', $content);
        $this->assertStringContainsString('name', $content);
        $this->assertStringContainsString('email', $content);
        $this->assertStringNotContainsString('sensitive_data', $content);
        $this->assertStringNotContainsString('public_data', $content);
        
        // Check data rows
        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('john@example.com', $content);
        $this->assertStringContainsString('Jane Smith', $content);
        $this->assertStringContainsString('jane@example.com', $content);
    }

    public function testHandleReplacesHiddenColumnValues()
    {
        Storage::fake('testing');

        $biExport = BiExport::from('test_models', [
            'columns' => ['id', 'name', 'sensitive_data'],
            'hidden' => ['sensitive_data'],
            'hidden_text' => 'REDACTED'
        ]);
        $job = new ExportBiToCsv($biExport);

        $job->handle();

        $fileName = $job->getTargetFileName();
        $content = Storage::disk('testing')->get($fileName);
        
        // Original sensitive data should not be present
        $this->assertStringNotContainsString('secret123', $content);
        $this->assertStringNotContainsString('topsecret456', $content);
        
        // Should contain redacted text instead
        $this->assertStringContainsString('REDACTED', $content);
        
        // Should still contain other data
        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('Jane Smith', $content);
    }

    public function testHandleWritesUtf8BomHeader()
    {
        Storage::fake('testing');

        $biExport = BiExport::from('test_models', ['columns' => ['id', 'name']]);
        $job = new ExportBiToCsv($biExport);

        $job->handle();

        $fileName = $job->getTargetFileName();
        $rawContent = Storage::disk('testing')->get($fileName);
        
        // Check for UTF-8 BOM
        $this->assertStringStartsWith(chr(0xEF).chr(0xBB).chr(0xBF), $rawContent);
    }

    public function testHandleUsesCustomDelimiter()
    {
        Storage::fake('testing');
        config(['bi-export.export_csv_delimiter' => ',']);

        $biExport = BiExport::from('test_models', ['columns' => ['id', 'name']]);
        $job = new ExportBiToCsv($biExport);

        $job->handle();

        $fileName = $job->getTargetFileName();
        $content = Storage::disk('testing')->get($fileName);
        
        // Should use comma as delimiter
        $this->assertStringContainsString('id,name', $content);
    }

    public function testHandleWorksWithEmptyTable()
    {
        Storage::fake('testing');
        
        // Clear all test data
        TestModel::truncate();

        $biExport = BiExport::from('test_models', []);
        $job = new ExportBiToCsv($biExport);

        $job->handle();

        $fileName = $job->getTargetFileName();
        Storage::disk('testing')->assertExists($fileName);
        
        $content = Storage::disk('testing')->get($fileName);
        
        // Should contain UTF-8 BOM but no data rows, only headers would be written
        // when there's data to determine column structure
        $this->assertStringStartsWith(chr(0xEF).chr(0xBB).chr(0xBF), $content);
    }
} 