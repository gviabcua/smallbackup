<?php namespace Gviabcua\SmallBackup\Classes\Drivers\Contracts;

interface BackupStream
{
    public function backupStream(): string;
}