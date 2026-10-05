<?php

namespace App\Services;

use App\Models\DamageReport;
use App\Models\Facility;
use App\Models\Reservation;
use App\Support\Status;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya sumber angka rekap (PRD 7.6). Halaman dan CSV memanggil
 * recap() yang sama dan membaca kolom dari COLUMNS yang sama, sehingga
 * nilai di layar dan di file tidak mungkin berbeda (T17).
 *
 * Periode bersifat setengah terbuka: [from, to).
 */
class RecapService
{
    public const string TYPE_PLACES = 'tempat';

    public const string TYPE_EQUIPMENT = 'alat';

    public const string TYPE_REPORTS = 'laporan';

    public const array PLACE_TYPES = [
        Status::FACILITY_CLASSROOM,
        Status::FACILITY_HALL,
        Status::FACILITY_LABORATORY,
        Status::FACILITY_FIELD,
    ];

    private const array LABEL_COLUMNS = [
        'faculty' => 'Fakultas',
        'building' => 'Gedung',
        'facility' => 'Fasilitas',
    ];

    /** Urutan kolom tabel dan CSV per jenis rekap. */
    public const array COLUMNS = [
        self::TYPE_PLACES => self::LABEL_COLUMNS + [
            'reservation_count' => 'Jumlah reservasi',
            'minutes' => 'Total menit terpakai',
        ],
        self::TYPE_EQUIPMENT => self::LABEL_COLUMNS + [
            'reservation_count' => 'Jumlah reservasi',
            'quantity' => 'Total unit',
            'unit_minutes' => 'Unit-menit',
        ],
        self::TYPE_REPORTS => self::LABEL_COLUMNS + [
            'total' => 'Frekuensi laporan',
            Status::REPORT_NEW => 'Baru',
            Status::REPORT_IN_PROGRESS => 'Diproses',
            Status::REPORT_COMPLETED => 'Selesai',
            Status::REPORT_REJECTED => 'Ditolak',
        ],
    ];

    /**
     * @return list<array<string, string|int>> satu baris per fasilitas, kunci sesuai COLUMNS[$type]
     */
    public function recap(
        string $type,
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $facultyId = null,
        ?int $buildingId = null,
    ): array {
        return match ($type) {
            self::TYPE_PLACES => $this->reservationRecap($from, $to, $facultyId, $buildingId, self::PLACE_TYPES, ['reservation_count', 'minutes']),
            self::TYPE_EQUIPMENT => $this->reservationRecap($from, $to, $facultyId, $buildingId, [Status::FACILITY_EQUIPMENT], ['reservation_count', 'quantity', 'unit_minutes']),
            self::TYPE_REPORTS => $this->reportRecap($from, $to, $facultyId, $buildingId),
        };
    }

    /**
     * Hanya reservasi approved yang beririsan dengan periode. Menit dipotong
     * ke batas periode: max(0, min(end, to) - max(start, from)).
     *
     * @param  list<string>  $facilityTypes
     * @param  list<string>  $metrics
     */
    private function reservationRecap(
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $facultyId,
        ?int $buildingId,
        array $facilityTypes,
        array $metrics,
    ): array {
        // ponytail: menit irisan dihitung di PHP karena selisih waktu di SQL
        // berbeda antara MySQL dan SQLite (test). Query tetap satu, baris
        // di-stream lewat cursor; periode dibatasi 1 tahun oleh Form Request.
        $reservations = Reservation::query()
            ->approved()
            ->overlapping($from, $to)
            ->whereHas('facility', $this->facilityScope($facultyId, $buildingId, $facilityTypes))
            ->toBase()
            ->select(['facility_id', 'start_time', 'end_time', 'quantity'])
            ->cursor();

        $totals = [];

        foreach ($reservations as $reservation) {
            $start = Carbon::parse($reservation->start_time)->max($from);
            $end = Carbon::parse($reservation->end_time)->min($to);
            $minutes = max(0, (int) $start->diffInMinutes($end, false));
            $quantity = (int) $reservation->quantity;

            $row = &$totals[$reservation->facility_id];
            $row ??= ['reservation_count' => 0, 'minutes' => 0, 'quantity' => 0, 'unit_minutes' => 0];
            $row['reservation_count']++;
            $row['minutes'] += $minutes;
            $row['quantity'] += $quantity;
            $row['unit_minutes'] += $minutes * $quantity;
            unset($row);
        }

        $metricKeys = array_flip($metrics);

        return $this->withFacilityLabels(array_map(
            fn (array $row): array => array_intersect_key($row, $metricKeys),
            $totals,
        ));
    }

    /**
     * Frekuensi laporan berdasarkan damage_reports.created_at di dalam periode,
     * diagregasi langsung di query per fasilitas dan status.
     */
    private function reportRecap(CarbonInterface $from, CarbonInterface $to, ?int $facultyId, ?int $buildingId): array
    {
        $counts = DamageReport::query()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->whereHas('facility', $this->facilityScope($facultyId, $buildingId))
            ->toBase()
            ->select(['facility_id', 'status', DB::raw('COUNT(*) as aggregate')])
            ->groupBy('facility_id', 'status')
            ->get();

        $totals = [];

        foreach ($counts as $count) {
            $totals[$count->facility_id] ??= ['total' => 0] + array_fill_keys(Status::REPORT_STATUSES, 0);
            $totals[$count->facility_id]['total'] += (int) $count->aggregate;
            $totals[$count->facility_id][$count->status] = (int) $count->aggregate;
        }

        return $this->withFacilityLabels($totals);
    }

    private function facilityScope(?int $facultyId, ?int $buildingId, ?array $types = null): \Closure
    {
        return fn (Builder $query): Builder => $query
            ->when($types, fn (Builder $q) => $q->whereIn('type', $types))
            ->when($facultyId, fn (Builder $q) => $q->where('faculty_id', $facultyId))
            ->when($buildingId, fn (Builder $q) => $q->where('building_id', $buildingId));
    }

    /**
     * Menambahkan label fakultas/gedung/fasilitas dengan satu query eager load.
     *
     * @param  array<int, array<string, int>>  $totals  metrik per facility_id
     */
    private function withFacilityLabels(array $totals): array
    {
        if ($totals === []) {
            return [];
        }

        $rows = Facility::query()
            ->with(['faculty:id,name', 'building:id,name'])
            ->whereKey(array_keys($totals))
            ->get(['id', 'name', 'faculty_id', 'building_id'])
            ->map(fn (Facility $facility): array => [
                'faculty' => $facility->faculty?->name ?? 'Universitas',
                'building' => $facility->building?->name ?? 'Di luar gedung',
                'facility' => $facility->name,
            ] + $totals[$facility->id])
            ->all();

        usort($rows, fn (array $a, array $b): int => [$a['faculty'], $a['building'], $a['facility']]
            <=> [$b['faculty'], $b['building'], $b['facility']]);

        return $rows;
    }
}
