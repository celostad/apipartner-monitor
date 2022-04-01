## Installation

```
composer require celostad/apipartner-monitor
```

## Configuration

Copy configuration & migration to your project:

```
php artisan vendor:publish --provider="celostad\QueueMonitor\Providers\QueueMonitorProvider"  --tag=config --tag=migrations
```

Migrate the Queue Monitoring table. The table name can be configured in the config file or via the published migration.

```
php artisan migrate
```

## Usage

To monitor a job, simply add the `celostad\QueueMonitor\Traits\IsMonitored` Trait.

```php
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use celostad\QueueMonitor\Traits\IsMonitored; // <---

class ExampleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use IsMonitored; // <---
}
```

**Important!** You need to implement the `Illuminate\Contracts\Queue\ShouldQueue` interface to your job class. Otherwise, Laravel will not dispatch any events containing status information for monitoring the job.

## UI

You can enable the optional UI routes by calling `Route::queueMonitor()` inside your route file, similar to the official 

```php
Route::prefix('jobs')->group(function () {
    Route::queueMonitor();
});
```


This package was inspired by gilbitron's [laravel-queue-monitor](https://github.com/gilbitron/laravel-queue-monitor) which is not maintained anymore.
