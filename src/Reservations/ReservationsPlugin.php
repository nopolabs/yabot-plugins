<?php

namespace Nopolabs\Yabot\Plugins\Reservations;

use DateTime;
use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
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
reserve [env]
reserve [env] until [time]
reserve [env] forever
release [env]
release mine
release all
what envs are reserved
what envs are mine
what envs are free
is [resource] free
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

                    'list' => '/^what #resourceNamePlural# are reserved\\b/',
                    'listMine' => "/^what #resourceNamePlural# are mine\\b/",
                    'listFree' => "/^what #resourceNamePlural# are free\\b/",

                    'isFree' => "/^is (?'resource'\\w+) free\\b/",
                ],
            ],
            $config
        ));
    }

    public function reserve(MessageInterface $msg, array $matches)
    {
        $key = $matches['resource'];
        $results = $this->placeReservation($msg, $key);
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function reserveForever(MessageInterface $msg, array $matches)
    {
        $key = $matches['resource'];
        $results = $this->placeReservation($msg, $key, $this->resources->forever());
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function reserveUntil(MessageInterface $msg, array $matches)
    {
        $key = $matches['resource'];
        $until = $matches['until'];
        $until = $until === 'forever' ? $this->resources->forever() : new DateTime($until);
        $results = $this->placeReservation($msg, $key, $until);
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function release(MessageInterface $msg, array $matches)
    {
        $key = $matches['resource'];
        $results = $this->releaseReservation($msg, $key);
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function releaseMine(MessageInterface $msg, array $matches)
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

    public function releaseAll(MessageInterface $msg, array $matches)
    {
        $results = [];
        foreach ($this->resources->getKeys() as $key) {
            $results = array_merge($results, $this->releaseReservation($msg, $key));
        }
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function list(MessageInterface $msg, array $matches)
    {
        $results = $this->resources->getAllStatuses();
        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }

    public function listMine(MessageInterface $msg, array $matches)
    {
        $me = $msg->getUsername();
        $results = [];
        foreach ($this->resources->getAll() as $key => $resource) {
            if ($resource['user'] === $me) {
                $results[] = $key;
            }
        }
        $msg->reply(implode(',', $results));
        $msg->setHandled(true);
    }

    public function listFree(MessageInterface $msg, array $matches)
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

    public function isFree(MessageInterface $msg, array $matches)
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

    protected function placeReservation(MessageInterface $msg, $key, DateTime $until = null) : array
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

    protected function releaseReservation(MessageInterface $msg, $key) : array
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

    protected function validMatch(MessageInterface $message, array $params, array $matches) : bool
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