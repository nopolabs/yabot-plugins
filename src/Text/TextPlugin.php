<?php

namespace Nopolabs\Yabot\Text;


use React\Http\Request as HttpRequest;
use React\Http\Response as HttpResponse;
use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
use Nopolabs\Yabot\Bot\SlackClient;
use Nopolabs\Yabot\Helpers\SlackTrait;
use Nopolabs\Yabot\Http\HttpServer;
use Psr\Log\LoggerInterface;
use React\Stream\BufferedSink;
use Twilio\Rest\Client as TwilioClient;

class TextPlugin implements PluginInterface
{
    use PluginTrait;
    use SlackTrait;

    /** @var TwilioClient */
    private $twilio;
    private $fromPhone;

    public function __construct(
        MessageDispatcher $dispatcher,
        LoggerInterface $logger,
        SlackClient $slack,
        HttpServer $http,
        TwilioClient $twilio,
        $fromPhone,
        array $config = [])
    {
        $this->setDispatcher($dispatcher);
        $this->setLog($logger);
        $this->setSlack($slack);
        $http->addHandler([$this, 'request']);
        $this->twilio = $twilio;
        $this->fromPhone = $fromPhone;

        $default =[
            'text' => [
                'pattern' => "/^text (?'number'\\d{3}-?\\d{3}-?\\d{4})\\b(?'message'.*)$/",
                'channel' => 'general',
                'method' => 'text',
            ],
        ];

        $matchers = array_merge($default, $config);

        $this->setMatchers($matchers);
    }

    public function text(MessageInterface $msg, array $matches)
    {
        $number = '+1'.str_replace('-', '', $matches['number']);
        $message = $matches['message'];

        $this->twilio->messages->create(
            $number, // to
            array(
                'from' => $this->fromPhone,
                'body' => $message,
            )
        );
    }

    public function request(HttpRequest $request, HttpResponse $response)
    {
        $headers = array('Content-Type' => 'text/plain');
        $response->writeHead(200, $headers);

        $sink = new BufferedSink();
        $request->pipe($sink);
        $sink->promise()->then(function ($data) use ($response) {
            if (mb_parse_str($data, $result)) {
                $from = $result['From'];
                $body = $result['Body'];
                $this->say("$from: $body", 'general');
            }
            $response->end();
        });
    }
}