<?php

namespace Nopolabs\Yabot\Plugins\Giphy;

use Exception;
use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginTrait;
use Nopolabs\Yabot\Helpers\GuzzleTrait;
use Psr\Http\Message\ResponseInterface;
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
                    'search' => '/^(.*)/',
                ],
            ],
            $config
        ));
    }

    public function search(Message $msg, array $matches)
    {
        if (trim($matches[1]) === 'formats') {
            $formats = $this->get('formats', ['fixed_width']);
            $msg->reply('Available formats: ' . implode(' ', $formats));
            $msg->setHandled(true);
            return;
        }

        list($query, $format) = $this->getQueryAndFormat($matches);

        $this->giphyService->search($query)->then(
            function (string $gifUrl) use ($msg, $format) {
                $msg->reply($gifUrl);
            },
            function (Exception $e) {
                $this->getLog()->warning($e->getMessage());
            }
        );

        $msg->setHandled(true);
    }

    protected function getQueryAndFormat(array $matches) : array
    {
        $terms = preg_split('/\s+/', $matches[1]);
        $search = [];
        $format = $this->get('format', 'fixed_width');
        $formats = $this->get('formats', [$format]);
        foreach ($terms as $term) {
            if (in_array($term, $formats)) {
                $format = $term;
            } else {
                $search[] = $term;
            }
        }
        $query = implode(' ', $search);

        return [$query, $format];
    }
}