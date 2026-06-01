<?php

namespace App\Scheduler;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('alerte')]
class AlerteScheduler implements ScheduleProviderInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::cron('0 2 * * *', new VerifierAlertesMessage())
            )
            ->stateful($this->cache);
    }
}
