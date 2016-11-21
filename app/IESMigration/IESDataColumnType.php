<?php namespace App\IESMigration;

class IESDataColumnType
{
    const INT_PATTERN = '/^[-]{0,1}[0-9]{1,}$/';
    const FLOAT_PATTERN = '/^[-]{0,1}([0-9]{1,}\.[0-9]{1,}|\.[0-9]{1,}|[0-9]{1,})$/';

    const INDEX_COLUMN_NAME = 'classid';

    const STRING = 1;
    const BOOLEAN = 2;
    const INTEGER = 3;
    const FLOAT = 4;
}