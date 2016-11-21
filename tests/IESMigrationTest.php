<?php

use App\IESMigration\IESDataColumn;
use App\IESMigration\IESDataColumnType;

class IESMigrationTest extends TestCase {
    /**
     * IESDataColumn tests
     */
    public function testNullable() {
        $dataColumn = new IESDataColumn('test');
        $dataColumn->evaluate("abc");
        $dataColumn->evaluate("NULL");

        $dataColumn->finish();

        $this->assertTrue($dataColumn->isNullable());
    }

    public function testNotNullable() {
        $dataColumn = new IESDataColumn('test');
        $dataColumn->evaluate("abc");

        $dataColumn->finish();

        $this->assertFalse($dataColumn->isNullable());
    }

    public function testIntegerDataColumn() {
        $dataColumn = new IESDataColumn('test');
        $dataColumn->evaluate("1");
        $dataColumn->evaluate("0");
        $dataColumn->evaluate("123");
        $dataColumn->evaluate("NULL");

        $dataColumn->finish();

        $this->assertEquals(IESDataColumnType::INTEGER, $dataColumn->getDataType());
    }

    public function testBooleanDataColumn() {
        $dataColumn = new IESDataColumn('test');
        $dataColumn->evaluate("1");
        $dataColumn->evaluate("0");
        $dataColumn->evaluate("NULL");

        $dataColumn->finish();

        $this->assertEquals(IESDataColumnType::BOOLEAN, $dataColumn->getDataType());
    }

    public function testBooleanFloatDataColumn() {
        $dataColumn = new IESDataColumn('test');
        $dataColumn->evaluate("0");
        $dataColumn->evaluate("0.0");
        $dataColumn->evaluate("1");
        $dataColumn->evaluate("NULL");

        $dataColumn->finish();

        $this->assertEquals(IESDataColumnType::FLOAT, $dataColumn->getDataType());
    }

    public function testFloatDataColumn() {
        $dataColumn = new IESDataColumn('test');
        $dataColumn->evaluate("1.4");
        $dataColumn->evaluate("0");
        $dataColumn->evaluate("NULL");

        $dataColumn->finish();

        $this->assertEquals(IESDataColumnType::FLOAT, $dataColumn->getDataType());
    }

    public function testStringDataColumn() {
        $dataColumn = new IESDataColumn('test');
        $dataColumn->evaluate("1.4");
        $dataColumn->evaluate("0");
        $dataColumn->evaluate("false");
        $dataColumn->evaluate("abc");
        $dataColumn->evaluate("NULL");

        $dataColumn->finish();

        $this->assertEquals(IESDataColumnType::STRING, $dataColumn->getDataType());
    }

    public function testMeaningless() {
        $dataColumn = new IESDataColumn('test');
        $dataColumn->evaluate("0");
        $dataColumn->evaluate("0");
        $dataColumn->evaluate("0");
        $dataColumn->evaluate("0");

        $dataColumn->finish();

        $this->assertTrue($dataColumn->isMeaningless());
    }

    public function testNotMeaningless() {
        $dataColumn = new IESDataColumn('test');
        $dataColumn->evaluate("0");
        $dataColumn->evaluate("0");
        $dataColumn->evaluate("0");
        $dataColumn->evaluate("1");

        $dataColumn->finish();

        $this->assertFalse($dataColumn->isMeaningless());
    }
}
