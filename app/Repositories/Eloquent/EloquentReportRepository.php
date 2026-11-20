<?php

namespace App\Repositories\Eloquent;

use App\Models\Generation;
use App\Models\User;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Carbon\Carbon;

class EloquentReportRepository implements ReportRepositoryInterface
{
    public function getDailyGenerationStats(Carbon $startDate, Carbon $endDate): array
    {
        $generations = Generation::where('status', 'succeeded')
            ->whereNotNull('pixelforge_completed_at')
            ->whereBetween('pixelforge_completed_at', [$startDate, $endDate])
            ->get();

        $dailyMap = [];

        foreach ($generations as $gen) {
            $date = Carbon::parse($gen->pixelforge_completed_at)->utc()->format('Y-m-d');

            if (! isset($dailyMap[$date])) {
                $dailyMap[$date] = [
                    'date'                => $date,
                    'generations'         => 0,
                    'input_text_tokens'   => 0,
                    'input_image_tokens'  => 0,
                    'output_tokens'       => 0,
                    'reasoning_tokens'    => 0,
                    'provider_cost'       => '0.000000000',
                    'credit_spend'        => '0.000000000',
                    'profit'              => '0.000000000',
                ];
            }

            $dailyMap[$date]['generations']++;
            $dailyMap[$date]['input_text_tokens']  += $gen->input_text_tokens;
            $dailyMap[$date]['input_image_tokens'] += $gen->input_image_tokens;
            $dailyMap[$date]['output_tokens']      += $gen->output_tokens;
            $dailyMap[$date]['reasoning_tokens']   += $gen->reasoning_tokens;

            $dailyMap[$date]['provider_cost'] = bcadd($dailyMap[$date]['provider_cost'], $gen->actual_cost_provider, 9);
            $dailyMap[$date]['credit_spend']  = bcadd($dailyMap[$date]['credit_spend'], $gen->actual_cost_user, 9);
            $dailyMap[$date]['profit']        = bcsub($dailyMap[$date]['credit_spend'], $dailyMap[$date]['provider_cost'], 9);
        }

        return $dailyMap;
    }

    public function getUserSpendReport(): array
    {
        $users = User::withCount([
            'generations',
            'generations as succeeded_generations_count' => fn ($q) => $q->where('status', 'succeeded'),
            'generations as failed_generations_count'    => fn ($q) => $q->where('status', 'failed'),
        ])->get();

        $spendAggregates = Generation::where('status', 'succeeded')
            ->selectRaw('user_id, SUM(actual_cost_user) as total_user, SUM(actual_cost_provider) as total_provider')
            ->groupBy('user_id')
            ->get();

        $spendMap = [];
        foreach ($spendAggregates as $row) {
            $userCost = number_format((float) ($row->total_user ?? 0), 9, '.', '');
            $providerCost = number_format((float) ($row->total_provider ?? 0), 9, '.', '');
            $spendMap[$row->user_id] = [
                'credit_spend'  => $userCost,
                'provider_cost' => $providerCost,
                'profit'        => bcsub($userCost, $providerCost, 9),
            ];
        }

        return $users->map(function (User $u) use ($spendMap) {
            $spend = $spendMap[$u->id] ?? [
                'credit_spend'  => '0.000000000',
                'provider_cost' => '0.000000000',
                'profit'        => '0.000000000',
            ];

            return [
                'id'                   => $u->id,
                'name'                 => $u->name,
                'email'                => $u->email,
                'is_admin'             => $u->is_admin,
                'balance'              => (string) $u->balance,
                'total_generations'    => $u->generations_count,
                'succeeded_generations' => $u->succeeded_generations_count,
                'failed_generations'   => $u->failed_generations_count,
                'credit_spend'         => $spend['credit_spend'],
                'provider_cost'        => $spend['provider_cost'],
                'profit'               => $spend['profit'],
            ];
        })->all();
    }
}
