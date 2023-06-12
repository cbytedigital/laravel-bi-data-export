<?php

namespace CbyteDigital\BiDataExport\Traits;

trait BiExportable
{
    /**
     * Returns the values for exporting
     *
     * @return array
     */
    public function getBiExportValues(): array
    {
        return [
            'columns' => $this->getBiExportColumns(),
            'hidden' => $this->getBiHiddenColumns(),
            'hidden_text' => $this->getBiHiddenText(),
        ];
    }

    /**
     * Returns the columns defined for exporting
     *
     * @return array|string
     */
    public function getBiExportColumns(): array|string
    {
        return $this->biExportable ?? '*';
    }

    /**
     * Returns the hidden columns
     *
     * @return array|null
     */
    public function getBiHiddenColumns(): array|null
    {
        return $this->biHidden ?? null;
    }

    /**
     * Returns the columns defined for exporting
     *
     * @return string
     */
    public function getBiHiddenText(): string
    {
        return $this->biHiddenText ?? config('bi-export.default_hidden_text');
    }
}
