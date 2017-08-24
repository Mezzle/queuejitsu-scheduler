<?php
/**
 * @copyright (c) 2017 Stickee Technology Limited
 */

namespace QueueJitsu\Scheduler\Adapter;

use QueueJitsu\Job\Job;
use Ramsey\Uuid\Uuid;

/**
 * Interface AdapterInterface
 *
 * @package QueueJitsu\Scheduler\Adapter
 */
interface AdapterInterface
{
    /**
     * getNextJob
     *
     * @return mixed
     */
    public function getNextJob(): ?Job;

    /**
     * enqueueAt
     *
     * @param int $at
     * @param \QueueJitsu\Job\Job $job
     */
    public function enqueueAt(int $at, Job $job): void;

    /**
     * enqueueCron
     *
     * @param string $cron
     * @param \QueueJitsu\Job\Job $job
     *
     * @throws \RuntimeException
     */
    public function enqueueCron(string $cron, Job $job);
}
