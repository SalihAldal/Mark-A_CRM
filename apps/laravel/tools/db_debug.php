<?php

declare(strict_types=1);

/**
 * Quick DB debug helper (local/dev).
 * Prints integration_accounts and key meta fields.
 *
 * NOTE: Uses .env DB_* values via Laravel bootstrap.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('integration_accounts')
    ->select([
        'id',
        'tenant_id',
        'provider',
        'status',
        'name',
        Illuminate\Support\Facades\DB::raw("JSON_UNQUOTE(JSON_EXTRACT(config_json,'$.page_id')) as page_id"),
        Illuminate\Support\Facades\DB::raw("JSON_UNQUOTE(JSON_EXTRACT(config_json,'$.phone_number_id')) as phone_number_id"),
        Illuminate\Support\Facades\DB::raw("LENGTH(JSON_UNQUOTE(JSON_EXTRACT(config_json,'$.page_access_token'))) as ig_token_len"),
        Illuminate\Support\Facades\DB::raw("LENGTH(JSON_UNQUOTE(JSON_EXTRACT(config_json,'$.access_token'))) as wa_token_len"),
        'updated_at',
    ])
    ->orderByDesc('id')
    ->get();

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

