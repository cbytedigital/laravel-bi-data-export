<?php

namespace CbyteDigital\BiDataExport\Tests\Unit;

use CbyteDigital\BiDataExport\Tests\Stubs\TestModel;
use CbyteDigital\BiDataExport\Tests\TestCase;

class BiExportableTraitTest extends TestCase
{
    public function testGetBiExportValuesReturnsCorrectStructure()
    {
        $model = new TestModel();
        $exportValues = $model->getBiExportValues();

        $this->assertIsArray($exportValues);
        $this->assertArrayHasKey('columns', $exportValues);
        $this->assertArrayHasKey('hidden', $exportValues);
        $this->assertArrayHasKey('hidden_text', $exportValues);
    }

    public function testGetBiExportColumnsReturnsConfiguredColumns()
    {
        $model = new TestModel();
        $columns = $model->getBiExportColumns();

        $this->assertEquals([
            'id',
            'name',
            'email',
            'sensitive_data',
            'public_data'
        ], $columns);
    }

    public function testGetBiExportColumnsReturnsAsteriskWhenNotConfigured()
    {
        $model = new class extends TestModel {
            protected $biExportable = null;
        };

        $columns = $model->getBiExportColumns();

        $this->assertEquals('*', $columns);
    }

    public function testGetBiHiddenColumnsReturnsConfiguredHidden()
    {
        $model = new TestModel();
        $hidden = $model->getBiHiddenColumns();

        $this->assertEquals(['sensitive_data'], $hidden);
    }

    public function testGetBiHiddenColumnsReturnsNullWhenNotConfigured()
    {
        $model = new class extends TestModel {
            protected $biHidden = null;
        };

        $hidden = $model->getBiHiddenColumns();

        $this->assertNull($hidden);
    }

    public function testGetBiHiddenTextReturnsConfiguredText()
    {
        $model = new TestModel();
        $hiddenText = $model->getBiHiddenText();

        $this->assertEquals('HIDDEN', $hiddenText);
    }

    public function testGetBiHiddenTextReturnsConfigValueWhenNotConfigured()
    {
        $model = new class extends TestModel {
            protected $biHiddenText = null;
        };

        $hiddenText = $model->getBiHiddenText();

        $this->assertEquals('REDACTED', $hiddenText);
    }

    public function testCompleteExportValuesStructure()
    {
        $model = new TestModel();
        $exportValues = $model->getBiExportValues();

        $expectedStructure = [
            'columns' => ['id', 'name', 'email', 'sensitive_data', 'public_data'],
            'hidden' => ['sensitive_data'],
            'hidden_text' => 'HIDDEN'
        ];

        $this->assertEquals($expectedStructure, $exportValues);
    }
} 