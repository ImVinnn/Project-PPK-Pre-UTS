<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['faculty_id', 'building_id', 'name', 'type', 'capacity', 'location_detail', 'description', 'status'])]
class Facility extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function roomDetail(): HasOne
    {
        return $this->hasOne(RoomDetail::class);
    }

    public function equipmentDetail(): HasOne
    {
        return $this->hasOne(EquipmentDetail::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function damageReports(): HasMany
    {
        return $this->hasMany('App\Models\DamageReport');
    }

    public function isRoom(): bool
    {
        return in_array($this->type, [
            \App\Support\Status::FACILITY_CLASSROOM,
            \App\Support\Status::FACILITY_HALL,
            \App\Support\Status::FACILITY_LABORATORY,
        ], true);
    }

    public function isEquipment(): bool
    {
        return $this->type === \App\Support\Status::FACILITY_EQUIPMENT;
    }

    public function isField(): bool
    {
        return $this->type === \App\Support\Status::FACILITY_FIELD;
    }

    public function isActive(): bool
    {
        return $this->status === \App\Support\Status::FACILITY_ACTIVE;
    }
}
