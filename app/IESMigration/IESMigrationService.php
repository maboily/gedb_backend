<?php namespace App\IESMigration;

use Illuminate\Support\Facades\Schema;

/**
 * Migration service capable of parsing IES XML files into a database.
 * Class IESMigrationService
 * @package App\IESMigration
 */
class IESMigrationService
{
    /**
     * @var \Illuminate\Console\Command
     */
    protected $cmdInstance = null;
    /**
     * @var IESXMLFile[]
     */
    protected $files = [];

    public function __construct($cmdInstance = null)
    {
        $this->cmdInstance = $cmdInstance;
    }

    public function log($message)
    {
        if (!is_null($this->cmdInstance)) {
            $this->cmdInstance->info($message);
        }
    }

    public function error($message)
    {
        if (!is_null($this->cmdInstance)) {
            $this->cmdInstance->error($message);
        }
    }

    public function run($folder)
    {
        $fileNames = $this->getParseableFiles($folder);

        $this->log("Found " . count($fileNames) . " file(s) to parse");

        foreach ($fileNames as $fileName) {
            $this->files[] = new IESXMLFile($fileName);
        }

        $this->checkTableCollisions();

        foreach ($this->files as $file) {
            $file->parse();
            $file->overrides();
            $file->flattenData();
            $file->migrate();
            $file->cleanup();

            $this->log("Migration for " . $file->getTableName() . " complete.");
        }

        $this->cleanup();
    }

    protected function getParseableFiles($folder)
    {
        return glob($folder . "/datatable_*.xml");
    }

    protected function checkTableCollisions()
    {
        foreach ($this->files as $file) {
            if (Schema::hasTable($file->getTableName())) {
                throw new \Exception("Table {$file->getTableName()} already exists");
            }
        }
    }

    protected function cleanup()
    {
        $this->files = null;
    }
} 