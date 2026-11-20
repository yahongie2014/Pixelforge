<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and normalises the date-range filter for the admin financial report.
 */
class AdminReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // EnsureUserIsAdmin middleware handles this; double-guard here too.
        return $this->user()?->is_admin === true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:end_date'],
            'end_date'   => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.date_format'      => 'Start date must be in YYYY-MM-DD format.',
            'end_date.date_format'        => 'End date must be in YYYY-MM-DD format.',
            'start_date.before_or_equal'  => 'Start date must be before or equal to end date.',
            'end_date.before_or_equal'    => 'End date cannot be in the future.',
        ];
    }

    /**
     * Return the validated start date, defaulting to 30 days ago.
     */
    public function startDate(): string
    {
        return $this->validated('start_date') ?? now()->subDays(29)->format('Y-m-d');
    }

    /**
     * Return the validated end date, defaulting to today.
     */
    public function endDate(): string
    {
        return $this->validated('end_date') ?? now()->format('Y-m-d');
    }

    /**
     * Carbon start of range (start of day, UTC).
     */
    public function startCarbon(): Carbon
    {
        return Carbon::parse($this->startDate())->startOfDay();
    }

    /**
     * Carbon end of range (end of day, UTC).
     */
    public function endCarbon(): Carbon
    {
        return Carbon::parse($this->endDate())->endOfDay();
    }
}
