<?php

namespace CbyteDigital\BiDataExport\Tests\Unit;

use CbyteDigital\BiDataExport\Dto\BiExport;
use CbyteDigital\BiDataExport\Tests\TestCase;

class BiExportTest extends TestCase
{
    public function testCanCreateBiExportFromStringData()
    {
        $biExport = BiExport::from('users', 'all');

        $this->assertEquals('users', $biExport->table);
        $this->assertNull($biExport->columns);
        $this->assertNull($biExport->hidden);
        $this->assertEquals('', $biExport->hiddenText);
    }

    public function testCanCreateBiExportFromArrayWithColumns()
    {
        $data = [
            'columns' => ['id', 'name', 'email'],
        ];

        $biExport = BiExport::from('users', $data);

        $this->assertEquals('users', $biExport->table);
        $this->assertEquals(['id', 'name', 'email'], $biExport->columns);
        $this->assertNull($biExport->hidden);
        $this->assertEquals('', $biExport->hiddenText);
    }

    public function testCanCreateBiExportWithHiddenColumns()
    {
        $data = [
            'columns' => ['id', 'name', 'email', 'password'],
            'hidden' => ['password'],
            'hidden_text' => 'HIDDEN'
        ];

        $biExport = BiExport::from('users', $data);

        $this->assertEquals('users', $biExport->table);
        $this->assertEquals(['id', 'name', 'email', 'password'], $biExport->columns);
        $this->assertEquals(['password'], $biExport->hidden);
        $this->assertEquals('HIDDEN', $biExport->hiddenText);
    }

    public function testColumnsCanBeNullWhenNotArray()
    {
        $data = [
            'columns' => '*',
        ];

        $biExport = BiExport::from('users', $data);

        $this->assertEquals('users', $biExport->table);
        $this->assertNull($biExport->columns);
    }

    public function testUsesEmptyStringAsDefaultHiddenText()
    {
        $data = [
            'columns' => ['id', 'name'],
            'hidden' => ['secret']
        ];

        $biExport = BiExport::from('users', $data);

        $this->assertEquals('', $biExport->hiddenText);
    }

    public function testCanHandleEmptyArrayData()
    {
        $biExport = BiExport::from('empty_table', []);

        $this->assertEquals('empty_table', $biExport->table);
        $this->assertNull($biExport->columns);
        $this->assertNull($biExport->hidden);
        $this->assertEquals('', $biExport->hiddenText);
    }
} 