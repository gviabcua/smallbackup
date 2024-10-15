<?php namespace Gviabcua\SmallBackup\Classes;

use File;
use Exception;
use Log;
use Str;
use Winter\Storm\Filesystem\Zip;
use Phar, PharData;
use Gviabcua\SmallBackup\Models\Settings;

class StorageBackupManager extends BackupManager
{
    /**
     * Backup file prefix
     *
     * @var string
     */
    protected $prefix = 'gsb-storage-';

    /**
     * Backup Storage(s) by resource name (null = all)
     *
     * @param string|null $resource resource
     * @param bool $once do not overwrite existing backup file
     * @return string file with current backup
     * @throws Exception
     */
    public function backup(string $resource = null, bool $once = false): string
    {
        if ($resource) {
            $path = array_get($this->getResources(), $resource);
            if (!$path) {
                throw new Exception(trans('gviabcua.smallbackup::lang.backup.flash.unknown_resource', ['resource' => $resource]));
            }
            $folders[] = $path;
        } else {
            $folders = array_diff($this->getResources(), $this->getExcludedResources());
        }

        $folders = collect($folders)->map(function ($folder) {
            return PathHelper::normalizePath(base_path($folder));
        })->filter(function ($folder) {
            return File::isDirectory($folder);
        })->all();

        if (empty($folders)) {
            throw new Exception(trans('gviabcua.smallbackup::lang.backup.flash.empty_resource'));
        }

        $excludedFolders = array_merge(
            array_map(function ($folder) {
                return PathHelper::normalizePath(base_path($folder));
            }, $this->getExcludedResources()),
            [$this->folder]
        );
        $files = [];

        foreach ($folders as $_folder) {
            $files = array_merge($files,
                collect(File::allFiles($_folder))
                    ->filter(function ($file) use ($excludedFolders) {
                        return !Str::startsWith($file->getPathname(), $excludedFolders);
                    })
                    ->map(function ($file) {
                        return $file->getPathname();
                    })
                    ->all()
            );
        }

        if (empty($files)) {
            throw new Exception(trans('gviabcua.smallbackup::lang.backup.flash.empty_files'));
        }

        $name = $this->getOutputFileName($resource);
        $pathname = $this->getOutputPathName($name);

        if (!$once || !File::exists($pathname)) {
            switch ($this->getOutput()) {
                case 'tar_unsafe': return $this->saveAsTar($name, $pathname, $files, null, true);
                case 'tar': return $this->saveAsTar($name, $pathname, $files);
                case 'tar_gz': case 'tar_bz2': return $this->saveAsTar($name, $pathname, $files, str_after($this->getOutput(), '_'));
                case 'zip': return $this->saveAsZip($name, $pathname, $files);
                default: throw new Exception(trans('gviabcua.smallbackup::lang.backup.flash.unknown_output'));
            }
        }

        return $pathname;
    }

    /**
     * Get output file name
     *
     * @param string|null $resource
     * @return string
     */
    protected function getOutputFileName(string $resource = null): string
    {
        return $this->prefix . str_slug($resource ?: 'all') . '-' .  now()->format('Y-m-d');
    }

    /**
     * Get output pathname
     *
     * @param string $name
     * @return string
     */
    protected function getOutputPathName(string $name): string
    {
        $pathname = $this->folder . DIRECTORY_SEPARATOR . $name;

        switch ($this->getOutput()) {
            case 'tar_unsafe': case 'tar': case 'tar_gz': case 'tar_bz2': $pathname .= '.tar'; break;
            case 'zip': $pathname .= '.zip'; break;
        }

        return $pathname;
    }

    protected function getTempPathName(string $name, string $extension): string
    {
        return temp_path($name . '.backup.' . $extension);
    }

    /**
     * Save folderlist as TAR archive
     *
     * @param string $name filename
     * @param string $pathname path name
     * @param array $folders list of files
     * @param string|null $compression compression type
     * @param bool $unsafe do not check TAR names and truncate them
     * @return string file with current backup
     */
    protected function saveAsTar(string $name, string $pathname, array $files, ?string $compression = null, bool $unsafe = false): string
    {
        File::delete([$pathname, $pathname . '.gz', $pathname . '.bz2']);
        $temp_pathname = $this->getTempPathName($name, 'tar');

        $truncated = [];

        try {
            $archive = new PharData($temp_pathname);
            foreach ($files as $file) {
                $relative_name = str_after($file, PathHelper::normalizePath(base_path()));
                if (!$unsafe) {
                    $local_name = PathHelper::tarTruncatePath($relative_name);
                    if ($local_name != $relative_name) {
                        $truncated[$relative_name] = $local_name;
                    }
                    $archive->addFile($file, $local_name);
                } else {
                    $local_name = $relative_name;
                }
                $archive->addFile($file, $local_name);
            }

            if ($compression && $archive->canCompress($compression == 'gz' ? Phar::GZ : Phar::BZ2)) {
                $archive->compress($compression == 'gz' ? Phar::GZ : Phar::BZ2);
                $pathname .= '.' . $compression;
            }
            File::move($temp_pathname . ($compression ? '.' . $compression : ''), $pathname);
        } catch (Exception $ex) {
            throw new Exception(trans('gviabcua.smallbackup::lang.backup.flash.failed_backup', ['error' => $ex->getMessage()]));
        } finally {
            File::delete([$temp_pathname, $temp_pathname . '.gz', $temp_pathname . '.bz2']);
        }

        if (!$unsafe && !empty($truncated)) {
            Log::warning(trans('gviabcua.smallbackup::lang.backup.flash.truncated_filenames', [
                'filenames' => collect($truncated)->map(function ($local_name, $relative_name) {
                    return $relative_name . ' -> ' . $local_name;
                })->implode(', ')
            ]));
        }

        return $pathname;
    }

    /**
     * Save folderlist as ZIP archive
     *
     * @param string $name filename
     * @param string $pathname path name
     * @param array $files list of files
     * @return string file with current backup
     */
    protected function saveAsZip(string $name, string $pathname, array $files): string
    {
        $temp_pathname = $this->getTempPathName($name, 'zip');

        $files = array_map(function ($folder) {
            return PathHelper::linuxPath($folder); // FIX Winter Zip in Windows
        }, $files);

        try {
            Zip::make(
                $temp_pathname,
                function ($zip) use ($files, $name) {
                    $zip->folder($name, function ($zip) use ($files) {
                        foreach ($files as $file) {
                            $zip->add($file, ['basedir' => PathHelper::linuxPath(base_path())]); // FIX Winter Zip in Windows
                        }
                    });
                }
            );
            File::move($temp_pathname, $pathname);
        } catch (Exception $ex) {
            throw new Exception(trans('gviabcua.smallbackup::lang.backup.flash.failed_backup', ['error' => $ex->getMessage()]));
        } finally {
            File::delete($temp_pathname);
        }

        return $pathname;
    }

    /**
     * Get list of available resources
     *
     * @return array
     */
    protected function getResources(): array
    {
        return (array)Settings::instance()->getResourcesOptions();
    }

    /**
     * Get output type of storage backup
     *
     * @return string
     */
    protected function getOutput(): string
    {
        return (string)Settings::get('storage_output', 'tar');
    }

    /**
     * Get list of excluded folders from storage backup
     *
     * @return array
     */
    protected function getExcludedResources(): array
    {
        $data = Settings::get('storage_excluded_resources');
        return $data
            ? (is_array($data) ? $data : explode(',', $data))
            : []
        ;

    }
}