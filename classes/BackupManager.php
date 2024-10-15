<?php namespace Gviabcua\SmallBackup\Classes;

use File, Str;
use Carbon\Carbon;
use Gviabcua\SmallBackup\Models\Settings;

abstract class BackupManager
{

    /**
     * Backup folder
     *
     * @var string
     */
    protected $folder;

    /**
     * Backup file prefix
     *
     * @var string
     */
    protected $prefix;


    public function __construct(string $folder = null, string $prefix = null)
    {
        $this->folder = PathHelper::normalizePath(
            base_path($folder ?: $this->getBackupFolder())
        );

        if (!File::isDirectory($this->folder))  {
            File::makeDirectory($this->folder, config('cms.defaultMask.folder') ?? 0775, true);
        }

        if ($prefix) {
            $this->prefix = $prefix;
        }
    }

    /**
     * Backup source and return his filename
     *
     * @param string|null $resource
     * @param bool $once do not overwrite existing backup file
     * @return string file with current backup
     */
    abstract public function backup(string $resource = null, bool $once = false): string;

    /**
     * Clear expired backups
     *
     * @return integer number of deleted files
     */
    public function clear(): int
    {
        $counter = 0;
        foreach (File::files($this->folder) as $file) {
            $outdated = Str::startsWith($file->getFilename(), $this->prefix)
                && Carbon::createFromTimestamp($file->getCTime(), config('app.timezone'))
                    ->lt(now()->subDays($this->getCleanupInterval())
                );

            if ($outdated) {
                File::delete($file->getPathname());
                $counter++;
            }
        }

        return $counter;
    }

    /**
     * List all backup files backuped by this class
     *
     * @return array|\SplFileInfo[]
     */
    public function list(): array
    {
        return collect(File::files($this->folder))
            ->filter(function ($file) {
                return Str::startsWith($file->getFilename(), $this->prefix);
            })
            ->sortBy(function ($file) {
                return $file->getCTime();
            })
            ->toArray();
    }

    /**
     * Get backup folder from settings file
     *
     * @return string
     */
    protected function getBackupFolder(): string
    {
        return strval(Settings::get('backup_folder', 'storage/app/backup'));
    }

    /**
     * Get backup cleanup interval from settings file
     *
     * @return integer
     */
    protected function getCleanupInterval(): int
    {
        return intval(Settings::get('cleanup_interval', 7));
    }
}