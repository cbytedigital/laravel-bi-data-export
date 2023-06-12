<?php

namespace CbyteDigital\BiDataExport\Console;

use CbyteDigital\BiDataExport\Traits\BiExportable;
use CbyteDigital\BiDataExport\Dto\BiExport;
use Illuminate\Console\Command;

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
        $models = config('bi-export.models');
        $tables = config('bi-export.tables');

        // Include tables from config for export
        $exports = collect($tables);

        // Include models from config for export
        collect($models)
            ->filter(fn ($m) => in_array(BiExportable::class, class_uses_recursive($m)))
            ->each(function ($m) use ($exports) {
                $mi = new $m();
                $exports->put($mi->getTable(), $mi->getBiExportValues());
            });

        // Map values to dto and dispatch export job
        $exportJob = config('bi-export.export_job');
        $exports
            ->map(fn ($v, $k) => BiExport::from($k, $v))
            ->each(fn ($e) => dispatch(new $exportJob($e)));

        $syncQueue = config('queue.default') === 'sync';
        $this->info(($syncQueue ? 'Finished ' : 'Dispatched ').$exports->count().' export jobs.');

        return Command::SUCCESS;
    }
}
