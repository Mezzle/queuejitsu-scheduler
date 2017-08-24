<?php
/**
 * @copyright (c) 2017 Stickee Technology Limited
 */

namespace QueueJitsu\Scheduler\Adapter;

use Predis\Client;
use QueueJitsu\Job\Job;

/**
 * Class RedisAdapter
 *
 * @package QueueJitsu\Scheduler\Adapter
 */
class RedisAdapter implements AdapterInterface
{
    const QUEUE_NAME = '_schdlr_';

    /**
     * @var \Predis\Client $client
     */
    private $client;

    /**
     * RedisAdapter constructor.
     *
     * @param \Predis\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * enqueueAt
     *
     * @param int $at
     * @param \QueueJitsu\Job\Job $job
     */
    public function enqueueAt(int $at, Job $job): void
    {
        $data = $job->getPayload();

        $data['s_time'] = time();

        $key = sprintf('%s:%s', self::QUEUE_NAME, $at);
        $this->client->rpush($key, [json_encode($data)]);
        $this->client->zadd(self::QUEUE_NAME, [$at => $at]);
    }

    /**
     * getNextJob
     *
     * @return null|\QueueJitsu\Job\Job
     */
    public function getNextJob(): ?Job
    {
        $at = time();

        $items = $this->client->zrangebyscore(self::QUEUE_NAME, '-inf', $at, ['limit', 0, 1]);

        if (empty($items)) {
            return null;
        }

        $next_timestamp = $items[0];

        return $this->getNextJobAtTimestamp($next_timestamp);
    }

    /**
     * getNextJobAtTimestamp
     *
     * @param $timestamp
     *
     * @return Job
     */
    private function getNextJobAtTimestamp($timestamp): Job
    {
        $key = sprintf('%s:%s', self::QUEUE_NAME, $timestamp);

        $item = json_decode($this->client->lpop($key), true);

        $this->cleanupTimestamp($timestamp);

        return new Job($item['class'], $item['queue'], $item['args'], $item['id']);
    }

    /**
     * cleanupTimestamp
     *
     * @param $timestamp
     */
    private function cleanupTimestamp($timestamp): void
    {
        $key = sprintf('%s:%s', self::QUEUE_NAME, $timestamp);

        if ($this->client->llen($key)) {
            $this->client->del([$key]);
            $this->client->zrem(self::QUEUE_NAME, $timestamp);
        }
    }
}
