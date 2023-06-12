<?php


use CbyteDigital\BiDataExport\Dto\BiExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExportBiToCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public BiExport $biExport;

    public string $delimiter = ';';

    public string $disk;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BiExport $biExport)
    {
        $this->biExport = $biExport;
        $this->disk = config('bi-export.export_disk');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $cursor = DB::table($this->biExport->table)
            ->select($this->biExport->columns ?? '*')
            ->cursor();

        $headersWritten = false;
        $writeStream = fopen('php://temp', 'w');

        // Write UTF8 encoding headers
        fprintf($writeStream, chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($cursor as $value) {
            // Write headers
            if (! $headersWritten) {
                $headersWritten = true;
                fputcsv($writeStream, array_keys((array) $value), $this->delimiter);
            }

            // Replace hidden values
            if ($this->biExport->hidden !== null) {
                $this->replaceHiddenValues($value);
            }

            // Write values
            fputcsv($writeStream, array_values((array) $value), $this->delimiter);
        }

        // Write data to destination filesystem
        $targetFile = $this->getTargetFileName();
        Storage::disk($this->disk)->put($targetFile, $writeStream);
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

    private function replaceHiddenValues(&$value): void
    {
        foreach ($this->biExport->hidden as $hiddenColumn) {
            if (property_exists($value, $hiddenColumn)) {
                $value->{$hiddenColumn} = $this->biExport->hiddenText;
            }
        }
    }
}
