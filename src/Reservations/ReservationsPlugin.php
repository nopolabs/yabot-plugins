<?php

namespace Nopolabs\Yabot\Plugins\Reservations;

use DateTime;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginTrait;
use Psr\Log\LoggerInterface;

class ReservationsPlugin implements PluginInterface
{
    use PluginTrait;

    /** @var ResourcesInterface */
    protected $resources;

    public function __construct(
        LoggerInterface $logger,
        ResourcesInterface $resources,
        array $config = [])
    {
        $this->setLog($logger);
        $this->resources = $resources;

        $help = <<<EOS
<prefix> reserve [env]
<prefix> reserve [env] until [time]
<prefix> reserve [env] forever
<prefix> release [env]
<prefix> release mine
<prefix> release all
<prefix> (what|which) envs are reserved
<prefix> (what|which) envs are mine
<prefix> (what|which) envs are free
<prefix> is [env] free
EOS;

        $this->setConfig(array_merge(
            [
                'help' => $help,
                'resourceNamePlural' => 'envs',
                'matchers' => [
                    'reserveForever' => "/^reserve (?'resource'\\w+) forever\\b/",
                    'reserveUntil' => "/^reserve (?'resource'\\w+) until (?'until'.+)/",
                    'reserve' => "/^reserve (?'resource'\\w+)/",

                    'releaseMine' => "/^release mine\\b/",
                    'releaseAll' => "/^release all\\b/",
                    'release' => "/^release (?'resource'\\w+)/",

                    'list' => '/^wh(?:at|ich) #resourceNamePlural# are reserved\\b/',
                    'listMine' => "/^wh(?:at|ich) #resourceNamePlural# are mine\\b/",
                    'listFree' => "/^wh(?:at|ich) #resourceNamePlural# are free\\b/",

                    'isFree' => "/^is (?'resource'\\w+) free\\b/",
                ],
            ],
            $config
        ));
    }

    public function reserve(Message $msg, array $matches)
    {
        $key = $matches['resource'];
        $results = $this->placeReservation($msg, $key);
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function reserveForever(Message $msg, array $matches)
    {
        $key = $matches['resource'];
        $results = $this->placeReservation($msg, $key, $this->resources->forever());
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function reserveUntil(Message $msg, array $matches)
    {
        $key = $matches['resource'];
        $until = $matches['until'];
        $until = $until === 'forever' ? $this->resources->forever() : new DateTime($until);
        $results = $this->placeReservation($msg, $key, $until);
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function release(Message $msg, array $matches)
    {
        $key = $matches['resource'];
        $results = $this->releaseReservation($msg, $key);
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function releaseMine(Message $msg, array $matches)
    {
        $me = $msg->getUsername();
        $results = [];
        foreach ($this->resources->getAll() as $key => $resource) {
            if (isset($resource['user']) && ($resource['user'] === $me)) {
                $results = array_merge($results, $this->releaseReservation($msg, $key));
            }
        }
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function releaseAll(Message $msg, array $matches)
    {
        $results = [];
        foreach ($this->resources->getKeys() as $key) {
            $results = array_merge($results, $this->releaseReservation($msg, $key));
        }
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function list(Message $msg, array $matches)
    {
        $results = $this->resources->getAllStatuses();
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function listMine(Message $msg, array $matches)
    {
        $me = $msg->getUsername();
        $results = [];
        foreach ($this->resources->getAll() as $key => $resource) {
            if (isset($resource['user']) && ($resource['user'] === $me)) {
                $results[] = $key;
            }
        }
        $msg->reply(implode(',', $results));
        $msg->setHandled(true);
    }

    public function listFree(Message $msg, array $matches)
    {
        $results = [];
        foreach ($this->resources->getAll() as $key => $resource) {
            if (empty($resource)) {
                $results[] = $key;
            }
        }
        $msg->reply(implode(',', $results));
        $msg->setHandled(true);
    }

    public function isFree(Message $msg, array $matches)
    {
        $results = [];
        $key = $matches['resource'];
        $resource = $this->resources->getResource($key);
        if ($resource === null) {
            $results[] = "$key not found.";
        } else {
            if (empty($resource)) {
                $results[] = "$key is free.";
            } else {
                $results[] = "$key is reserved by {$resource['user']}.";
            }
        }
        $msg->reply(implode(',', $results));
        $msg->setHandled(true);
    }

    protected function overrideConfig(array $params)
    {
        $config = $this->canonicalConfig(array_merge($this->getConfig(), $params));

        $matchers = $config['matchers'];
        $resourceNamePlural = $config['resourceNamePlural'];

        $matchers = $this->replaceInPatterns('#resourceNamePlural#', $resourceNamePlural, $matchers);
        $matchers = $this->replaceInPatterns(' ', "\\s+", $matchers);

        $config['matchers'] = $matchers;

        $this->setConfig($config);
    }

    protected function placeReservation(Message $msg, $key, DateTime $until = null) : array
    {
        $results = [];
        $resource = $this->resources->getResource($key);

        if ($resource === null) {
            $results[] = "$key not found.";
        } else {
            $user = $msg->getUser();
            $username = $user->getUsername();
            if (empty($resource)) {
                $this->resources->reserve($key, $user, $until);
                $results[] = "Reserved $key for $username.";
            } elseif ($resource['user'] === $username) {
                $this->resources->reserve($key, $user, $until);
                $results[] = "Updated $key for $username.";
            } else {
                $results[] = "$key is reserved by {$resource['user']}.";
            }
            $results[] = $this->resources->getStatus($key);
        }

        return $results;
    }

    protected function releaseReservation(Message $msg, $key) : array
    {
        $results = [];
        $resource = $this->resources->getResource($key);

        if ($resource === null) {
            $results[] = "$key not found.";
        } else {
            if (empty($resource)) {
                $results[] = "$key is not reserved.";
            } else {
                $this->resources->release($key);
                $results[] = "Released $key.";
            }
        }

        return $results;
    }

    protected function validMatch(Message $message, array $params, array $matches) : bool
    {
        if (isset($matches['resource'])) {
            $key = $matches['resource'];
            if (!$this->resources->isResource($key)) {
                $message->reply("'$key' is not a reservable resource");
                return false;
            }
        }
        return true;
    }
}