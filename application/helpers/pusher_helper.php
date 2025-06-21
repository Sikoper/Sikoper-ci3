<?php
use Pusher\Pusher;

function get_pusher_instance()
{
    require_once FCPATH . 'vendor/autoload.php';

    $options = [
        'cluster' => $_ENV['PUSHER_CLUSTER'],
        'useTLS'  => true
    ];

    return new Pusher(
        $_ENV['PUSHER_APP_KEY'],
        $_ENV['PUSHER_APP_SECRET'],
        $_ENV['PUSHER_APP_ID'],
        $options
    );
}

function push_event($channel, $event, $data)
{
    $pusher = get_pusher_instance();
    $pusher->trigger($channel, $event, $data);
}
