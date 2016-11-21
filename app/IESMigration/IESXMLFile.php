<?php namespace App\IESMigration;

/**
 * Encapsulates an IES XML file information, including its structure and its data.
 * Class IESXMLFile
 * @package App\IESMigration
 */
class IESXMLFile
{
    const INSERT_CUT_SIZE = 200;

    protected $fileName;
    /**
     * @var IESDataColumn[]
     */
    protected $dataColumns = [];
    protected $data = [];

    public function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Parses the XML file, gathering values contained in the files, along a list of attributes in it.
     */
    public function parse()
    {
        $this->parseXMLFileToData($this->fileName);

        return true;
    }

    /**
     * Parses the overrides for this file.
     */
    public function overrides()
    {
        // Finds override path
        $fileParts = pathinfo($this->fileName);
        $fileNameFolder = $fileParts['dirname'];
        $fileName = $fileParts['basename'];
        $overrideFileName = "{$fileNameFolder}/USA/{$fileName}";

        // If an override file is found, apply it
        if (is_file($overrideFileName)) {
            $this->parseXMLFileToData($overrideFileName);

            return true;
        } else {
            return false;
        }
    }

    protected function parseXMLFileToData($xmlFile) {
        $loadedXML = simplexml_load_file($xmlFile);

        foreach ($loadedXML->Class as $class) {
            $dataEntry = [];

            foreach ($class->attributes() as $attributeName => $attributeValue) {
                $dataEntry[strtolower($attributeName)] = $attributeValue;
            }

            // Override check
            $classId = intval($dataEntry['classid']);
            if (isset($this->data[$classId])) {
                foreach ($dataEntry as $key => $value) {
                    $this->data[$classId][$key] = $value;
                }
            } else {
                $this->data[$classId] = $dataEntry;
            }
        }
    }

    /**
     * Internal function which parses the file's data and attributes to figure out its structure.
     */
    protected function getDataStructure()
    {
        $distinctColumns = [];

        // Checks for distinct columns
        foreach ($this->data as $data) {
            foreach ($data as $dataKey => $value) {
                if (!in_array($dataKey, $distinctColumns)) {
                    $distinctColumns[] = $dataKey;
                }
            }
        }

        // Data type check
        foreach ($distinctColumns as $column) {
            $dataTypeInstance = new IESDataColumn($column);

            foreach ($this->data as $data) {
                if (isset($data[$column])) {
                    $dataTypeInstance->evaluate($data[$column]);
                } else {
                    $dataTypeInstance->evaluate("null");
                }
            }

            $dataTypeInstance->finish();
            $this->dataColumns[$column] = $dataTypeInstance;
        }
    }


    /**
     * Executes the SQL table creation script for this XML file.
     */
    protected function executeCreateScript()
    {
        $columnDefinitions = [];

        foreach ($this->dataColumns as $dataColumn) {
            if (!$dataColumn->isMeaningless()) {
                $columnDefinitions[] = $dataColumn->getSQLDefinition();
            }
        }

        // Ignores empty table definitions
        if (count($columnDefinitions) == 0)
            return;

        $columnDefinitions = implode(",", $columnDefinitions);

        app('db')->statement("CREATE TABLE {$this->getTableName()} ($columnDefinitions);");
    }

    /**
     * Attempts to execute the SQL data creation script for the current XML file.
     * @throws \Exception
     */
    protected function executeDataScript($dataStartIdx, $dataEndIdx)
    {
        $insertColumns = [];

        // INSERT INTO ... section
        foreach ($this->dataColumns as $dataColumn) {
            if (!$dataColumn->isMeaningless()) {
                $insertColumns[] = "`" . $dataColumn->getColumnName() . "`";
            }
        }

        // Ignores empty table definitions
        if (count($insertColumns) == 0)
            return;

        $insertColumns = implode(",", $insertColumns);

        // VALUES ... section
        $rawLines = [];
        for ($dataIdx = $dataStartIdx; $dataIdx <= $dataEndIdx; $dataIdx++) {
            $data = $this->data[$dataIdx];

            // Checks if ID is valid. Duplicates does not occur due to how the parser works
            if ($data['classid'] == 0)
                continue;

            $lineData = [];

            foreach ($this->dataColumns as $dataColumn) {
                if (!$dataColumn->isMeaningless()) {
                    $columnName = $dataColumn->getColumnName();

                    if (isset($data[$columnName])) {
                        $parsedValue = $dataColumn->parseValue($data[$columnName]);

                        // Checks for string value
                        if ($dataColumn->getDataType() == IESDataColumnType::STRING || $parsedValue === NULL) {
                            $lineData[] = app('db')->connection()->getPdo()->quote($parsedValue);
                        } else {
                            $lineData[] = $parsedValue;
                        }
                    } else {
                        $lineData[] = "NULL";
                    }
                }
            }

            $rawLines[] = "(" . implode(",", $lineData) . ")";
        }

        $rawLines = implode(",", $rawLines);
        app('db')->statement("INSERT INTO {$this->getTableName()} ($insertColumns) VALUES $rawLines;");
    }

    /**
     * Performs database operations for migrating the XML file's data into database.
     */
    public function migrate()
    {
        $this->getDataStructure();

        app('db')->transaction(function () {
            $this->executeCreateScript();
        });

        // Cuts data script in multiple parts
        for ($part = 0; $part < ceil(count($this->data) / IESXMLFile::INSERT_CUT_SIZE); $part++) {
            $partStartIdx = $part * IESXMLFile::INSERT_CUT_SIZE;
            $partEndIdx = ($part + 1) * IESXMLFile::INSERT_CUT_SIZE - 1;

            // Out of bounds check for end index
            if ($partEndIdx > count($this->data)) {
                $partEndIdx = count($this->data) - 1;
            }

            // Executes partial data script
            app('db')->transaction(function () use ($partStartIdx, $partEndIdx) {
                $this->executeDataScript($partStartIdx, $partEndIdx);
            });
        }

        return true;
    }

    /**
     * Cleanups memory after this XML file processing is finished.
     */
    public function cleanup()
    {
        $this->data = null;
        $this->dataColumns = null;
    }

    /**
     * Gets the table name associated to this XML file.
     */
    public function getTableName()
    {
        return strtolower(basename($this->fileName, ".xml"));
    }

    public function flattenData() {
        $this->data = array_values($this->data);
    }
}