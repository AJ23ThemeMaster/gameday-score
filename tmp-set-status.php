<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$status = $argv[1] ?? 'in_progress';
$g = App\Models\Game::find(1);
$g->status = $status;
$g->save();
echo "status={$status}\n";
