<?php namespace Gviabcua\SmallBackup\Classes;

use File;
use Exception;
use Cms\Classes\Theme;
use Winter\Storm\Filesystem\Zip;

class ThemeBackupManager extends BackupManager
{
    /**
     * Backup file prefix
     *
     * @var string
     */
    protected $prefix = 'gsb-theme-';

    /**
     * Backup Theme(s) by connection name (null = default)
     *
     * @param string|null $resource
     * @param bool $once do not overwrite existing backup file
     * @return string backup file
     */
    public function backup(string $resource = null, bool $once = false): string
    {
        $themeName = $resource ?: Theme::getActiveThemeCode();

        if ($themeName && File::isDirectory(themes_path($themeName))) {
            $filename = $this->prefix . str_slug($themeName) . '-' . now()->format('Y-m-d') . '.zip';
            $pathname = $this->folder . DIRECTORY_SEPARATOR . $filename;

            if (!$once || !File::exists($pathname)) {
                Zip::make(
                    $pathname,
                    themes_path($themeName)
                );
            }

            return $pathname;
        } else {
            throw new Exception(trans('gviabcua.smallbackup::lang.backup.flash.unknown_theme', ['theme' => $themeName]));
        }
    }
}