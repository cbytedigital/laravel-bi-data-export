<?php

return [
    /**
     * Define models for exporting
     * Models are required to use the 'BiExportable' trait
     *
     * Example:
     * \App\Models\Model::class
     *
     */
    'models' => [
        // \App\Models\Model::class
    ],

    /**
     * Define tables for exporting
     * In case the table does not use a model, you can define the table and columns here
     *
     * Examples:
     *
     * Get all columns
     * 'table' => [
     *     'columns' => '*'
     * ],
     *
     * Get specific columns
     * 'table' => [
     *     'columns' => [
     *         'id',
     *         'name'
     *     ]
     * ],
     *
     * Get specific columns and replace the value to redact sensitive information
     * 'table' => [
     *     'columns' => [
     *         'id',
     *         'name',
     *         'email'
     *     ],
     *     'hidden' => [
     *         'email'
     *     ],
     *     'hidden_text' => 'REDACTED'
     * ],
     *
     */
    'tables' => [
        // 'table' => [
        //     'columns' => '*'
        // ]
    ],

    /**
     * Determines the export action. You can define your own implementation here.
     */
    'export_job' => \ExportBiToCsv::class,

    /**
     * Determines the export location.
     */
    'export_disk' => env('BI_EXPORT_DISK', 's3'),

    /**
     * Default replacement value if not overwritten by the model or tables config.
     */
    'default_hidden_text' => env('BI_HIDDEN_TEXT', 'REDACTED'),

    /**
     * Ability to add a prefix to the filename. For example: {prefix}table.sql
     */
    'filename_prefix' => env('BI_FILENAME_PREFIX'),

    /**
     * Ability to add a suffix to the filename. For example: table{suffix}.sql
     */
    'filename_suffix' => env('BI_FILENAME_SUFFIX')
];
