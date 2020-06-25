<?php
/*
 * Copyright (c) 2017 - 2020 Martin Meredith
 * Copyright (c) 2017 Stickee Technology Limited
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace QueueJitsu\Scheduler;

use QueueJitsu\Job\Job;
use QueueJitsu\Job\JobManager;
use QueueJitsu\Scheduler\Adapter\AdapterInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;

/**
 * Class Scheduler
 *
 * @package QueueJitsu\Scheduler
 */
class Scheduler implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    const STATUS_SCHEDULED = 63;

    /**
     * @var \QueueJitsu\Scheduler\Adapter\AdapterInterface $adapter
     */
    private $adapter;

    /**
     * @var \QueueJitsu\Job\JobManager $job_manager
     */
    private $job_manager;

    /**
     * Scheduler constructor.
     *
     * @param \QueueJitsu\Scheduler\Adapter\AdapterInterface $adapter
     * @param \QueueJitsu\Job\JobManager $job_manager
     */
    public function __construct(
        AdapterInterface $adapter,
        JobManager $job_manager
    ) {
        $this->adapter = $adapter;
        $this->job_manager = $job_manager;
    }

    /**
     * enqueueIn
     *
     * @param int $seconds
     * @param \QueueJitsu\Job\Job $job
     */
    public function enqueueIn(int $seconds, Job $job): void
    {
        $at = time() + $seconds;

        $this->enqueueAt($at, $job);
    }

    /**
     * enqueueAt
     *
     * @param int $at
     * @param \QueueJitsu\Job\Job $job
     */
    public function enqueueAt(int $at, Job $job): void
    {
        $this->adapter->enqueueAt($at, $job);

        $this->getEventManager()->trigger('afterSchedule', $job, ['at' => $at]);
    }

    /**
     * enqueueCron
     *
     * @param string $cron
     * @param \QueueJitsu\Job\Job $job
     *
     * @throws \RuntimeException
     */
    public function enqueueCron(string $cron, Job $job): void
    {
        $this->adapter->enqueueCron($cron, $job);
    }

    /**
     * schedule
     */
    public function schedule(): void
    {
        while ($job = $this->getNextJob()) {
            $this->job_manager->enqueue($job);
            $this->job_manager->updateStatus($job, self::STATUS_SCHEDULED);
        }
    }

    /**
     * getNextJob
     *
     * @return null|Job
     */
    private function getNextJob(): ?Job
    {
        return $this->adapter->getNextJob();
    }
}
