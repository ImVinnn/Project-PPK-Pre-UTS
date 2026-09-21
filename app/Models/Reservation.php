<?php

namespace App\Models;

use App\Support\Status;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'facility_id',
    'quantity',
    'start_time',
    'end_time',
    'purpose',
    'status',
    'processed_by',
    'processed_at',
    'rejection_reason',
    'cancelled_by',
    'cancelled_at',
    'cancel_reason',
])]
class Reservation extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'processed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', Status::RESERVATION_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', Status::RESERVATION_PENDING);
    }

    public function scopeActiveOrApproved(Builder $query): Builder
    {
        return $query->whereIn('status', [Status::RESERVATION_PENDING, Status::RESERVATION_APPROVED]);
    }

    public function scopeOverlapping(Builder $query, CarbonInterface|string $start, CarbonInterface|string $end): Builder
    {
        return $query->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }

    public function isPending(): bool
    {
        return $this->status === Status::RESERVATION_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === Status::RESERVATION_APPROVED;
    }

    public function isCancelled(): bool
    {
        return $this->status === Status::RESERVATION_CANCELLED;
    }

    public function isRejected(): bool
    {
        return $this->status === Status::RESERVATION_REJECTED;
    }

    public function isCancellableByUser(?CarbonInterface $now = null): bool
    {
        if (! in_array($this->status, [Status::RESERVATION_PENDING, Status::RESERVATION_APPROVED], true)) {
            return false;
        }

        $now ??= now();

        return $now->diffInMinutes($this->start_time, false) >= Status::USER_CANCELLATION_NOTICE_MINUTES;
    }

    public function durationMinutes(): int
    {
        return (int) $this->start_time->diffInMinutes($this->end_time);
    }
}
