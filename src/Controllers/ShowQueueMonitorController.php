<?php

namespace celostad\QueueMonitor\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use celostad\QueueMonitor\Controllers\Payloads\Metric;
use celostad\QueueMonitor\Controllers\Payloads\Metrics;
use celostad\QueueMonitor\Models\Contracts\MonitorContract;
use celostad\QueueMonitor\Services\QueueMonitor;

class ShowQueueMonitorController
{

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', 'string', Rule::in(['all', 'running', 'failed', 'succeeded'])],
            'queue' => ['nullable', 'string'],
            'partner' => ['nullable', 'string'],
            'uuid_job' => ['nullable', 'string'],
            'id_school' => ['nullable', 'integer'],

        ]);

        // força ALL a todos, caso a busca seja por JOB_ID
        if (!empty($data['uuid_job'])) {
            $data['type'] = 'all';
            $data['queue'] = 'all';
            $data['partner'] = 'all';
            $data['id_school'] = "";
        }

        // Limpa campo JOB_ID caso ID School enviado
        if (!empty($data['id_school'])) {
            $data['uuid_job'] = "";
            $data['type'] = 'all';
            $data['queue'] = 'all';
        }

        $filters = [
            'type'      => $data['type'] ?? 'all',
            'queue'     => $data['queue'] ?? 'all',
            'partner'   => $data['partner'] ?? 'all',
            'uuid_job'  => $data['uuid_job'] ?? 'all',
            'id_school' => $data['id_school'] ?? 'all',
        ];

        // dd($filters['type']);
        // dd($filters['partner']);
        $jobs = QueueMonitor::getModel()
            ->newQuery()
            ->when(($type = $filters['type']) && 'all' !== $type, static function (Builder $builder) use ($type) {
                switch ($type) {
                    case 'running':
                        $builder->whereNull('finished_at');
                        break;

                    case 'failed':
                        $builder->where('failed', 1)->whereNotNull('finished_at');
                        break;

                    case 'succeeded':
                        $builder->where('failed', 0)->whereNotNull('finished_at');
                        break;
                }
            })
            ->when(($queue = $filters['queue']) && 'all' !== $queue, static function (Builder $builder) use ($queue) {
                $builder->where('queue', $queue);
            })
            // INSERIDO APN 94 - Melhorias do board Horizon de Cargas Massivas
            ->when(($partner = $filters['partner']) && 'all' !== $partner, static function (Builder $builder) use ($partner) {
                $builder->where('id_partner', $partner);
            })
            ->when(($uuid_job = $filters['uuid_job']) && 'all' !== $uuid_job, static function (Builder $builder) use ($uuid_job) {
                $builder->where('uuid_job', $uuid_job);
            })
            ->when(($id_school = $filters['id_school']) && 'all' !== $id_school, static function (Builder $builder) use ($id_school) {
                $builder->where('id_school', $id_school);
            })
            //----------------------------------
            ->ordered()
            ->paginate(
                config('queue-monitor.ui.per_page')
            )
            ->appends(
                $request->all()
            );



        $queues = QueueMonitor::getModel()
            ->newQuery()
            ->select('queue')
            ->groupBy('queue')
            ->get()
            ->map(function (MonitorContract $monitor) {
                return $monitor->queue;
            })
            ->toArray();

        $partners = DB::table('users')
            ->join('queue_apipartner_monitor', 'queue_apipartner_monitor.id_partner', '=', 'users.id')
            ->select('users.*', 'queue_apipartner_monitor.id_partner')
            ->groupBy('queue_apipartner_monitor.id_partner')
            ->get()
            ->toArray();

        $uuid_jobs = QueueMonitor::getModel()
            ->newQuery()
            ->select('uuid_job')
            ->where('uuid_job', '=', $filters['uuid_job'])
            ->get();

        $id_schools = QueueMonitor::getModel()
            ->newQuery()
            ->select('id_school')
            ->where('id_school', '=', $filters['id_school'])
            ->get();

        $metrics = null;

        if (config('queue-monitor.ui.show_metrics')) {
            $metrics = $this->collectMetrics();
        }

        return view('queue-monitor::jobs', [
            'jobs' => $jobs,
            'filters' => $filters,
            'queues' => $queues,
            'partners' => $partners,
            'uuid_jobs' => $uuid_jobs,
            'id_schools' => $id_schools,
            'metrics' => $metrics,
        ]);
    }

    public function collectMetrics(): Metrics
    {
        $timeFrame = config('queue-monitor.ui.metrics_time_frame') ?? 2;

        $metrics = new Metrics();

        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(time_elapsed) as total_time_elapsed'),
            DB::raw('AVG(time_elapsed) as average_time_elapsed'),
        ];

        $aggregatedInfo = QueueMonitor::getModel()
            ->newQuery()
            ->select($aggregationColumns)
            ->where('started_at', '>=', Carbon::now()->subDays($timeFrame))
            ->first();

        $aggregatedComparisonInfo = QueueMonitor::getModel()
            ->newQuery()
            ->select($aggregationColumns)
            ->where('started_at', '>=', Carbon::now()->subDays($timeFrame * 2))
            ->where('started_at', '<=', Carbon::now()->subDays($timeFrame))
            ->first();

        if (null === $aggregatedInfo || null === $aggregatedComparisonInfo) {
            return $metrics;
        }

        return $metrics
            ->push(
                new Metric('Total Jobs Executed', $aggregatedInfo->count ?? 0, $aggregatedComparisonInfo->count, '%d')
            )
            ->push(
                new Metric('Total Execution Time', $aggregatedInfo->total_time_elapsed ?? 0, $aggregatedComparisonInfo->total_time_elapsed, '%ds')
            )
            ->push(
                new Metric('Average Execution Time', $aggregatedInfo->average_time_elapsed ?? 0, $aggregatedComparisonInfo->average_time_elapsed, '%0.2fs')
            );
    }
}
