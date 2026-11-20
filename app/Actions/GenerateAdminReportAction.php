<?php

namespace App\Actions;

use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Services\PixelforgeService;
use Carbon\Carbon;

class GenerateAdminReportAction
{
    public function __construct(
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly PixelforgeService $pixelforge,
    ) {}

    /**
     * Build the reconciled financial report combining internal ledger data with external API usage.
     *
     * @return array{
     *     dailyReport: array,
     *     userReport: array,
     *     summary: array,
     *     filters: array{start_date: string, end_date: string}
     * }
     */
    public function execute(string $startDate, string $endDate, Carbon $startCarbon, Carbon $endCarbon): array
    {
        // 1. Fetch external provider usage from Pixelforge for exact reconciliation
        $pixelforgeUsage = $this->pixelforge->getUsage($startDate, $endDate);
        $pixelforgeDailyMap = [];
        if ($pixelforgeUsage && isset($pixelforgeUsage['data'])) {
            foreach ($pixelforgeUsage['data'] as $dayItem) {
                $pixelforgeDailyMap[$dayItem['date']] = $dayItem;
            }
        }

        // 2. Fetch daily internal metrics from report repository
        $ourDailyMap = $this->reportRepository->getDailyGenerationStats($startCarbon, $endCarbon);

        // 3. Reconcile day-by-day across complete date period
        $period               = Carbon::parse($startDate)->daysUntil($endDate);
        $dailyReport          = [];
        $totalSpend           = '0.000000000';
        $totalProviderCost    = '0.000000000';
        $totalProfit          = '0.000000000';
        $totalGenerationsCount = 0;
        $totalDiscrepancy     = '0.000000000';

        foreach ($period as $dt) {
            $dateStr = $dt->format('Y-m-d');
            $ourData = $ourDailyMap[$dateStr] ?? $this->emptyDayRow($dateStr);

            $remoteData = $pixelforgeDailyMap[$dateStr] ?? null;
            $remoteCost = $remoteData['cost_usd'] ?? '0.000000000';
            $remoteGens = $remoteData['generations'] ?? 0;

            $discrepancy  = bcsub($ourData['provider_cost'], $remoteCost, 9);
            $isExactMatch = bccomp($ourData['provider_cost'], $remoteCost, 9) === 0;

            $marginPercent = bccomp($ourData['credit_spend'], '0', 9) > 0
                ? round((float) bcdiv(bcmul($ourData['profit'], '100', 9), $ourData['credit_spend'], 4), 2)
                : 20.0;

            $dailyReport[] = [
                'date'                 => $dateStr,
                'generations'          => $ourData['generations'],
                'credit_spend'         => $ourData['credit_spend'],
                'provider_cost'        => $ourData['provider_cost'],
                'remote_provider_cost' => $remoteCost,
                'remote_generations'   => $remoteGens,
                'is_match'             => $isExactMatch,
                'discrepancy'          => $discrepancy,
                'profit'               => $ourData['profit'],
                'margin_percent'       => $marginPercent,
            ];

            $totalSpend            = bcadd($totalSpend, $ourData['credit_spend'], 9);
            $totalProviderCost     = bcadd($totalProviderCost, $ourData['provider_cost'], 9);
            $totalProfit           = bcadd($totalProfit, $ourData['profit'], 9);
            $totalGenerationsCount += $ourData['generations'];
            $totalDiscrepancy      = bcadd($totalDiscrepancy, $discrepancy, 9);
        }

        $dailyReport = array_reverse($dailyReport);

        // 4. Fetch user spend breakdown from repository
        $userReport = $this->reportRepository->getUserSpendReport();

        // 5. Global KPIs
        $globalMargin = bccomp($totalSpend, '0', 9) > 0
            ? round((float) bcdiv(bcmul($totalProfit, '100', 9), $totalSpend, 4), 2)
            : 20.0;

        return [
            'dailyReport' => $dailyReport,
            'userReport'  => $userReport,
            'summary'     => [
                'total_spend'           => $totalSpend,
                'total_provider_cost'   => $totalProviderCost,
                'total_profit'          => $totalProfit,
                'total_generations'     => $totalGenerationsCount,
                'global_margin'         => $globalMargin,
                'is_reconciled'         => bccomp($totalDiscrepancy, '0', 9) === 0,
                'total_discrepancy'     => $totalDiscrepancy,
                'pixelforge_total_cost' => $pixelforgeUsage['total']['cost_usd'] ?? null,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
        ];
    }

    private function emptyDayRow(string $date): array
    {
        return [
            'date'               => $date,
            'generations'        => 0,
            'input_text_tokens'  => 0,
            'input_image_tokens' => 0,
            'output_tokens'      => 0,
            'reasoning_tokens'   => 0,
            'provider_cost'      => '0.000000000',
            'credit_spend'       => '0.000000000',
            'profit'             => '0.000000000',
        ];
    }
}
