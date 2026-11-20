<?php

namespace App\Http\Controllers;

use App\Actions\SubmitGenerationAction;
use App\Actions\SyncGenerationFromApiAction;
use App\Http\Requests\EstimateGenerationRequest;
use App\Http\Requests\GenerationIndexRequest;
use App\Http\Requests\StoreGenerationRequest;
use App\Models\Generation;
use App\Repositories\Contracts\GenerationRepositoryInterface;
use App\Services\PixelforgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GenerationController extends Controller
{
    public function __construct(
        private readonly GenerationRepositoryInterface $generations,
        private readonly PixelforgeService $pixelforge,
        private readonly SubmitGenerationAction $submit,
        private readonly SyncGenerationFromApiAction $sync,
    ) {}

    /**
     * Display the generation studio and history.
     */
    public function index(GenerationIndexRequest $request): Response
    {
        $user         = $request->user();
        $statusFilter = $request->validated('status');

        return Inertia::render('Generations/Index', [
            'generations'   => $this->generations->paginateForUser($user, 12, $statusFilter ?: null),
            'pricing'       => $this->pixelforge->getPricing(),
            'stats'         => $this->generations->getStatsForUser($user),
            'currentFilter' => $statusFilter ?? 'all',
        ]);
    }

    /**
     * Return a real-time cost estimate (cached in Redis / default cache store for 60s).
     */
    public function estimate(EstimateGenerationRequest $request): JsonResponse
    {
        $v         = $request->validated();
        $prompt    = $v['prompt'] ?? '';
        $size      = $v['size'] ?? '1024x1024';
        $quality   = $v['quality'] ?? 'medium';
        $refImages = array_values(array_filter($v['reference_images'] ?? []));

        $cacheKey = 'estimate:' . md5($prompt . '|' . $size . '|' . $quality . '|' . json_encode($refImages));
        $estimate = Cache::remember($cacheKey, 60, function () use ($prompt, $size, $quality, $refImages) {
            return $this->pixelforge->calculateEstimatedCost($prompt, $size, $quality, $refImages);
        });

        $user      = $request->user();
        $canAfford = $user ? $user->hasSufficientBalance($estimate['user']['max']) : true;

        return response()->json([
            'estimate'     => $estimate,
            'can_afford'   => $canAfford,
            'user_balance' => $user ? (string) $user->balance : null,
        ]);
    }

    /**
     * Create and submit a new generation.
     */
    public function store(StoreGenerationRequest $request): RedirectResponse
    {
        $result = $this->submit->execute($request->user(), $request->validated());

        if ($result->successful) {
            return redirect()->route('generations.index')->with('success', $result->successMessage);
        }

        return back()->withErrors($result->errors);
    }

    /**
     * Poll status of a generation; syncs live from Pixelforge if still non-terminal.
     */
    public function status(Request $request, Generation $generation): JsonResponse
    {
        Gate::authorize('view', $generation);

        if (! $generation->isTerminal() && $generation->pixelforge_id) {
            $remote = $this->pixelforge->getGeneration($generation->pixelforge_id);
            if ($remote) {
                $this->sync->execute($generation, $remote);
            }
        }

        return response()->json([
            'generation'   => $generation->fresh(),
            'user_balance' => (string) $request->user()->fresh()->balance,
        ]);
    }
}
