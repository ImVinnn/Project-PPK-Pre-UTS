<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
