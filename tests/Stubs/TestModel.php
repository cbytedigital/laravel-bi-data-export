<?php

namespace CbyteDigital\BiDataExport\Tests\Stubs;

use CbyteDigital\BiDataExport\Traits\BiExportable;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use BiExportable;

    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'email',
        'sensitive_data',
        'public_data'
    ];

    protected $biExportable = [
        'id',
        'name',
        'email',
        'sensitive_data',
        'public_data'
    ];

    protected $biHidden = [
        'sensitive_data'
    ];

    protected $biHiddenText = 'HIDDEN';
} 