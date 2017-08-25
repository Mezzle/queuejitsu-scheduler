<?php
/**
 * @copyright (c) 2017 Stickee Technology Limited
 */

namespace QueueJitsu\Scheduler\Adapter;

use Cron\CronExpression;
use Predis\Client;
use QueueJitsu\Job\Job;
use Ramsey\Uuid\Uuid;

/**
 * Class RedisAdapter
 *
 * @package QueueJitsu\Scheduler\Adapter
 */
class RedisAdapter implements AdapterInterface
{
    const AT_QUEUE_NAME = '_schdlr_at_';
    const CRON_QUEUE_NAME = '_schdlr_cron_';

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

        $key = sprintf('%s:%s', self::AT_QUEUE_NAME, $at);
        $this->client->rpush($key, [json_encode($data)]);
        $this->client->zadd(self::AT_QUEUE_NAME, [$at => $at]);
    }

    /**
     * enqueueCron
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param string $cron
     * @param \QueueJitsu\Job\Job $job
     *
     * @throws \RuntimeException
     */
    public function enqueueCron(string $cron, Job $job)
    {
        $data = ['cron' => $cron, 'job' => $job->getPayload()];

        $id = Uuid::uuid4()->toString();

        $key = sprintf('%s:%s', self::CRON_QUEUE_NAME, $id);

        $this->client->set($key, json_encode($data));

        $this->updateCron($id, $cron);
    }

    /**
     * getNextJob
     *
     * @return null|\QueueJitsu\Job\Job
     *
     * @throws \RuntimeException
     */
    public function getNextJob(): ?Job
    {
        if ($this->hasAtJobsToProcess() && $this->hasCronJobsToProcess()) {
            return $this->findNextJob();
        }

        if ($this->hasAtJobsToProcess()) {
            return $this->getNextAtJob();
        }

        return $this->getNextCronJob();
    }

    /**
     * hasAtJobsToProcess
     *
     * @return bool
     */
    private function hasAtJobsToProcess(): bool
    {
        return !is_null($this->getNextAtTimestamp());
    }

    /**
     * getNextAtTimestamp
     *
     * @return int|null
     */
    protected function getNextAtTimestamp(): ?int
    {
        $at = time();

        $items = $this->client->zrangebyscore(self::AT_QUEUE_NAME, '-inf', $at, ['limit', 0, 1]);

        if (empty($items)) {
            return null;
        }

        return (int)$items[0];
    }

    /**
     * hasCronJobsToProcess
     *
     * @return bool
     */
    private function hasCronJobsToProcess()
    {
        return !is_null($this->getNextCronId());
    }

    /**
     * getNextCronId
     *
     * @return null
     */
    private function getNextCronId()
    {
        $at = time();

        $items = $this->client->zrangebyscore(self::CRON_QUEUE_NAME, '-inf', $at, ['limit', 0, 1]);

        if (empty($items)) {
            return null;
        }

        return $items[0];
    }

    /**
     * findNextJob
     *
     * @return null|\QueueJitsu\Job\Job
     *
     * @throws \RuntimeException
     */
    protected function findNextJob()
    {
        $next_at_timestamp = $this->getNextAtTimestamp();
        $cron_id = $this->getNextCronId();

        if (is_null($cron_id)) {
            return $this->getNextAtJob();
        }

        $next_cron_timestamp = $this->getCronTimestamp($cron_id);

        if ($next_at_timestamp <= $next_cron_timestamp) {
            return $this->getNextAtJob();
        }

        return $this->getNextCronJob();
    }

    /**
     * getNextAtJob
     *
     * @return null|\QueueJitsu\Job\Job
     */
    protected function getNextAtJob()
    {
        $next_timestamp = $this->getNextAtTimestamp();

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
        $key = sprintf('%s:%s', self::AT_QUEUE_NAME, $timestamp);

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
        $key = sprintf('%s:%s', self::AT_QUEUE_NAME, $timestamp);

        if ($this->client->llen($key)) {
            $this->client->del([$key]);
            $this->client->zrem(self::AT_QUEUE_NAME, $timestamp);
        }
    }

    /**
     * getCronTimestamp
     *
     * @param string $cron_id
     *
     * @return int|null
     */
    private function getCronTimestamp(string $cron_id)
    {
        $items = $this->client->zscore(self::CRON_QUEUE_NAME, $cron_id);

        if (empty($items)) {
            return null;
        }

        return (int)$items[0];
    }

    /**
     * getNextCronJob
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @return null|\QueueJitsu\Job\Job
     *
     * @throws \RuntimeException
     */
    private function getNextCronJob()
    {
        $cron_id = $this->getNextCronId();

        if (is_null($cron_id)) {
            return null;
        }

        $key = sprintf('%s:%s', self::CRON_QUEUE_NAME, $cron_id);

        $data = json_decode($this->client->get($key), true);

        $this->updateCron($cron_id, $data['cron']);

        $job = $data['job'];

        return new Job($job['class'], $job['queue'], $job['args']);
    }

    /**
     * updateCron
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param string $id
     * @param string $cron
     *
     * @throws \RuntimeException
     */
    private function updateCron(string $id, string $cron)
    {
        $cron = CronExpression::factory($cron);
        $next_run = $cron->getNextRunDate()->getTimestamp();

        $this->client->zadd(self::CRON_QUEUE_NAME, [$id => $next_run]);
    }
}
