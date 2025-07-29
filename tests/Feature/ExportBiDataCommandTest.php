<?php

namespace CbyteDigital\BiDataExport\Tests\Feature;

use CbyteDigital\BiDataExport\Jobs\ExportBiToCsv;
use CbyteDigital\BiDataExport\Tests\Stubs\TestModel;
use CbyteDigital\BiDataExport\Tests\Stubs\TestModelWithoutBiExportable;
use CbyteDigital\BiDataExport\Tests\TestCase;
use Illuminate\Support\Facades\Queue;

class ExportBiDataCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function testCommandExistsAndCanBeExecuted()
    {
        // With sync queue, it shows "Finished" instead of "Dispatched"
        config(['queue.default' => 'sync']);
        
        $this->artisan('bi:export-data')
            ->expectsOutput('Finished 0 export jobs.')
            ->assertExitCode(0);
    }

    public function testCommandDispatchesJobsForConfiguredModels()
    {
        config(['bi-export.models' => [TestModel::class]]);
        config(['queue.default' => 'sync']);

        $this->artisan('bi:export-data')
            ->expectsOutput('Finished 1 export jobs.')
            ->assertExitCode(0);

        Queue::assertPushed(ExportBiToCsv::class, function ($job) {
            return $job->biExport->table === 'test_models';
        });
    }

    public function testCommandDispatchesJobsForConfiguredTables()
    {
        config(['bi-export.tables' => [
            'custom_table' => [
                'columns' => ['id', 'name']
            ]
        ]]);
        config(['queue.default' => 'sync']);

        $this->artisan('bi:export-data')
            ->expectsOutput('Finished 1 export jobs.')
            ->assertExitCode(0);

        Queue::assertPushed(ExportBiToCsv::class, function ($job) {
            return $job->biExport->table === 'custom_table' 
                && $job->biExport->columns === ['id', 'name'];
        });
    }

    public function testCommandDispatchesJobsForBothModelsAndTables()
    {
        config([
            'bi-export.models' => [TestModel::class],
            'bi-export.tables' => [
                'users' => [
                    'columns' => '*'
                ]
            ]
        ]);
        config(['queue.default' => 'sync']);

        $this->artisan('bi:export-data')
            ->expectsOutput('Finished 2 export jobs.')
            ->assertExitCode(0);

        Queue::assertPushed(ExportBiToCsv::class, 2);
    }

    public function testCommandIgnoresModelsWithoutBiExportableTrait()
    {
        config(['bi-export.models' => [
            TestModel::class,
            TestModelWithoutBiExportable::class
        ]]);
        config(['queue.default' => 'sync']);

        $this->artisan('bi:export-data')
            ->expectsOutput('Finished 1 export jobs.')
            ->assertExitCode(0);

        // Only TestModel should be processed
        Queue::assertPushed(ExportBiToCsv::class, 1);
    }

    public function testCommandUsesCustomExportJob()
    {
        // Create a proper custom job class
        $customJobClass = new class(\CbyteDigital\BiDataExport\Dto\BiExport::from('test', [])) extends ExportBiToCsv {
            // Custom job implementation
        };

        config([
            'bi-export.export_job' => get_class($customJobClass),
            'bi-export.models' => [TestModel::class]
        ]);
        config(['queue.default' => 'sync']);

        $this->artisan('bi:export-data')
            ->assertExitCode(0);

        Queue::assertPushed(get_class($customJobClass));
    }

    public function testCommandShowsFinishedMessageWithSyncQueue()
    {
        config([
            'queue.default' => 'sync',
            'bi-export.models' => [TestModel::class]
        ]);

        $this->artisan('bi:export-data')
            ->expectsOutput('Finished 1 export jobs.')
            ->assertExitCode(0);
    }

    public function testCommandShowsDispatchedMessageWithAsyncQueue()
    {
        Queue::fake(); // This makes it behave like non-sync queue
        
        config([
            'queue.default' => 'redis', // Non-sync queue
            'bi-export.models' => [TestModel::class]
        ]);

        $this->artisan('bi:export-data')
            ->expectsOutput('Dispatched 1 export jobs.')
            ->assertExitCode(0);
    }

    public function testCommandPassesCorrectBiExportDataToJob()
    {
        config(['bi-export.models' => [TestModel::class]]);
        config(['queue.default' => 'sync']);

        $this->artisan('bi:export-data')
            ->assertExitCode(0);

        Queue::assertPushed(ExportBiToCsv::class, function ($job) {
            $biExport = $job->biExport;
            
            return $biExport->table === 'test_models'
                && $biExport->columns === ['id', 'name', 'email', 'sensitive_data', 'public_data']
                && $biExport->hidden === ['sensitive_data']
                && $biExport->hiddenText === 'HIDDEN';
        });
    }

    public function testCommandWorksWithEmptyConfiguration()
    {
        config([
            'bi-export.models' => [],
            'bi-export.tables' => []
        ]);
        config(['queue.default' => 'sync']);

        $this->artisan('bi:export-data')
            ->expectsOutput('Finished 0 export jobs.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function testCommandHandlesNullConfiguration()
    {
        config([
            'bi-export.models' => null,
            'bi-export.tables' => null
        ]);
        config(['queue.default' => 'sync']);

        $this->artisan('bi:export-data')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }
} 