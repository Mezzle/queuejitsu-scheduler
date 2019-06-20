<?php

declare(strict_types=1);
/**
 * @copyright (c) 2017 Stickee Technology Limited
 */

namespace QueueJitsu\Scheduler\Adapter;

use QueueJitsu\Job\Job;

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
     * @return null|Job
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
