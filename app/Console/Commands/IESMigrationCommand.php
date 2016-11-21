<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use App\IESMigration\IESMigrationService;

class IESMigrationCommand extends Command  {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:ies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Performs IES migration into the active database";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $iesFolder = $this->input->getOption('ies_folder');

        $migrationService = new IESMigrationService($this);
        $migrationService->run($iesFolder);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('ies_folder', null, InputOption::VALUE_REQUIRED, 'IES folder which contains the files to migrate.'),
        );
    }
}