<?php

namespace Nopolabs\Yabot\Plugins\Reservations;

use DateTime;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use function GuzzleHttp\Promise\settle;
use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Slack\Client;
use Nopolabs\Yabot\Helpers\ConfigTrait;
use Nopolabs\Yabot\Helpers\LoopTrait;
use Nopolabs\Yabot\Helpers\SlackTrait;
use Nopolabs\Yabot\Helpers\StorageTrait;
use Nopolabs\Yabot\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Slack\User;

/**
 * $config = [
 *     'channel' => 'general',
 *     'storageName' => 'resources',
 *     'keys' => ['dev1','dev2'],
 *     'defaultReservation' => '+12 hours',
 * ];
 */
class Resources implements ResourcesInterface
{
    use StorageTrait;
    use LoopTrait;
    use SlackTrait;
    use ConfigTrait;
    use LogTrait;

    protected $channel;
    protected $resources;

    public function __construct(
        Client $slack,
        StorageInterface $storage,
        LoopInterface $eventLoop,
        LoggerInterface $logger,
        array $config)
    {
        $this->setConfig($config);
        $this->setLog($logger);

        $this->setSlack($slack);

        $this->setStorage($storage);
        $this->setStorageKey($this->get('storageName', 'resources'));

        $this->setLoop($eventLoop);
        $this->addPeriodicTimer(60, [$this, 'expireResources']);

        $this->channel = $this->get('channel');

        $resources = $this->load() ?: [];
        $this->resources = [];

        /** @var array $keys */
        $keys = $this->get('keys', []);
        foreach ($keys as $key) {
            $resource = $resources[$key] ?? [];
            $this->resources[$key] = $resource;
        }

        $this->save($this->resources);
    }

    public function isResource($key)
    {
        return array_key_exists($key, $this->resources);
    }

    public function getResource($key)
    {
        return $this->isResource($key) ? $this->resources[$key] : null;
    }

    public function getAll() : array
    {
        return $this->resources;
    }

    public function getKeys() : array
    {
        return array_keys($this->resources);
    }

    public function isReserved($key) : bool
    {
        return !empty($this->resources[$key]);
    }

    public function isReservedBy($key, User $user) : bool
    {
        return isset($this->resources[$key]['user']) && $this->resources[$key]['user'] === $user->getUsername();
    }

    public function findFreeResource()
    {
        foreach ($this->getKeys() as $key) {
            if (!$this->isReserved($key)) {
                return $key;
            }
        }

        return null;
    }

    public function findUserResource(User $user)
    {
        foreach ($this->getKeys() as $key) {
            if ($this->isReservedBy($key, $user)) {
                return $key;
            }
        }

        return null;
    }

    public function reserve($key, User $user, DateTime $until = null)
    {
        $until = $until ?? $this->getDefaultReservationTime();

        $this->setResource($key, [
            'user' => $user->getUsername(),
            'userId' => $user->getId(),
            'until' => $until->format('Y-m-d H:i:s'),
        ]);
    }

    public function release($key)
    {
        $this->setResource($key, []);
    }

    public function getStatus($key)
    {
        return $this->getStatusAsync($key)->wait();
    }

    public function getAllStatuses() : array
    {
        return $this->getStatuses($this->getKeys());
    }

    public function getUserStatuses(User $user) : array
    {
        $userKeys = array_filter($this->getKeys(), function($key) use ($user) {
            return $this->isReservedBy($key, $user);
        });

        return $this->getStatuses($userKeys);
    }

    public function getStatuses(array $keys) : array
    {
        $requests = [];

        foreach ($keys as $key) {
            $requests[] = $this->getStatusAsync($key);
        }

        $statuses = [];
        /** @var array $results */
        $results = settle($requests)->wait();
        foreach ($results as $key => $result) {
            if ($result['state'] === PromiseInterface::FULFILLED) {
                $statuses[] = $result['value'];
            }
        }

        return $statuses;
    }

    public function forever() : DateTime
    {
        return new DateTime('3000-01-01');
    }

    protected function setResource($key, $resource)
    {
        $this->resources[$key] = $resource;
        $this->save($this->resources);
    }

    protected function getStatusAsync($key) : PromiseInterface
    {
        $status = json_encode([$key => $this->resources[$key]]);

        return new FulfilledPromise($status);
    }

    protected function expireResources()
    {
        foreach ($this->getKeys() as $key) {
            if ($this->isExpired($key)) {
                $this->release($key);
                $this->say("released $key", $this->channel);
            }
        }
    }

    protected function isExpired($key) : bool
    {
        if ($this->isReserved($key)) {
            $until = $this->getResource($key)['until'];
            $expires = new DateTime($until);

            return $expires < new DateTime();
        }

        return false;
    }

    protected function getDefaultReservationTime() : DateTime
    {
        return new DateTime($this->get('defaultReservation', '+12 hours'));
    }
}