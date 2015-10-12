<?php

require_once("phapper/phapper.php");
$r = new \Phapper\Phapper();
$r->setDebug(true);

$modmail = $r->getModmail("rotorcowboy", 5, true);
//var_dump($modmail->data->children);

$new_messages = array();
foreach ($modmail->data->children as $message) {
    if ($message->data->new) {
        echo "New message from {$message->data->author}: {$message->data->subject}\n";
        $new_messages[] = $message->data->name;

        $time_sent = new DateTime();
        $time_sent->setTimestamp($message->data->created_utc);
        $r->submitTextPost("rotorcowboy", "New message from ".$message->data->author, "NEW MESSAGE!\n\nSent: **".$time_sent->format('Y-m-d H:i:s')."**\n\n---\n\n".$message->data->body, false);
        $r->comment($message->data->name, "Thanks, bro.");
    }
    else {
        echo "Old message from {$message->data->author}: {$message->data->subject}\n";
    }
}

echo "Marking ".(int)count($new_messages)." message(s) as read.\n";
$markread = $r->markMessageRead($new_messages);
var_dump($markread);