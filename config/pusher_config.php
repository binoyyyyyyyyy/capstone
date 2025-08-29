<?php
// Pusher Configuration
require_once __DIR__ . '/../vendor/autoload.php';

$pusher_config = array(
    'app_id' => '2018345',
    'app_key' => 'ed1a40e7a469cee7f86c',
    'app_secret' => '8477713c46f4e9aa6f24',
    'cluster' => 'ap1',
    'useTLS' => true
);

// Initialize Pusher
$pusher = new Pusher\Pusher(
    $pusher_config['app_key'],
    $pusher_config['app_secret'],
    $pusher_config['app_id'],
    array(
        'cluster' => $pusher_config['cluster'],
        'useTLS' => $pusher_config['useTLS']
    )
);

// Function to send notification
function sendPusherNotification($channel, $event, $data) {
    global $pusher;
    try {
        $pusher->trigger($channel, $event, $data);
        return true;
    } catch (Exception $e) {
        error_log("Pusher Error: " . $e->getMessage());
        return false;
    }
}
?> 