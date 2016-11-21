<?php namespace App\IESMigration;

/**
 * Class IESDataColumn Defines an IES data column entry.
 * @package App\IESMigration
 */
class IESDataColumn
{
    public static $NULL_VALUES = ["null", "n/a", "none"];
    public static $BOOLEAN_VALUES = [
        "yes"   => true,
        "no"    => false,
        "true"  => true,
        "false" => false,
        "1"     => true,
        "0"     => false,
    ];

    protected $columnName;
    protected $nullable = false;
    protected $dataType;
    protected $booleanValuesCheck = [];
    protected $meaningless = false;
    protected $maximumLength = 0;
    protected $intLowestValue = 0;
    protected $intHighestValue = 0;

    const INITIAL_COLUMN_LENGTH = 5;
    const COLUMN_LENGTH_STEPS = 2;

    /**
     * @param $columnName Name of the IES data column.
     */
    public function __construct($columnName)
    {
        $this->dataType = IESDataColumnType::INTEGER; // Columns are assumed as float by default
        $this->columnName = $columnName;
    }

    /**
     * Starts and continues the evaluation of this column's properties, considering the value given in parameter.
     * @param $value Value to evaluate.
     */
    public function evaluate($value)
    {
        $lowerValue = trim(strtolower($value));

        // Max length
        $this->maximumLength = strlen($value) > $this->maximumLength ? strlen($value) : $this->maximumLength;

        // Nullable check
        if (in_array($lowerValue, IESDataColumn::$NULL_VALUES)) {
            // Simply a null value, skip the rest of the evaluation
            $this->nullable = true;

            return;
        }

        // Boolean check
        if (count($this->booleanValuesCheck) < 3 && !in_array($lowerValue, $this->booleanValuesCheck)) {
            $this->booleanValuesCheck[] = $lowerValue;
        }

        // The data type was already assumed to be a string, so skip the database check
        if ($this->dataType == IESDataColumnType::STRING) {
            return;
        }

        // Evaluate data type
        if ($this->dataType == IESDataColumnType::INTEGER && !preg_match(IESDataColumnType::INT_PATTERN, $lowerValue)) {
            // Not a number, let's check if it's a float
            $this->dataType = IESDataColumnType::FLOAT;
        }

        if ($this->dataType == IESDataColumnType::FLOAT && !preg_match(IESDataColumnType::FLOAT_PATTERN, $lowerValue)) {
            // Not a number, it's either a string or an boolean (assume string for now)
            $this->dataType = IESDataColumnType::STRING;
        }

        // If still an integer, evaluate lowest/max size
        if ($this->dataType == IESDataColumnType::INTEGER) {
            $intValue = intval($lowerValue);
            $this->intLowestValue = $intValue < $this->intLowestValue ? $intValue : $this->intLowestValue;
            $this->intHighestValue = $intValue > $this->intHighestValue ? $intValue : $this->intHighestValue;
        }
    }

    /**
     * Finishes the evaluation of this IES file column, finalizing the type decision of this column.
     */
    public function finish()
    {
        // Meaningless column, skip other checks
        if (count($this->booleanValuesCheck) <= 1) {
            $this->meaningless = true;
            return;
        }

        // Checks if it's an integer within range, if not, change type to string
        if ($this->dataType == IESDataColumnType::INTEGER && $this->getIntegerSubType() === NULL) {
            $this->dataType = IESDataColumnType::STRING;
            return;
        }

        if ($this->dataType != IESDataColumnType::FLOAT &&
            count($this->booleanValuesCheck) <= 3
        ) { // It might be a boolean (3 possible values instead of 2, since NULL is also a value)
            $isBoolean = true;
            $boolCount = 0;

            foreach ($this->booleanValuesCheck as $value) {
                if (!in_array($value, IESDataColumn::$NULL_VALUES) &&
                    !array_key_exists($value, IESDataColumn::$BOOLEAN_VALUES)
                ) {
                    $isBoolean = false;
                } else if (array_key_exists($value, IESDataColumn::$BOOLEAN_VALUES)) {
                    $boolCount++;
                }
            }

            if ($isBoolean && $boolCount == 2) {
                $this->dataType = IESDataColumnType::BOOLEAN;
            }
        }
    }

    /**
     * Gets the full SQL definition of this column (in a CREATE TABLE format).
     * @return string SQL definition of this column
     */
    public function getSQLDefinition()
    {
        // Index columns are always of the same type
        if ($this->columnName == IESDataColumnType::INDEX_COLUMN_NAME) {
            return "{$this->columnName} INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY";
        }

        $type = "";
        $nullSuffix = $this->nullable ? "NULL" : "NOT NULL";

        if ($this->dataType == IESDataColumnType::STRING) {
            $type = "VARCHAR({$this->getColumnLength()})";
        } else if ($this->dataType == IESDataColumnType::BOOLEAN) {
            $type = "BIT";
        } else if ($this->dataType == IESDataColumnType::FLOAT) {
            $type = "DECIMAL(13,4)";
        } else if ($this->dataType == IESDataColumnType::INTEGER) {
            $subType = $this->getIntegerSubType();

            if ($subType == IESDataColumnIntegerTypes::SIGNED_BYTE)
                $type = "TINYINT SIGNED";
            else if ($subType == IESDataColumnIntegerTypes::SIGNED_SHORT)
                $type = "SMALLINT SIGNED";
            else if ($subType == IESDataColumnIntegerTypes::SIGNED_INTEGER)
                $type = "INTEGER SIGNED";
            else if ($subType == IESDataColumnIntegerTypes::SIGNED_LONG)
                $type = "BIGINT SIGNED";
            else if ($subType == IESDataColumnIntegerTypes::UNSIGNED_BYTE)
                $type = "TINYINT UNSIGNED";
            else if ($subType == IESDataColumnIntegerTypes::UNSIGNED_SHORT)
                $type = "SMALLINT UNSIGNED";
            else if ($subType == IESDataColumnIntegerTypes::UNSIGNED_INTEGER)
                $type = "INTEGER UNSIGNED";
            else if ($subType == IESDataColumnIntegerTypes::UNSIGNED_LONG)
                $type = "BIGINT UNSIGNED";
        }

        return "`{$this->columnName}` {$type} {$nullSuffix}";
    }

    /**
     * Converts a value corresponding to the information known about it in this instance.
     * @param $value Value to convert
     * @return mixed Converted value
     * @throws Exception Internal error
     */
    public function parseValue($value)
    {
        $loweredValue = trim(strtolower($value));

        // Parses NULL values
        if ($this->nullable && in_array($loweredValue, IESDataColumn::$NULL_VALUES))
            return null;

        // Parses as a standard data-type
        if ($this->dataType == IESDataColumnType::STRING)
            return $value;
        else if ($this->dataType == IESDataColumnType::BOOLEAN)
            return IESDataColumn::$BOOLEAN_VALUES[$loweredValue] ? "1" : "0";
        else if ($this->dataType == IESDataColumnType::FLOAT)
            return floatval($loweredValue);
        else if ($this->dataType == IESDataColumnType::INTEGER)
            return intval($value);

        throw new \Exception("An unexpected error occured.");
    }

    /**
     * Returns the column name.
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * Returns the current data type for this instance.
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Returns the scaled column length.
     */
    public function getColumnLength()
    {
        $columnLength = self::INITIAL_COLUMN_LENGTH;
        while ($columnLength < $this->maximumLength) {
            $columnLength *= self::COLUMN_LENGTH_STEPS;
        }

        return $columnLength;
    }

    /**
     * Gets the integer type for this column (can be either byte, short, int, long all signed or unsigned). Returns null if value is out of range.
     * @return int|null
     */
    public function getIntegerSubType() {
        // Integer check
        if ($this->getDataType() != IESDataColumnType::INTEGER) {
            return null;
        }

        if ($this->isSignedInteger()) {
            if ($this->intLowestValue >= -128 && $this->intHighestValue <= 127) {
                return IESDataColumnIntegerTypes::SIGNED_BYTE;
            } else if ($this->intLowestValue >= -32768 && $this->intHighestValue <= 32767) {
                return IESDataColumnIntegerTypes::SIGNED_SHORT;
            } else if ($this->intLowestValue >= -2147483648 && $this->intHighestValue <= 2147483647) {
                return IESDataColumnIntegerTypes::SIGNED_INTEGER;
            } else if ($this->intLowestValue >= -9223372036854775808 && $this->intLowestValue <= 9223372036854775807) {
                return IESDataColumnIntegerTypes::SIGNED_LONG;
            } else {
                return null; // Not an int/too big number
            }
        } else {
            if ($this->intHighestValue <= 255) {
                return IESDataColumnIntegerTypes::UNSIGNED_BYTE;
            } else if ($this->intHighestValue <= 65535) {
                return IESDataColumnIntegerTypes::UNSIGNED_SHORT;
            } else if ($this->intHighestValue <= 4294967295) {
                return IESDataColumnIntegerTypes::UNSIGNED_INTEGER;
            } else if ($this->intHighestValue <= 18446744073709551615) {
                return IESDataColumnIntegerTypes::UNSIGNED_LONG;
            } else {
                return null; // Not an int/too big number
            }
        }
    }

    /**
     * Returns true if this integer type is signed (has negative values).
     * @return bool
     */
    public function isSignedInteger() {
        return $this->intLowestValue < 0;
    }

    /**
     * Returns true if this column is defined as nullable.
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * Returns true if this column was deemed as meaningless. Meaningless columns are scored as such when they have only 1 possible value.
     */
    public function isMeaningless()
    {
        return $this->meaningless;
    }
}