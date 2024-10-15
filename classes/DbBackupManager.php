<?php namespace Gviabcua\SmallBackup\Classes;

use Db;
use File;
use Exception;
use Winter\Storm\Filesystem\Zip;
use Gviabcua\SmallBackup\Models\Settings;

class DbBackupManager extends BackupManager
{
    /**
     * Backup file prefix
     *
     * @var string
     */
    protected $prefix = 'gsb-db-';

    /**
     * Backup DB by connection name (null = default)
     *
     * @param string|null $resource connection name
     * @param bool $once do not overwrite existing backup file
     * @return string file with current backup
     */
    public function backup(string $resource = null, bool $once = false): string
    {
        $connectionName = $resource ?: config('database.default');
        $connectionDriver = config('database.connections.' . $connectionName . '.driver');

        if ($connectionDriver == 'mysql') {
            $filename = $this->prefix . now()->format('Y-m-d') . '.sql';
            $pathname = $this->folder . DIRECTORY_SEPARATOR . $filename;

            if (!$once || !File::exists($this->getUseCompression() ? $pathname . '.zip' : $pathname)) {
                $stream = (new Drivers\Mysql(
                        Db::connection($connectionName),
                        $this->getExcludedTables(),
                        $this->getCustomMapping()
                    ))->backupStream();
                File::put(
                    $pathname,
                    $stream
                );
            }
        } elseif ($connectionDriver == 'sqlite') {
            $filename = $this->prefix . now()->format('Y-m-d') . '.sqlite';
            $pathname = $this->folder . DIRECTORY_SEPARATOR . $filename;

            if (!$once || !File::exists($this->getUseCompression() ? $pathname . '.zip' : $pathname)) {
                File::copy(
                    config('database.connections.' . $connectionName . '.database'),
                    $pathname
                );
            }
        } else {
            throw new Exception(trans('gviabcua.smallbackup::lang.backup.flash.unknown_database_driver', ['driver' => $connectionDriver]));
        }

        if ($this->getUseCompression() && File::exists($pathname)) {
            $zipFilename = $filename . '.zip';
            $zipPathname = $this->folder . DIRECTORY_SEPARATOR . $zipFilename;

            Zip::make(
                $zipPathname,
                $pathname
            );
            File::delete($pathname);

            return $zipPathname;
        }

        return $pathname;
    }

    /**
     * Get list of excluded tables from db backup
     *
     * @return array
     */
    protected function getExcludedTables(): array
    {
        $data = Settings::get('db_excluded_tables');
        return $data
            ? (is_array($data) ? $data : explode(',', $data))
            : []
        ;
    }

    /**
     * Get using compression from settings file
     *
     * @return boolean
     */
    protected function getUseCompression(): bool
    {
        return boolval(Settings::get('db_use_compression'));
    }

    /**
     * Get custom columns mapping for db backup
     *
     * @return array
     */
    protected function getCustomMapping(): array
    {
        return array_pluck((array)Settings::get('db_custom_mapping'), 'doctrine_type', 'db_type');
    }
}