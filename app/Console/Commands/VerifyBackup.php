<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\BackupDestination;
use ZipArchive;

/**
 * OPS-03: a backup only counts once it has been restored. This restores the newest backup into an
 * empty database (BACKUP_VERIFY_DATABASE), compares every table and the media files with the live
 * site, then removes what it restored.
 */
#[Signature('ctp:backup-verify {--disk= : Backup disk to read (default: the first in BACKUP_DISKS)} {--keep : Leave the restored tables in place}')]
#[Description('Restore the newest backup into a scratch database and compare it with the live site')]
class VerifyBackup extends Command
{
    public function handle(): int
    {
        $database = (string) config('ctp.backup.verify_database');
        $live = (string) config('database.connections.mysql.database');
        if ($database === '' || $database === $live) {
            $this->components->error('Set BACKUP_VERIFY_DATABASE to an empty database that is not the live one.');

            return self::FAILURE;
        }

        $disk = $this->option('disk') ?: (config('backup.backup.destination.disks')[0] ?? 'backups');
        $backup = BackupDestination::create($disk, config('backup.backup.name'))->newestBackup();
        if (! $backup) {
            $this->components->error("No backup found on the \"{$disk}\" disk. Run php artisan backup:run first.");

            return self::FAILURE;
        }
        $this->components->info("Restoring {$backup->path()} ({$backup->date()->diffForHumans()}, ".round($backup->sizeInBytes() / 1024).' KB)');

        $work = storage_path('app/backup-verify/'.now()->format('YmdHis'));
        File::ensureDirectoryExists($work);

        try {
            $zipPath = $work.'/backup.zip';
            file_put_contents($zipPath, $backup->stream());
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new \RuntimeException('The backup zip cannot be opened.');
            }
            if ($password = config('backup.backup.password')) {
                $zip->setPassword($password);
            }
            $zip->extractTo($work.'/extract');
            $zip->close();

            $dumps = File::glob($work.'/extract/db-dumps/*.sql*');
            if (! $dumps) {
                throw new \RuntimeException('The backup has no database dump.');
            }
            $sql = str_ends_with($dumps[0], '.gz') ? (string) gzdecode((string) file_get_contents($dumps[0])) : (string) file_get_contents($dumps[0]);

            config(['database.connections.restore' => ['database' => $database] + config('database.connections.mysql')]);
            DB::purge('restore');
            $restore = DB::connection('restore');
            $this->dropTables($restore);
            $restore->unprepared($sql);

            $rows = [];
            $missing = 0;
            // Name the schema: without it the listing covers every database this user can see.
            foreach (Schema::getTableListing($live, schemaQualified: false) as $table) {
                $liveCount = DB::table($table)->count();
                $restored = Schema::connection('restore')->hasTable($table) ? $restore->table($table)->count() : null;
                $missing += $restored === null ? 1 : 0;
                $rows[] = [$table, $liveCount, $restored ?? 'MISSING', match (true) {
                    $restored === null => '✗ missing',
                    $restored === $liveCount => '✓',
                    default => '≈ changed since the backup',
                }];
            }
            $this->table(['Table', 'Live rows', 'Restored rows', ''], $rows);

            $media = collect(File::allFiles($work.'/extract'))->filter(fn ($f) => str_contains($f->getPathname(), 'storage/app/public/'))->count();
            $onDisk = collect(Storage::disk('public')->allFiles())->reject(fn ($p) => str_starts_with($p, 'og/') || str_starts_with(basename($p), '.'))->count();
            $this->components->twoColumnDetail('Media files in the backup / on disk', "{$media} / {$onDisk}");

            if (! $this->option('keep')) {
                $this->dropTables($restore);
            }

            if ($missing > 0) {
                $this->components->error("{$missing} table(s) are missing from the backup.");

                return self::FAILURE;
            }
            $this->components->info('Restore verified: every table came back.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->components->error('Restore failed: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            File::deleteDirectory($work);
        }
    }

    private function dropTables(Connection $connection): void
    {
        $connection->statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (Schema::connection($connection->getName())->getTableListing($connection->getDatabaseName(), schemaQualified: false) as $table) {
            Schema::connection($connection->getName())->drop($table);
        }
        $connection->statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
