<?php

namespace App\Console\Commands;

use App\Support\LegacyDataImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Signature('legacy:import {--source-database=subhoskh_db_subhomoysamanta} {--source-path=G:/xampp_lite_8_5/www/subhomoysamanta} {--source-connection=} {--dry-run}')]
#[Description('Import legacy business data without overwriting existing records; unlinked receipts become wallet credit')]
class ImportLegacyData extends Command
{
    public function handle(LegacyDataImporter $importer): int
    {
        $connection = $this->option('source-connection');
        if (! $connection) {
            $configuration = config('database.connections.'.config('database.default'));
            if (! in_array($configuration['driver'], ['mysql', 'mariadb'], true)) {
                $this->error('A MySQL source or an explicit source connection is required.');

                return self::FAILURE;
            }
            $configuration['database'] = $this->option('source-database');
            $configuration['url'] = null;
            config(['database.connections.legacy_source' => $configuration]);
            DB::purge('legacy_source');
            $connection = 'legacy_source';
        }
        $source = DB::connection($connection);
        if ($source->getDatabaseName() === DB::connection()->getDatabaseName() && $source->getConfig('host') === DB::connection()->getConfig('host')) {
            $this->error('Source and destination must be different databases.');

            return self::FAILURE;
        }
        $result = Cache::lock('legacy-data-import', 1800)->block(5, fn (): array => $importer->run($source, (string) $this->option('source-path'), (bool) $this->option('dry-run')));
        $this->table(['Source table', 'Rows'], collect($result['source_counts'])->map(fn (int $count, string $table): array => [$table, $count])->values()->all());
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
