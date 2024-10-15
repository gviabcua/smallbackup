<?php namespace Gviabcua\SmallBackup\Console;

use Exception;
use Log;
use Gviabcua\SmallBackup\Classes\Console\BackupCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Gviabcua\SmallBackup\Classes\StorageBackupManager;

class BackupStorage extends BackupCommand
{
    /**
     * @var string The console command name.
     */
    protected $name = 'smallbackup:storage';

    /**
     * @var string The console command description.
     */
    protected $description = 'Backup current or default CMS storage resource.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $manager = new StorageBackupManager();

        $this->cleanup($manager);

        try {
            $this->output->write('Backup...');
            $file = $manager->backup($this->argument('name'), boolval($this->option('once')));
            $this->output->success(
                trans('gviabcua.smallbackup::lang.backup.flash.successfull_backup', ['file' => $file])
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            $this->output->error("Backup failed! " . $ex->getMessage());
        }

        $this->output->write('Done.');

    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL, 'Another CMS storage resource name.'],
        ];
    }
}
