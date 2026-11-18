<?php

namespace App\Filament\Pages;

use App\Models\BioAnalyticsEvent;
use App\Models\BioPage;
use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard Analytics DQWS';

    protected static ?int $navigationSort = -2;

    protected string $view = 'filament.pages.bio-analytics';

    protected Width | string | null $maxContentWidth = Width::Full;

    public ?array $pageFilters = [];

    public ?BioPage $bioPage = null;

    public function mount(): void
    {
        $this->bioPage = BioPage::where('slug', 'dqws')->first();

        $now = Carbon::now();
        $defaults = [
            'preset' => '30d',
            'startDate' => $now->copy()->subDays(29)->toDateString(),
            'endDate' => $now->toDateString(),
        ];

        $this->filters = array_merge($defaults, array_filter($this->filters ?? []));
        $this->pageFilters = $this->filters;
    }

    public function applyDateRange(string $startDate, string $endDate, string $preset = 'custom'): void
    {
        try {
            $start = Carbon::parse($startDate)->toDateString();
        } catch (\Throwable) {
            $start = Carbon::now()->subDays(29)->toDateString();
        }

        try {
            $end = Carbon::parse($endDate)->toDateString();
        } catch (\Throwable) {
            $end = Carbon::now()->toDateString();
        }

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        $this->filters = [
            'preset' => $preset,
            'startDate' => $start,
            'endDate' => $end,
        ];
        $this->pageFilters = $this->filters;

        if ($this->persistsFiltersInSession()) {
            session()->put($this->getFiltersSessionKey(), $this->filters);
        }
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes([])
            ->components([
                DatePicker::make('startDate')
                    ->label('Dari Tanggal')
                    ->format('Y-m-d')
                    ->live(),
                DatePicker::make('endDate')
                    ->label('Sampai Tanggal')
                    ->format('Y-m-d')
                    ->live(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetFilters')
                ->label('Reset Filter')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('resetFilters'),

            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action('exportCsv'),
        ];
    }

    public function resetFilters(): void
    {
        $now = Carbon::now();
        $this->applyDateRange(
            $now->copy()->subDays(29)->toDateString(),
            $now->toDateString(),
            '30d'
        );
    }

    public function exportCsv(): StreamedResponse
    {
        [$start, $end] = $this->resolveDateRange();
        $pageId = $this->bioPage?->id ?? BioPage::where('slug', 'dqws')->value('id');

        $events = BioAnalyticsEvent::query()
            ->when($pageId, fn ($q) => $q->where('bio_page_id', $pageId))
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->get();

        $filename = 'analytics-dqws-' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($events): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Waktu',
                'Tipe Event',
                'ID Blok',
                'Label Tombol / Blok',
                'Tipe Blok',
                'URL Tujuan',
                'Sumber Trafik',
                'UTM Source',
                'UTM Medium',
                'UTM Campaign',
                'Perangkat',
                'OS',
                'Browser',
                'Visitor Hash',
            ]);

            foreach ($events as $event) {
                fputcsv($handle, [
                    $event->created_at?->format('Y-m-d H:i:s'),
                    $event->event_type,
                    $event->block_id,
                    $event->block_label,
                    $event->block_type,
                    $event->target_url,
                    $event->referrer,
                    $event->utm_source,
                    $event->utm_medium,
                    $event->utm_campaign,
                    $event->device_type,
                    $event->os,
                    $event->browser,
                    substr((string) $event->visitor_hash, 0, 16),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveDateRange(): array
    {
        $this->pageFilters = $this->filters ?? $this->pageFilters ?? [];

        $from = $this->pageFilters['startDate'] ?? $this->filters['startDate'] ?? null;
        $to = $this->pageFilters['endDate'] ?? $this->filters['endDate'] ?? null;

        try {
            $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->subDays(29)->startOfDay();
        } catch (\Throwable) {
            $start = Carbon::now()->subDays(29)->startOfDay();
        }

        try {
            $end = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();
        } catch (\Throwable) {
            $end = Carbon::now()->endOfDay();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    #[Computed]
    public function stats(): array
    {
        [$start, $end] = $this->resolveDateRange();
        $pageId = $this->bioPage?->id ?? BioPage::where('slug', 'dqws')->value('id');

        $events = BioAnalyticsEvent::query()
            ->when($pageId, fn ($q) => $q->where('bio_page_id', $pageId))
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $views = $events->where('event_type', 'view');
        $clicks = $events->whereIn('event_type', ['click', 'popup_click']);

        $popupViews = $events->where('event_type', 'popup_view')->count();
        $popupCloses = $events->where('event_type', 'popup_close')->count();
        $popupClicks = $events->where('event_type', 'popup_click')->count();
        $popupInteractions = $popupClicks + $popupCloses;
        $popupConversionRate = $popupInteractions > 0
            ? round(($popupClicks / $popupInteractions) * 100, 1)
            : 0.0;
        $popupViewCtr = $popupViews > 0
            ? round(($popupClicks / $popupViews) * 100, 1)
            : 0.0;

        $totalViews = $views->count();
        $uniqueVisitors = $views->pluck('visitor_hash')->unique()->count();
        $totalClicks = $clicks->count();
        $ctr = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 1) : 0.0;

        // 1. Daily Trend (Views vs Clicks)
        $diffDays = (int) $start->diffInDays($end);
        $chartStart = $diffDays > 60 ? $end->copy()->subDays(59)->startOfDay() : $start->copy();
        $period = CarbonPeriod::create($chartStart->toDateString(), $end->toDateString());

        $viewsByDate = $views->groupBy(fn ($e) => Carbon::parse($e->created_at)->toDateString());
        $clicksByDate = $clicks->groupBy(fn ($e) => Carbon::parse($e->created_at)->toDateString());

        $dailyTrend = [];
        $maxDailyValue = 1;
        foreach ($period as $date) {
            $key = $date->toDateString();
            $vCount = isset($viewsByDate[$key]) ? $viewsByDate[$key]->count() : 0;
            $cCount = isset($clicksByDate[$key]) ? $clicksByDate[$key]->count() : 0;
            $maxDailyValue = max($maxDailyValue, $vCount, $cCount);

            $dailyTrend[] = [
                'date' => $key,
                'label' => $date->translatedFormat('d M'),
                'views' => $vCount,
                'clicks' => $cCount,
            ];
        }

        // 2. Button Performance Table
        $buttonPerformance = $clicks
            ->groupBy(fn ($e) => ($e->block_id ?: $e->block_label ?: 'unknown'))
            ->map(function ($group) use ($totalClicks) {
                $first = $group->first();
                $count = $group->count();
                $pct = $totalClicks > 0 ? round(($count / $totalClicks) * 100, 1) : 0.0;

                return [
                    'block_id' => $first->block_id,
                    'label' => $first->block_label ?: 'Tombol Tanpa Judul',
                    'type' => $first->block_type ?: 'link',
                    'target_url' => $first->target_url,
                    'clicks' => $count,
                    'percentage' => $pct,
                ];
            })
            ->sortByDesc('clicks')
            ->values()
            ->all();

        // 3. Traffic Sources (Referrer) & UTM Breakdown
        $totalEventsCount = max(1, $views->count());
        $referrers = $views
            ->groupBy(fn ($e) => $e->referrer ?: 'Direct')
            ->map(function ($group, $source) use ($totalEventsCount) {
                $count = $group->count();
                return [
                    'source' => $source,
                    'count' => $count,
                    'percentage' => round(($count / $totalEventsCount) * 100, 1),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();

        $utmCampaigns = $views
            ->filter(fn ($e) => ! empty($e->utm_source) || ! empty($e->utm_campaign))
            ->groupBy(fn ($e) => trim(($e->utm_source ?: '-') . ' / ' . ($e->utm_campaign ?: '-')))
            ->map(fn ($group, $name) => [
                'name' => $name,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->all();

        // 4. Device, OS & Browser Breakdown
        $devices = $views
            ->groupBy(fn ($e) => $e->device_type ?: 'Mobile')
            ->map(function ($group, $device) use ($totalEventsCount) {
                $count = $group->count();
                return [
                    'device' => $device,
                    'count' => $count,
                    'percentage' => round(($count / $totalEventsCount) * 100, 1),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();

        $oses = $views
            ->groupBy(fn ($e) => $e->os ?: 'Lainnya')
            ->map(function ($group, $os) use ($totalEventsCount) {
                $count = $group->count();
                return [
                    'os' => $os,
                    'count' => $count,
                    'percentage' => round(($count / $totalEventsCount) * 100, 1),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();

        $browsers = $views
            ->groupBy(fn ($e) => $e->browser ?: 'Lainnya')
            ->map(function ($group, $browser) use ($totalEventsCount) {
                $count = $group->count();
                return [
                    'browser' => $browser,
                    'count' => $count,
                    'percentage' => round(($count / $totalEventsCount) * 100, 1),
                ];
            })
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->all();

        // 5. Peak Hours (00:00 - 23:00)
        $hourlyCounts = array_fill(0, 24, ['views' => 0, 'clicks' => 0, 'total' => 0]);
        foreach ($events as $event) {
            $hour = (int) Carbon::parse($event->created_at)->format('G');
            if ($event->event_type === 'view') {
                $hourlyCounts[$hour]['views']++;
            } else {
                $hourlyCounts[$hour]['clicks']++;
            }
            $hourlyCounts[$hour]['total']++;
        }

        $maxHourlyTotal = 1;
        $peakHour = 0;
        $peakHourCount = 0;
        $peakHours = [];
        for ($h = 0; $h < 24; $h++) {
            $tot = $hourlyCounts[$h]['total'];
            if ($tot > $maxHourlyTotal) {
                $maxHourlyTotal = $tot;
            }
            if ($tot > $peakHourCount) {
                $peakHourCount = $tot;
                $peakHour = $h;
            }
            $peakHours[] = [
                'hour' => sprintf('%02d:00', $h),
                'short' => sprintf('%02d', $h),
                'views' => $hourlyCounts[$h]['views'],
                'clicks' => $hourlyCounts[$h]['clicks'],
                'total' => $tot,
            ];
        }

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'preset' => $this->pageFilters['preset'] ?? '30d',
            'period_label' => $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
            'total_views' => $totalViews,
            'unique_visitors' => $uniqueVisitors,
            'total_clicks' => $totalClicks,
            'ctr' => $ctr,
            'popup_stats' => [
                'views' => $popupViews,
                'closes' => $popupCloses,
                'clicks' => $popupClicks,
                'conversion_rate' => $popupConversionRate,
                'view_ctr' => $popupViewCtr,
            ],
            'daily_trend' => $dailyTrend,
            'max_daily_value' => $maxDailyValue,
            'button_performance' => $buttonPerformance,
            'referrers' => $referrers,
            'utm_campaigns' => $utmCampaigns,
            'devices' => $devices,
            'oses' => $oses,
            'browsers' => $browsers,
            'peak_hours' => $peakHours,
            'max_hourly_total' => $maxHourlyTotal,
            'peak_hour_label' => $peakHourCount > 0 ? sprintf('%02d:00 - %02d:59', $peakHour, $peakHour) : 'Belum ada aktivitas',
            'peak_hour_count' => $peakHourCount,
        ];
    }
}
