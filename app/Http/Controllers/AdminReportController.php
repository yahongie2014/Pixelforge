<?php

namespace App\Http\Controllers;

use App\Actions\GenerateAdminReportAction;
use App\Http\Requests\AdminReportRequest;
use Inertia\Inertia;
use Inertia\Response;

class AdminReportController extends Controller
{
    public function __construct(
        private readonly GenerateAdminReportAction $reportAction,
    ) {}

    /**
     * Display the operator financial and reconciliation report.
     * All data access and computation are abstracted via ReportRepository and Action.
     */
    public function index(AdminReportRequest $request): Response
    {
        $report = $this->reportAction->execute(
            $request->startDate(),
            $request->endDate(),
            $request->startCarbon(),
            $request->endCarbon()
        );

        return Inertia::render('Admin/Report', $report);
    }
}
