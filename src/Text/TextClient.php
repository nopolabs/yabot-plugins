<?php

namespace Nopolabs\Yabot\Text;


use Twilio\Rest\Client;

class TextClient
{
    private $client;

    public function __construct(array $config)
    {
        $username = $config['twilio']['sid'];
        $password = $config['twilio']['token'];
        $this->client = new Client($username, $password);
    }

    public function send($to, $from, $message)
    {
        $this->client->messages->create($to, ['from' => $from, 'body' => $message]);
    }
}