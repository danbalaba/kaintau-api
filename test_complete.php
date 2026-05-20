<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $order = App\Models\Order::findOrFail(1);
    echo "Current Status: " . $order->status . "\n";
    echo "Current Payment Status: " . $order->payment_status . "\n";
    
    $order->status = 'completed';
    $order->payment_status = 'paid';
    $order->save();
    
    echo "SUCCESS: Status updated successfully!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
}
