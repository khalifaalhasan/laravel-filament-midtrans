<?php

namespace App\Console\Commands;

use Database\Seeders\SnapshotSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Dump the local database (domain tables) and uploaded files into database/seeders/snapshot,
 * so `php artisan db:seed --class=SnapshotSeeder` can rebuild the same data on a server.
 */
class ExportSnapshot extends Command
{
    protected $signature = 'app:export-snapshot {--keep-personal : Keep real accounts and buyer data as-is (only for a private repo)}';

    protected $description = 'Export local data + uploaded files to database/seeders/snapshot';

    public function handle(): int
    {
        $dir = SnapshotSeeder::path();
        File::deleteDirectory($dir);
        File::ensureDirectoryExists($dir.'/files');

        $data = [];
        foreach (SnapshotSeeder::TABLES as $table => $key) {
            $data[$table] = DB::table($table)->orderBy(((array) $key)[0])->get()->map(fn ($row) => (array) $row)->all();
        }

        // Roles by name: role ids may differ on another database.
        $data['model_has_roles'] = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->get(['model_has_roles.model_type', 'model_has_roles.model_id', 'roles.name as role'])
            ->map(fn ($row) => (array) $row)->all();

        $anonymized = $this->option('keep-personal') ? 0 : $this->anonymize($data);

        foreach ($data as $table => $rows) {
            File::put("{$dir}/{$table}.json", json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $disk = Storage::disk('public');
        $files = collect($disk->allFiles())->reject(fn ($f) => str_starts_with(basename($f), '.'));
        foreach ($files as $file) {
            File::ensureDirectoryExists(dirname("{$dir}/files/{$file}"));
            File::put("{$dir}/files/{$file}", $disk->get($file));
        }

        $this->table(['Tabel', 'Baris'], collect($data)->map(fn ($rows, $table) => [$table, count($rows)])->values()->all());
        $this->info("{$files->count()} file disalin. ".($anonymized ? "{$anonymized} akun non-demo dianonimkan." : 'Data pribadi TIDAK dianonimkan (--keep-personal).'));
        $this->line("Snapshot: {$dir}");

        return self::SUCCESS;
    }

    /** Real accounts (anything outside example.com/org/net) get placeholder identities; their orders and tickets lose personal data. */
    private function anonymize(array &$data): int
    {
        $isDemo = fn (string $email) => (bool) preg_match('/@example\.(com|org|net)$/', $email);
        $password = Hash::make('password');
        $real = [];

        foreach ($data['users'] as &$user) {
            if ($isDemo($user['email'])) {
                continue;
            }
            $real[$user['id']] = "user{$user['id']}@example.test";
            $user = ['name' => "Pengguna {$user['id']}", 'email' => $real[$user['id']], 'phone' => null, 'password' => $password, 'remember_token' => null] + $user;
        }
        unset($user);

        $realOrders = [];
        foreach ($data['orders'] as &$order) {
            if (! isset($real[$order['user_id']])) {
                continue;
            }
            $realOrders[$order['id']] = $email = $real[$order['user_id']];
            $attendees = json_decode((string) $order['attendees'], true) ?: [];
            $order = [
                'buyer_name' => "Pengguna {$order['user_id']}", 'buyer_email' => $email, 'buyer_phone' => null,
                'buyer_id_type' => null, 'buyer_id_number' => null,
                'attendees' => $attendees ? json_encode(array_map(fn ($a, $i) => ['name' => 'Pemegang '.($i + 1), 'email' => $email, 'gender' => $a['gender'] ?? null], $attendees, array_keys($attendees))) : null,
            ] + $order;
        }
        unset($order);

        foreach ($data['tickets'] as &$ticket) {
            if (isset($realOrders[$ticket['order_id']])) {
                $ticket = ['holder_name' => 'Pemegang tiket', 'holder_email' => $realOrders[$ticket['order_id']], 'holder_phone' => null, 'holder_id_type' => null, 'holder_id_number' => null] + $ticket;
            }
        }
        unset($ticket);

        // Gateway payloads carry buyer details; drop them for anonymized orders.
        foreach ($data['payment_notifications'] as &$notification) {
            if (isset($realOrders[$notification['order_id']])) {
                $notification['payload'] = '{}';
            }
        }

        return count($real);
    }
}
