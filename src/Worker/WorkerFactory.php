<?php
/**
 * @copyright (c) 2017 Stickee Technology Limited
 */

namespace QueueJitsu\Scheduler\Worker;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use QueueJitsu\Scheduler\Scheduler;
use QueueJitsu\Worker\WorkerManager;

/**
 * Class WorkerFactory
 *
 * @package QueueJitsu\Scheduler\Worker
 */
class WorkerFactory
{
    /**
     * __invoke
     *
     * @param \Psr\Container\ContainerInterface $container
     *
     * @return \QueueJitsu\Scheduler\Worker\Worker
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container)
    {
        $logger_class = $container->has(LoggerInterface::class) ? LoggerInterface::class : NullLogger::class;

        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $container->get($logger_class);

        /** @var Scheduler $scheduler */
        $scheduler = $container->get(Scheduler::class);

        /** @var WorkerManager $worker_manager */
        $worker_manager = $container->get(WorkerManager::class);

        return new Worker($logger, $worker_manager, $scheduler);
    }
}
