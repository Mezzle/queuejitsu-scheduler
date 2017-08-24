<?php
/**
 * Copyright (c) 2017 Martin Meredith
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

namespace QueueJitsu\Scheduler\Worker;

use Psr\Log\LoggerInterface;
use QueueJitsu\Scheduler\Scheduler;
use QueueJitsu\Worker\AbstractWorker;
use QueueJitsu\Worker\WorkerManager;

/**
 * Class Worker
 *
 * @package QueueJitsu\Scheduler\Worker
 */
class Worker extends AbstractWorker
{
    /**
     * @var \QueueJitsu\Scheduler\Scheduler $scheduler
     */
    private $scheduler;

    /**
     * Worker constructor.
     *
     * @param \Psr\Log\LoggerInterface $log
     * @param \QueueJitsu\Worker\WorkerManager $manager
     * @param \QueueJitsu\Scheduler\Scheduler $scheduler
     */
    public function __construct(
        LoggerInterface $log,
        WorkerManager $manager,
        Scheduler $scheduler
    ) {
        parent::__construct($log, $manager);

        $this->scheduler = $scheduler;
    }

    /**
     * getWorkerIdentifier
     *
     * @return string
     */
    protected function getWorkerIdentifier(): string
    {
        return 'scheduler';
    }

    /**
     * loop
     *
     */
    protected function loop(): void
    {
        $this->scheduler->schedule();
    }
}
