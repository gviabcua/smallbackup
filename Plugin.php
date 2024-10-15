<?php namespace Gviabcua\SmallBackup;

include_once(__DIR__ . '/helpers/helpers.php'); // for version without Composer

use System\Classes\PluginBase;
use Artisan;

/**
 * SmallBackup Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'gviabcua.smallbackup::lang.plugin.name',
            'description' => 'gviabcua.smallbackup::lang.plugin.description',
            'author' => 'Webula Gviabcua mod',
            'icon' => 'icon-database'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommand('smallbackup.db', Console\BackupDb::class);
        $this->registerConsoleCommand('smallbackup.theme', Console\BackupTheme::class);
        $this->registerConsoleCommand('smallbackup.plugins', Console\BackupPlugins::class);
        $this->registerConsoleCommand('smallbackup.storage', Console\BackupStorage::class);
    }

    /**
     * Registers schedule calls implemented in this plugin.
     *
     * @return void
     */
    public function registerSchedule($schedule)
    {
        if (Models\Settings::get('db_auto')) {
            // $schedule->command('smallbackup:db')->daily();
            // Workaround because of shared hostings disables proc_open
            $schedule->call(function () {
                Artisan::call('smallbackup:db');
            })->daily();
        }
        if (Models\Settings::get('theme_auto')) {
            // $schedule->command('smallbackup:theme')->daily();
            $schedule->call(function () {
                Artisan::call('smallbackup:theme');
            })->daily();
        }
        if (Models\Settings::get('plugins_auto')) {
            // $schedule->command('smallbackup:theme')->daily();
            $schedule->call(function () {
                Artisan::call('smallbackup:plugins');
            })->daily();
        }
        if (Models\Settings::get('storage_auto')) {
            // $schedule->command('smallbackup:storage')->daily();
            $schedule->call(function () {
                Artisan::call('smallbackup:storage');
            })->daily();
        }
    }

    /**
     * Register any back-end setting used by this plugin.
     *
     * @return array
     */
    public function registerSettings()
    {
        return [
            'settings' => [
                'label' => 'gviabcua.smallbackup::lang.plugin.name',
                'description' => 'gviabcua.smallbackup::lang.plugin.description',
                'category'    => 'Gviabcua',
                'icon' => 'icon-database',
                'class' => Models\Settings::class,
                'url' => \Backend::url('gviabcua/smallbackup/settings/update'),
                'keywords' => 'database file backup',
                'order' => 991,
                'permissions' => ['gviabcua.smallbackup.access_settings'],
            ]
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'gviabcua.smallbackup.access_settings' => [
                'label' => 'gviabcua.smallbackup::lang.permissions.access_settings',
                'tab' => 'gviabcua.smallbackup::lang.plugin.name',
            ],
        ];

    }
}
