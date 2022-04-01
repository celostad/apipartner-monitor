<?php

namespace celostad\QueueMonitor\Tests;

use celostad\QueueMonitor\Services\ClassUses;
use celostad\QueueMonitor\Tests\Support\MonitoredExtendingJob;
use celostad\QueueMonitor\Tests\Support\MonitoredJob;
use celostad\QueueMonitor\Traits\IsMonitored;

class ClassUsesTraitTest extends TestCase
{
    public function testUsingMonitorTrait()
    {
        $this->assertArrayHasKey(
            IsMonitored::class,
            ClassUses::classUsesRecursive(MonitoredJob::class)
        );
    }

    public function testUsingMonitorTraitExtended()
    {
        $this->assertArrayHasKey(
            IsMonitored::class,
            ClassUses::classUsesRecursive(MonitoredExtendingJob::class)
        );
    }
}
