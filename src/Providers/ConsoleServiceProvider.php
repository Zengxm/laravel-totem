<?php

namespace Studio\Totem\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Studio\Totem\Events\Executed;
use Studio\Totem\Events\Executing;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Register any services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('totem.console_enbaled')) {
            $this->app->resolving(Schedule::class, function ($schedule) {
                $this->schedule($schedule);
            });
        }
    }

    /**
     * Prepare schedule from tasks.
     *
     * @param Schedule $schedule
     */
    public function schedule(Schedule $schedule)
    {
        $tasks = app('totem.tasks')->findAllActive();

        $tasks->each(function ($task) use ($schedule) {
            $event = $schedule->command($task->command, $task->compileParameters(true));

            $event->cron($task->getCronExpression())
                ->name($task->description)
                ->timezone($task->timezone)
                ->before(function () use ($task, $event) {
                    $event->start = microtime(true);
                    Executing::dispatch($task);
                })
                ->after(function () use ($event, $task) {
                    Executed::dispatch($task, $event->start);
                })
                ->onFailure(function () use ($event, $task) {
                    Log::channel(config('totem.log_channel'))->error($event->command.'--'.'执行失败', $task->compileParameters(true));
                })
                ->sendOutputTo(config('totem.log_path').DIRECTORY_SEPARATOR.$task->getMutexName());
            if ($task->dont_overlap) {
                $event->withoutOverlapping();
            }
            if ($task->run_in_background) {
                $event->runInBackground();
            }
            if ($task->run_on_one_server && in_array(config('cache.default'), ['memcached', 'redis'])) {
                $event->onOneServer();
            }
        });
    }
}
