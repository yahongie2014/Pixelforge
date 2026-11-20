<?php

namespace App\Http\Middleware;

use App\Enums\CreditPurchaseTier;
use App\Enums\GenerationQuality;
use App\Enums\GenerationSize;
use App\Enums\GenerationStatus;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'enums' => [
                'statuses' => array_map(fn (GenerationStatus $case) => [
                    'value'       => $case->value,
                    'label'       => $case->label(),
                    'rank'        => $case->rank(),
                    'is_terminal' => $case->isTerminal(),
                ], GenerationStatus::cases()),
                'sizes' => array_map(fn (GenerationSize $case) => [
                    'value'        => $case->value,
                    'label'        => $case->label(),
                    'aspect_ratio' => $case->aspectRatio(),
                ], GenerationSize::cases()),
                'qualities' => array_map(fn (GenerationQuality $case) => [
                    'value'    => $case->value,
                    'label'    => $case->label(),
                    'subtitle' => $case->subtitle(),
                ], GenerationQuality::cases()),
                'credit_tiers' => CreditPurchaseTier::values(),
            ],
        ];
    }
}
