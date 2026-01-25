<?php

declare(strict_types=1);

/**
 * Local debug helper: send one internal realtime broadcast.
 * This should return 200 from the gateway if signature verification works.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

app(App\Services\RealtimeGateway::class)->broadcast(
    1,
    [['type' => 'tenant', 'id' => 1]],
    'test.ping',
    ['ok' => true, 'ts' => (string) now()]
);

echo "sent\n";

