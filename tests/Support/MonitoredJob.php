<?php

namespace celostad\QueueMonitor\Tests\Support;

use celostad\QueueMonitor\Traits\IsMonitored;

class MonitoredJob extends BaseJob
{
    use IsMonitored;
}
