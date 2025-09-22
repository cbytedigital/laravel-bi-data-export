<?php

namespace CbyteDigital\BiDataExport\Jobs;

use CbyteDigital\BiDataExport\Dto\BiExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ExportBiToCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public BiExport $biExport;

    public string $delimiter;

    public string $disk;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BiExport $biExport)
    {
        $this->biExport = $biExport;
        $this->delimiter = config('bi-export.export_csv_delimiter');
        $this->disk = config('bi-export.export_disk');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $logChannel = config('bi-export.log_channel', config('logging.default'));
        $log = Log::channel($logChannel);

        $log->info('BI Export Job: Starting export', [
            'table' => $this->biExport->table,
            'columns' => $this->biExport->columns,
            'disk' => $this->disk
        ]);

        try {
            $headersWritten = false;
            $recordCount = 0;
            $chunkSize = config('bi-export.chunk_size', 1000);
            $targetFile = $this->getTargetFileName();

            $log->info('BI Export Job: Starting to process records in chunks', [
                'table' => $this->biExport->table,
                'chunk_size' => $chunkSize
            ]);

            // Create a temporary file for streaming
            $tempFile = tempnam(sys_get_temp_dir(), 'bi_export_');
            $fileHandle = fopen($tempFile, 'w');

            // Write UTF8 encoding headers
            fprintf($fileHandle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Process data in chunks to avoid memory issues
            $query = DB::table($this->biExport->table)
                ->select($this->biExport->columns ?? '*');

            // Check if table has 'id' column, otherwise find first column to order by
            $hasIdColumn = DB::getSchemaBuilder()->hasColumn($this->biExport->table, 'id');

            if ($hasIdColumn) {
                $query->orderBy('id');
                $log->info('BI Export Job: Using id column for ordering', [
                    'table' => $this->biExport->table
                ]);
            } else {
                // Get first column for ordering (chunk requires orderBy)
                $columns = DB::getSchemaBuilder()->getColumnListing($this->biExport->table);
                $firstColumn = $columns[0] ?? 'created_at';
                $query->orderBy($firstColumn);
                $log->info('BI Export Job: Using first available column for ordering', [
                    'table' => $this->biExport->table,
                    'order_column' => $firstColumn
                ]);
            }

            $logRef = $log; // for use in closure
            $query->chunk($chunkSize, function ($chunk) use (&$headersWritten, &$recordCount, $fileHandle, $logRef) {
                    foreach ($chunk as $record) {
                        $recordCount++;

                        // Write headers only once
                        if (!$headersWritten) {
                            $headersWritten = true;
                            $headers = array_keys((array) $record);
                            $logRef->info('BI Export Job: Writing headers', [
                                'table' => $this->biExport->table,
                                'headers' => $headers
                            ]);
                            fputcsv($fileHandle, $headers, $this->delimiter);
                        }

                        // Replace hidden values
                        if ($this->biExport->hidden !== null) {
                            $this->replaceHiddenValues($record);
                        }

                        // Write values
                        fputcsv($fileHandle, array_values((array) $record), $this->delimiter);
                    }

                    if ($recordCount % 10000 === 0) {
                        $logRef->info('BI Export Job: Progress update', [
                            'table' => $this->biExport->table,
                            'records_processed' => $recordCount
                        ]);
                    }

                    // Force garbage collection to free memory
                    if ($recordCount % 5000 === 0) {
                        gc_collect_cycles();
                    }
                });

            fclose($fileHandle);

            $log->info('BI Export Job: Finished processing records', [
                'table' => $this->biExport->table,
                'total_records' => $recordCount
            ]);

            // Upload the file to storage
            $log->info('BI Export Job: Writing to storage', [
                'table' => $this->biExport->table,
                'target_file' => $targetFile,
                'disk' => $this->disk
            ]);

            Storage::disk($this->disk)->putFileAs(
                dirname($targetFile),
                new \Illuminate\Http\File($tempFile),
                basename($targetFile)
            );

            // Clean up temporary file
            unlink($tempFile);

            $log->info('BI Export Job: Export completed successfully', [
                'table' => $this->biExport->table,
                'target_file' => $targetFile,
                'records_exported' => $recordCount
            ]);

        } catch (\Exception $e) {
            $log->error('BI Export Job: Export failed', [
                'table' => $this->biExport->table,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * @return string
     */
    public function getTargetFileName(): string
    {
        $fileNamePrefix = config('bi-export.filename_prefix', '');
        $fileNameSuffix = config('bi-export.filename_suffix', '');
        $fileName = $fileNamePrefix . $this->biExport->table . $fileNameSuffix;

        return now()->toDateString() . '/' . $fileName . '.csv';
    }

    private function replaceHiddenValues($value): void
    {
        foreach ($this->biExport->hidden as $hiddenColumn) {
            if (property_exists($value, $hiddenColumn)) {
                $value->{$hiddenColumn} = $this->biExport->hiddenText;
            }
        }
    }
}
