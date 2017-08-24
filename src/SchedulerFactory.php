<?php
/**
 * @copyright (c) 2017 Stickee Technology Limited
 */

namespace QueueJitsu\Scheduler;

use Psr\Container\ContainerInterface;
use QueueJitsu\Job\JobManager;
use QueueJitsu\Scheduler\Adapter\AdapterInterface;

/**
 * Class SchedulerFactory
 *
 * @package QueueJitsu\Scheduler
 */
class SchedulerFactory
{
    /**
     * __invoke
     *
     * @param \Psr\Container\ContainerInterface $container
     *
     * @return \QueueJitsu\Scheduler\Scheduler
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container)
    {
        /** @var AdapterInterface $adapter */
        $adapter = $container->get(AdapterInterface::class);

        /** @var JobManager $job_manager */
        $job_manager = $container->get(JobManager::class);

        return new Scheduler($adapter, $job_manager);
    }
}
