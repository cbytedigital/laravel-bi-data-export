<?php

namespace CbyteDigital\BiDataExport\Dto;

use Illuminate\Support\Arr;

class BiExport
{
    public string $table;

    public array|null $columns;

    public array|null $hidden;

    public string $hiddenText;

    public static function from(string $tableName, array|string $data): BiExport
    {
        $model = new BiExport();
        $model->table = $tableName;

        $columns = Arr::get($data, 'columns');
        $model->columns = is_array($columns) ? $columns : null;
        $model->hidden = Arr::get($data, 'hidden');
        $model->hiddenText = Arr::get($data, 'hidden_text') ?? '';

        return $model;
    }
}
