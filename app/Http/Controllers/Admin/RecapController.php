<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecapFilterRequest;
use App\Models\Building;
use App\Models\Faculty;
use App\Services\RecapService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecapController extends Controller
{
    public function __construct(private readonly RecapService $recapService) {}

    public function index(RecapFilterRequest $request): View
    {
        $filters = $request->filters();

        $recaps = [];
        foreach (array_keys(RecapService::COLUMNS) as $type) {
            $recaps[$type] = $this->recapService->recap($type, ...$filters);
        }

        return view('admin.recap.index', [
            'recaps' => $recaps,
            'filters' => $filters,
            'faculties' => Faculty::query()->orderBy('name')->get(['id', 'name']),
            'buildings' => Building::query()->with('faculty:id,name')->orderBy('name')->get(['id', 'name', 'faculty_id']),
        ]);
    }

    public function export(RecapFilterRequest $request, string $type): StreamedResponse
    {
        $filters = $request->filters();
        $columns = RecapService::COLUMNS[$type];
        $rows = $this->recapService->recap($type, ...$filters);

        $filename = sprintf(
            'rekap-%s_%s_sd_%s.csv',
            $type,
            $filters['from']->format('Ymd-Hi'),
            $filters['to']->format('Ymd-Hi'),
        );

        return response()->streamDownload(function () use ($columns, $rows): void {
            $out = fopen('php://output', 'w');

            // BOM UTF-8 agar Excel membaca karakter non-ASCII dengan benar.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_values($columns), ',', '"', '');

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn (string $key) => $row[$key], array_keys($columns)), ',', '"', '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
