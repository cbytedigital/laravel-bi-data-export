<?php

namespace CbyteDigital\BiDataExport\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class TestModelWithoutBiExportable extends Model
{
    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'email',
        'sensitive_data',
        'public_data'
    ];
} 