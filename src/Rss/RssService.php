<?php

namespace Nopolabs\Yabot\Plugins\Rss;

use GuzzleHttp\Promise\PromiseInterface;
use Nopolabs\Yabot\Guzzle\Guzzle;
use Nopolabs\Yabot\Helpers\ConfigTrait;
use Nopolabs\Yabot\Helpers\GuzzleTrait;
use Nopolabs\Yabot\Helpers\LogTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

class RssService
{
    use LogTrait;
    use GuzzleTrait;
    use ConfigTrait;

    public function __construct(
        LoggerInterface $logger,
        Guzzle $guzzle,
        array $config = [])
    {
        $this->setLog($logger);
        $this->setGuzzle($guzzle);
        $this->setConfig(array_merge(
            [
                'SimpleXMLElementOptions' => LIBXML_NOCDATA,
                'JsonEncodeOptions' => JSON_PRETTY_PRINT,
            ],
            $config
        ));
    }

    public function fetchRaw(string $url) : PromiseInterface
    {
        return $this->getAsync($url)->then(
            function (ResponseInterface $response) {
                return $response->getBody();
            }
        );
    }

    public function fetchXml(string $url) : PromiseInterface
    {
        return $this->getAsync($url)->then(
            function (ResponseInterface $response) {
                $data = $response->getBody();
                $options = $this->get('SimpleXMLElementOptions', 0);
                return new SimpleXMLElement($data, $options);
            }
        );
    }

    public function fetchJson(string $url) : PromiseInterface
    {
        return $this->getAsync($url)->then(
            function (ResponseInterface $response) {
                $data = $response->getBody();
                $xmlOptions = $this->get('SimpleXMLElementOptions', 0);
                $xml = new SimpleXMLElement($data, $xmlOptions);
                $jsonOptions = $this->get('JsonEncodeOptions', 0);
                return json_encode($xml, $jsonOptions);
            }
        );
    }

    public function fetch(string $url) : PromiseInterface
    {
        return $this->getAsync($url)->then(
            function (ResponseInterface $response) {
                $data = $response->getBody();
                $xmlOptions = $this->get('SimpleXMLElementOptions', 0);
                $xml = new SimpleXMLElement($data, $xmlOptions);
                $jsonOptions = $this->get('JsonEncodeOptions', 0);
                $json = json_encode($xml, $jsonOptions & ~JSON_PRETTY_PRINT);
                return json_decode($json);
            }
        );
    }
}