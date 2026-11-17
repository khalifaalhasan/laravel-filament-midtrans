<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Rebuild data exported by `php artisan app:export-snapshot`. Meant for a fresh database (e.g. a new server);
 * re-running is safe because rows are upserted by primary key.
 *
 *   php artisan db:seed --class=SnapshotSeeder --force
 */
class SnapshotSeeder extends Seeder
{
    /** Tables in foreign-key order => their unique key for upserts. */
    public const TABLES = [
        'users' => 'id',
        'events' => 'id',
        'event_user' => ['event_id', 'user_id'],
        'ticket_types' => 'id',
        'orders' => 'id',
        'order_items' => 'id',
        'tickets' => 'id',
        'payment_notifications' => 'id',
        'activity_log' => 'id',
    ];

    /** Overridable so tests never touch the committed snapshot. */
    public static ?string $path = null;

    public static function path(): string
    {
        return self::$path ?? database_path('seeders/snapshot');
    }

    public function run(): void
    {
        $dir = self::path();
        if (! File::exists("{$dir}/users.json")) {
            throw new RuntimeException("Snapshot tidak ditemukan di {$dir}. Jalankan `php artisan app:export-snapshot` di lokal lalu commit hasilnya.");
        }

        DB::transaction(function () use ($dir) {
            foreach (self::TABLES as $table => $key) {
                $rows = json_decode(File::get("{$dir}/{$table}.json"), true);
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table($table)->upsert($chunk, (array) $key, array_values(array_diff(array_keys($chunk[0]), (array) $key)));
                }
                $this->command?->info(str_pad($table, 22).count($rows));
            }

            foreach (json_decode(File::get("{$dir}/model_has_roles.json"), true) as $row) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => Role::findOrCreate($row['role'])->id,
                    'model_type' => $row['model_type'],
                    'model_id' => $row['model_id'],
                ]);
            }
        });

        app()['cache']->forget(config('permission.cache.key'));

        $files = File::exists("{$dir}/files") ? File::allFiles("{$dir}/files") : [];
        foreach ($files as $file) {
            Storage::disk('public')->put($file->getRelativePathname(), $file->getContents());
        }
        $this->command?->info(count($files).' file disalin ke storage/app/public');
    }
}
