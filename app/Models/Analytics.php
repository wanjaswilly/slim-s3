<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Analytics extends Model
{
    protected $table = 'analyticss';
    protected $fillable = [
        'shop_id',
        'type',
        'period',
        'date',
        'metrics',
        'breakdown',
        'comparison',
        'metadata'
    ];

    protected $casts = [
        'date' => 'date',
        'metrics' => 'array',
        'breakdown' => 'array',
        'comparison' => 'array',
        'metadata' => 'array'
    ];

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('date', '>=', Carbon::now()->subDays($days));
    }

    // Methods
    public static function recordDailyMetrics($shopId)
    {
        $shop = Shop::find($shopId);
        if (!$shop) return;

        $today = Carbon::now()->toDateString();
        
        // Calculate daily metrics
        $sales = $shop->sales()
            ->whereDate('sale_date', $today)
            ->where('status', 'completed')
            ->get();

        $totalSales = $sales->count();
        $totalRevenue = $sales->sum('total_amount');
        $averageOrderValue = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        $productsSold = $sales->sum(function ($sale) {
            return $sale->items->sum('quantity');
        });

        $newCustomers = $shop->customers()
            ->whereDate('created_at', $today)
            ->count();

        // Store metrics
        return static::create([
            'shop_id' => $shopId,
            'type' => 'daily_summary',
            'period' => 'day',
            'date' => $today,
            'metrics' => [
                'total_sales' => $totalSales,
                'total_revenue' => $totalRevenue,
                'average_order_value' => $averageOrderValue,
                'products_sold' => $productsSold,
                'new_customers' => $newCustomers,
                'conversion_rate' => 0, // Would need traffic data
                'refund_rate' => 0
            ],
            'breakdown' => [
                'by_channel' => $sales->groupBy('channel')->map->count(),
                'by_payment_method' => $sales->groupBy('payment_method')->map->count()
            ]
        ]);
    }

    public static function getSalesTrend($shopId, $days = 30)
    {
        return static::where('shop_id', $shopId)
            ->where('type', 'daily_summary')
            ->where('date', '>=', Carbon::now()->subDays($days))
            ->orderBy('date')
            ->get()
            ->map(function ($record) {
                return [
                    'date' => $record->date->format('Y-m-d'),
                    'revenue' => $record->metrics['total_revenue'] ?? 0,
                    'sales' => $record->metrics['total_sales'] ?? 0
                ];
            });
    }

    public function getGrowthRate($previousPeriod)
    {
        $currentValue = $this->metrics['total_revenue'] ?? 0;
        $previousValue = $previousPeriod->metrics['total_revenue'] ?? 0;

        if ($previousValue == 0) {
            return $currentValue > 0 ? 100 : 0;
        }

        return (($currentValue - $previousValue) / $previousValue) * 100;
    }

    public function getTopProducts($limit = 5)
    {
        return $this->breakdown['top_products'] ?? [];
    }

    public function getChannelPerformance()
    {
        return $this->breakdown['by_channel'] ?? [];
    }
}