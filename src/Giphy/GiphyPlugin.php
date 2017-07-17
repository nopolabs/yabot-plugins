<?php

namespace Nopolabs\Yabot\Plugins\Giphy;

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use function GuzzleHttp\Promise\settle;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginTrait;
use Nopolabs\Yabot\Helpers\GuzzleTrait;
use Psr\Log\LoggerInterface;

class GiphyPlugin implements PluginInterface
{
    use PluginTrait;
    use GuzzleTrait;

    private $giphyService;

    public function __construct(
        LoggerInterface $logger,
        GiphyService $giphyService,
        array $config = [])
    {
        $this->setLog($logger);
        $this->giphyService = $giphyService;
        $this->setConfig(array_merge(
            [
                'help' => '<prefix> [search terms] [optional format] (giphy formats to list available)',
                'prefix' => 'giphy',
                'matchers' => [
                    'each' => '/^\*\s+(.*)/',
                    'search' => '/^(.*)/',
                ],
                'format' => 'fixed_width',
                'formats' => [
                    'fixed_height',
                    'fixed_height_still',
                    'fixed_height_downsampled',
                    'fixed_width',
                    'fixed_width_still',
                    'fixed_width_downsampled',
                    'fixed_height_small',
                    'fixed_height_small_still',
                    'fixed_width_small',
                    'fixed_width_small_still',
                    'preview',
                    'downsized_small',
                    'downsized',
                    'downsized_medium',
                    'downsized_large',
                    'downsized_still',
                    'original',
                    'original_still',
                    'looping',
                ],
            ],
            $config
        ));
    }

    public function each(Message $msg, array $matches)
    {
        $requests = [];

        $format = $this->getDefaultFormat();

        $terms = preg_split('/[\s,]+/', $matches[1]);

        foreach ($terms as $term) {
            $requests[$term] = $this->giphyService->search($term, $format);
        }

        $results = settle($requests)->wait();
        foreach ($results as $term => $result) {
            if ($result['state'] === PromiseInterface::FULFILLED) {
                $gifUrl = $result['value'];
                $msg->reply($term.': '.$gifUrl);
            }
        }

        $msg->setHandled(true);
    }

    public function search(Message $msg, array $matches)
    {
        if (trim($matches[1]) === 'formats') {
            $formats = $this->getFormats();
            $msg->reply('Available formats: ' . implode(' ', $formats));
            $msg->setHandled(true);
            return;
        }

        $terms = preg_split('/\s+/', $matches[1]);
        $query = $this->getQuery($terms);
        $format = $this->getFormat($terms);

        $this->giphyService->search($query, $format)->then(
            function (string $gifUrl) use ($msg) {
                $msg->reply($gifUrl);
            },
            function (Exception $e) {
                $this->getLog()->warning($e->getMessage());
            }
        );

        $msg->setHandled(true);
    }

    protected function getQuery(array $terms) : string
    {
        $search = [];
        $formats = $this->getFormats();
        foreach ($terms as $term) {
            if (!in_array($term, $formats)) {
                $search[] = $term;
            }
        }
        $query = implode(' ', $search);

        return $query;
    }

    protected function getFormat(array $terms) : string
    {
        $format = $this->getDefaultFormat();
        $formats = $this->getFormats();
        foreach ($terms as $term) {
            if (in_array($term, $formats)) {
                $format = $term;
            }
        }

        return $format;
    }

    protected function getDefaultFormat()
    {
        return $this->get('format', 'fixed_width');
    }

    protected function getFormats() : array
    {
        $default = $this->getDefaultFormat();

        return $this->get('formats', [$default]);;
    }
}