<?php
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
}
