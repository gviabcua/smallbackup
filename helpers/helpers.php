<?php
if (!function_exists('gsb_backup_db')) {
    function gsb_backup_db(bool $once = false, string $connectionName = null, bool $noCleanup = false): bool {
        try {
            $manager = new \Gviabcua\SmallBackup\Classes\DbBackupManager;
            if (!$noCleanup) {
                $manager->clear();
            }
            $manager->backup($connectionName, $once);
        } catch (\Exception $ex) {
            \Log::error($ex->getMessage());
            return false;
        }
        return true;
    }
}

if (!function_exists('gsb_backup_theme')) {
    function gsb_backup_theme(bool $once = false, string $themeName = null, bool $noCleanup = false): bool {
        try {
            $manager = new \Gviabcua\SmallBackup\Classes\ThemeBackupManager;
            if (!$noCleanup) {
                $manager->clear();
            }
            $manager->backup($themeName, $once);
        } catch (\Exception $ex) {
            \Log::error($ex->getMessage());
            return false;
        }
        return true;
    }
}
if (!function_exists('gsb_backup_plugins')) {
    function gsb_backup_plugins(bool $once = false, string $themeName = null, bool $noCleanup = false): bool {
        try {
            $manager = new \Gviabcua\SmallBackup\Classes\PluginsBackupManager;
            if (!$noCleanup) {
                $manager->clear();
            }
            $manager->backup($themeName, $once);
        } catch (\Exception $ex) {
            \Log::error($ex->getMessage());
            return false;
        }
        return true;
    }
}
if (!function_exists('gsb_backup_storage')) {
    function gsb_backup_storage(bool $once = false, string $cmsStorage = null, bool $noCleanup = false): bool {
        try {
            $manager = new \Gviabcua\SmallBackup\Classes\StorageBackupManager;
            if (!$noCleanup) {
                $manager->clear();
            }
            $manager->backup($cmsStorage, $once);
        } catch (\Exception $ex) {
            \Log::error($ex->getMessage());
            return false;
        }
        return true;
    }
}