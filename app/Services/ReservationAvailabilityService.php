<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\Reservation;
use App\Support\Status;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ReservationAvailabilityService
{
    /**
     * Generate the 26 fixed daily time slots (07:00 - 20:00, 30 min each).
     *
     * @return array<int, array{index: int, start_time: string, end_time: string}>
     */
    public function generateSlotDefinitions(): array
    {
        $slots = [];
        $start = Carbon::createFromTimeString(Status::OPERATING_HOUR_START);
        $end = Carbon::createFromTimeString(Status::OPERATING_HOUR_END);
        $index = 1;

        while ($start->lt($end)) {
            $slotEnd = $start->copy()->addMinutes(Status::SLOT_MINUTES);
            $slots[] = [
                'index' => $index++,
                'start_time' => $start->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
            ];
            $start = $slotEnd;
        }

        return $slots;
    }

    /**
     * Get availability of all 26 slots for a facility on a specific date.
     * Guaranteed never to leak applicant details or purpose to public callers.
     *
     * @param  Facility  $facility
     * @param  CarbonInterface|string  $date
     * @return array<int, array<string, mixed>>
     */
    public function getDailyAvailability(Facility $facility, CarbonInterface|string $date): array
    {
        $targetDate = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $dateString = $targetDate->toDateString();
        $slotDefs = $this->generateSlotDefinitions();

        $facility->loadMissing(['roomDetail', 'equipmentDetail']);

        $isFacilityActive = $facility->status === Status::FACILITY_ACTIVE;
        $isEquipment = $facility->isEquipment();

        // Query only approved reservations for this facility on target date.
        // We strictly avoid selecting user_id, purpose, or joining users for privacy.
        $approvedReservations = Reservation::query()
            ->select(['id', 'facility_id', 'start_time', 'end_time', 'quantity'])
            ->where('facility_id', $facility->id)
            ->where('status', Status::RESERVATION_APPROVED)
            ->whereDate('start_time', $dateString)
            ->get();

        $usableStock = 0;
        if ($isEquipment && $facility->equipmentDetail) {
            $equipmentTotal = $facility->equipmentDetail->stock_total;
            $equipmentUnavailable = $facility->equipmentDetail->stock_unavailable;
            $usableStock = max(0, $equipmentTotal - $equipmentUnavailable);
        }

        $result = [];

        foreach ($slotDefs as $def) {
            $slotStart = Carbon::parse("{$dateString} {$def['start_time']}:00");
            $slotEnd = Carbon::parse("{$dateString} {$def['end_time']}:00");

            if (! $isFacilityActive) {
                $result[] = [
                    'index' => $def['index'],
                    'start_time' => $def['start_time'],
                    'end_time' => $def['end_time'],
                    'start_datetime' => $slotStart->toDateTimeString(),
                    'end_datetime' => $slotEnd->toDateTimeString(),
                    'is_available' => false,
                    'reason' => $facility->status === Status::FACILITY_MAINTENANCE ? 'Dalam Perbaikan' : 'Nonaktif',
                    'available_quantity' => 0,
                    'stock_total' => $isEquipment ? $usableStock : 1,
                ];
                continue;
            }

            // Find approved reservations overlapping this slot
            $overlapping = $approvedReservations->filter(function ($res) use ($slotStart, $slotEnd) {
                return $slotStart->lt($res->end_time) && $slotEnd->gt($res->start_time);
            });

            if ($isEquipment) {
                $bookedQuantity = (int) $overlapping->sum('quantity');
                $availableQuantity = max(0, $usableStock - $bookedQuantity);
                $isAvailable = $availableQuantity > 0;

                $result[] = [
                    'index' => $def['index'],
                    'start_time' => $def['start_time'],
                    'end_time' => $def['end_time'],
                    'start_datetime' => $slotStart->toDateTimeString(),
                    'end_datetime' => $slotEnd->toDateTimeString(),
                    'is_available' => $isAvailable,
                    'reason' => $isAvailable ? 'Tersedia' : 'Stok Habis',
                    'available_quantity' => $availableQuantity,
                    'stock_total' => $usableStock,
                ];
            } else {
                $isAvailable = $overlapping->isEmpty();

                $result[] = [
                    'index' => $def['index'],
                    'start_time' => $def['start_time'],
                    'end_time' => $def['end_time'],
                    'start_datetime' => $slotStart->toDateTimeString(),
                    'end_datetime' => $slotEnd->toDateTimeString(),
                    'is_available' => $isAvailable,
                    'reason' => $isAvailable ? 'Tersedia' : 'Terpakai',
                    'available_quantity' => $isAvailable ? 1 : 0,
                    'stock_total' => 1,
                ];
            }
        }

        return $result;
    }

    /**
     * Check if a proposed time range is available for a facility.
     *
     * @param  Facility  $facility
     * @param  CarbonInterface|string  $start
     * @param  CarbonInterface|string  $end
     * @param  int  $requestedQuantity
     * @return bool
     */
    public function isRangeAvailable(
        Facility $facility,
        CarbonInterface|string $start,
        CarbonInterface|string $end,
        int $requestedQuantity = 1
    ): bool {
        if ($facility->status !== Status::FACILITY_ACTIVE) {
            return false;
        }

        $startDate = $start instanceof CarbonInterface ? $start : Carbon::parse($start);
        $endDate = $end instanceof CarbonInterface ? $end : Carbon::parse($end);

        $facility->loadMissing('equipmentDetail');

        $overlappingApproved = Reservation::query()
            ->select(['id', 'quantity', 'start_time', 'end_time'])
            ->where('facility_id', $facility->id)
            ->where('status', Status::RESERVATION_APPROVED)
            ->overlapping($startDate, $endDate)
            ->get();

        if ($facility->isEquipment()) {
            if (! $facility->equipmentDetail) {
                return false;
            }

            $usableStock = max(0, $facility->equipmentDetail->stock_total - $facility->equipmentDetail->stock_unavailable);

            // Check across all 30-minute segments within [startDate, endDate]
            $cursor = $startDate->copy();
            while ($cursor->lt($endDate)) {
                $segEnd = $cursor->copy()->addMinutes(Status::SLOT_MINUTES);
                $segBooked = (int) $overlappingApproved->filter(function ($res) use ($cursor, $segEnd) {
                    return $cursor->lt($res->end_time) && $segEnd->gt($res->start_time);
                })->sum('quantity');

                if (($usableStock - $segBooked) < $requestedQuantity) {
                    return false;
                }

                $cursor = $segEnd;
            }

            return true;
        }

        return $overlappingApproved->isEmpty();
    }

    /**
     * Calculate contiguous chain duration in minutes (two-way search)
     * for reservations of the same user on the same facility on the same day.
     * Pending reservations are included. Cancelled and rejected are excluded.
     *
     * @param  int  $userId
     * @param  int  $facilityId
     * @param  CarbonInterface  $newStart
     * @param  CarbonInterface  $newEnd
     * @param  int|null  $ignoreReservationId
     * @return int
     */
    public function calculateContiguousChainMinutes(
        int $userId,
        int $facilityId,
        CarbonInterface $newStart,
        CarbonInterface $newEnd,
        ?int $ignoreReservationId = null
    ): int {
        $dateString = $newStart->toDateString();

        $existing = Reservation::query()
            ->where('user_id', $userId)
            ->where('facility_id', $facilityId)
            ->whereIn('status', [Status::RESERVATION_PENDING, Status::RESERVATION_APPROVED])
            ->whereDate('start_time', $dateString)
            ->when($ignoreReservationId, fn ($q) => $q->where('id', '!=', $ignoreReservationId))
            ->get();

        $chainStart = $newStart->copy();
        $chainEnd = $newEnd->copy();

        // 1. Traverse backwards: find adjacent reservation connecting to current chainStart
        $foundEarlier = true;
        while ($foundEarlier) {
            $foundEarlier = false;
            foreach ($existing as $res) {
                if ($res->end_time->equalTo($chainStart)) {
                    $chainStart = $res->start_time->copy();
                    $foundEarlier = true;
                    break;
                }
            }
        }

        // 2. Traverse forwards: find adjacent reservation connecting to current chainEnd
        $foundLater = true;
        while ($foundLater) {
            $foundLater = false;
            foreach ($existing as $res) {
                if ($res->start_time->equalTo($chainEnd)) {
                    $chainEnd = $res->end_time->copy();
                    $foundLater = true;
                    break;
                }
            }
        }

        return (int) $chainStart->diffInMinutes($chainEnd);
    }
}
