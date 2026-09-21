<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamageReport extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * Only fields the user submits via the form.
     * user_id and status are set explicitly by the controller — never from request.
     */
    protected $fillable = [
        'facility_id',
        'category',
        'other_category',
        'description',
        'photo_path',
    ];

    /**
     * Get the user who submitted this report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the facility this report refers to.
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Human-readable category label.
     */
    public function categoryLabel(): string
    {
        return match ($this->category) {
            'kerusakan_fisik' => 'Kerusakan Fisik',
            'kelistrikan'     => 'Kelistrikan',
            'kebersihan'      => 'Kebersihan',
            'perlengkapan'    => 'Perlengkapan',
            'lainnya'         => 'Lainnya',
            default           => $this->category,
        };
    }

    /**
     * Human-readable status label.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'baru'      => 'Baru',
            'diproses'  => 'Diproses',
            'selesai'   => 'Selesai',
            'ditolak'   => 'Ditolak',
            default     => $this->status,
        };
    }

    /**
     * Bootstrap badge CSS class for the status.
     */
    public function statusBadge(): string
    {
        return match ($this->status) {
            'baru'      => 'bg-warning text-dark',
            'diproses'  => 'bg-info text-dark',
            'selesai'   => 'bg-success',
            'ditolak'   => 'bg-danger',
            default     => 'bg-secondary',
        };
    }
}

