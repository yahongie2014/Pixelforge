<?php

namespace App\Repositories\Eloquent;

use App\Models\Generation;
use App\Models\User;
use App\Repositories\Contracts\GenerationRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentGenerationRepository implements GenerationRepositoryInterface
{
    public function create(array $data): Generation
    {
        return Generation::create($data);
    }

    public function update(Generation $generation, array $data): Generation
    {
        $generation->update($data);

        return $generation->fresh();
    }

    public function findById(int $id): ?Generation
    {
        return Generation::find($id);
    }

    public function findByPixelforgeId(string $pixelforgeId): ?Generation
    {
        return Generation::where('pixelforge_id', $pixelforgeId)->first();
    }

    public function findByIdempotencyKey(string $key): ?Generation
    {
        return Generation::where('idempotency_key', $key)->first();
    }

    public function paginateForUser(User $user, int $perPage = 12, ?string $status = null): LengthAwarePaginator
    {
        $validStatuses = ['queued', 'processing', 'succeeded', 'failed'];

        $query = $user->generations()->latest();

        if ($status && in_array($status, $validStatuses, true)) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getStatsForUser(User $user): array
    {
        $succeeded = $user->generations()->where('status', 'succeeded');

        return [
            'total_generations' => $user->generations()->count(),
            'succeeded_count'   => $succeeded->count(),
            'total_spent'       => (string) ($succeeded->sum('actual_cost_user') ?? '0.000000000'),
            'balance'           => (string) $user->balance,
        ];
    }

    public function getCompletedBetween(Carbon $from, Carbon $to): Collection
    {
        return Generation::where('status', 'succeeded')
            ->whereNotNull('pixelforge_completed_at')
            ->whereBetween('pixelforge_completed_at', [$from, $to])
            ->get();
    }

    public function getSpendByUser(): array
    {
        $results = Generation::where('status', 'succeeded')
            ->selectRaw('user_id, SUM(actual_cost_user) as total_user, SUM(actual_cost_provider) as total_provider')
            ->groupBy('user_id')
            ->get();

        $map = [];
        foreach ($results as $row) {
            $userCost = number_format((float) ($row->total_user ?? 0), 9, '.', '');
            $providerCost = number_format((float) ($row->total_provider ?? 0), 9, '.', '');
            $map[$row->user_id] = [
                'credit_spend'  => $userCost,
                'provider_cost' => $providerCost,
                'profit'        => bcsub($userCost, $providerCost, 9),
            ];
        }

        return $map;
    }
}
