<?php

namespace JumaMiller\MadiLib\core\Migration;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class Migration
{
    /**
     * @param $columnName
     * @param $columnType
     * @return bool|int
     */
    public static function generate_tenants_migration_file($stub_path,$columnName, $columnType): bool|int|string
    {
        // Assuming $columnName and $columnType are retrieved from user input /stubs folder
        $migrationTemplate = File::get(base_path($stub_path));
        //create folder if not exists with tenant_id in /database/migrations/tenant/dynamic
        $path = database_path('migrations/tenant/dynamic/'.tenant('id'));
        if (!File::exists($path)) {
            File::makeDirectory($path, 0777, true, true);
        }
        //
        $migrationFileName = date('Y_m_d_His').'_add_' . $columnName . '_to_contacts_table.php';
        $migrationFilePath = database_path('migrations/tenant/dynamic/'.tenant('id').'/' . $migrationFileName);
        //replace the placeholders
        $migrationContent = str_replace(
            ['{{ColumnName}}', '{{ColumnType}}'],
            [$columnName, $columnType],
            $migrationTemplate
        );
        $file= File::put($migrationFilePath, $migrationContent);
        if (!$file) {
            return false;
        }
        return $migrationFileName;
    }
    /**
     * @return void
     */
    public static function run_migration($job): void
    {
        Log::info('Start Running migrations');
        //run job
        $job->run();
        Log::info('Ended Running migrations');
    }
}
