<?php

namespace CbyteDigital\BiDataExport\Console;

use CbyteDigital\BiDataExport\Traits\BiExportable;
use CbyteDigital\BiDataExport\Dto\BiExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExportBiDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bi:export-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger configured job to export BI data to files.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        Log::info('BI Export: Starting data export command');

        $models = config('bi-export.models');
        $tables = config('bi-export.tables');

        Log::info('BI Export: Loaded config', [
            'models_count' => count($models),
            'tables_count' => count($tables),
            'models' => $models,
            'tables' => array_keys($tables)
        ]);

        // Include tables from config for export
        $exports = collect($tables);
        Log::info('BI Export: Added tables to exports', ['table_exports' => $exports->keys()->toArray()]);

        // Include models from config for export
        collect($models)
            ->filter(function ($m) {
                $hasTraits = in_array(BiExportable::class, class_uses_recursive($m));
                Log::info('BI Export: Checking model', [
                    'model' => $m,
                    'has_bi_exportable_trait' => $hasTraits,
                    'traits' => class_uses_recursive($m)
                ]);
                return $hasTraits;
            })
            ->each(function ($m) use ($exports) {
                try {
                    $mi = new $m();
                    $table = $mi->getTable();
                    $exportValues = $mi->getBiExportValues();

                    Log::info('BI Export: Adding model to exports', [
                        'model' => $m,
                        'table' => $table,
                        'export_values' => $exportValues
                    ]);

                    $exports->put($table, $exportValues);
                } catch (\Exception $e) {
                    Log::error('BI Export: Error processing model', [
                        'model' => $m,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $this->error("Error processing model {$m}: " . $e->getMessage());
                }
            });

        Log::info('BI Export: Final exports collection', [
            'exports_count' => $exports->count(),
            'export_tables' => $exports->keys()->toArray()
        ]);

        // Map values to dto and dispatch export job
        $exportJob = config('bi-export.export_job');
        Log::info('BI Export: Using export job class', ['job_class' => $exportJob]);

        $exports
            ->map(function ($v, $k) {
                Log::info('BI Export: Creating BiExport DTO', ['table' => $k, 'values' => $v]);
                return BiExport::from($k, $v);
            })
            ->each(function ($e) use ($exportJob) {
                Log::info('BI Export: Dispatching job', [
                    'table' => $e->table,
                    'job_class' => $exportJob
                ]);
                dispatch(new $exportJob($e));
            });

        $syncQueue = config('queue.default') === 'sync';
        $message = ($syncQueue ? 'Finished ' : 'Dispatched ').$exports->count().' export jobs.';

        Log::info('BI Export: Command completed', [
            'sync_queue' => $syncQueue,
            'jobs_count' => $exports->count()
        ]);

        $this->info($message);

        return Command::SUCCESS;
    }
}
