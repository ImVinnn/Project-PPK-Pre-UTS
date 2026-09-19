<?php

namespace App\Support;

final class Status
{
    public const string ROLE_USER = 'pengguna';

    public const string ROLE_OFFICER = 'petugas';

    public const string ROLE_ADMIN = 'admin';

    public const array ROLES = [self::ROLE_USER, self::ROLE_OFFICER, self::ROLE_ADMIN];

    public const string ACCOUNT_PENDING = 'pending';

    public const string ACCOUNT_ACTIVE = 'active';

    public const string ACCOUNT_REJECTED = 'rejected';

    public const string ACCOUNT_INACTIVE = 'inactive';

    public const array ACCOUNT_STATUSES = [
        self::ACCOUNT_PENDING,
        self::ACCOUNT_ACTIVE,
        self::ACCOUNT_REJECTED,
        self::ACCOUNT_INACTIVE,
    ];

    public const string FACILITY_CLASSROOM = 'ruang_kelas';

    public const string FACILITY_HALL = 'aula';

    public const string FACILITY_LABORATORY = 'laboratorium';

    public const string FACILITY_EQUIPMENT = 'alat';

    public const string FACILITY_FIELD = 'lapangan';

    public const array FACILITY_TYPES = [
        self::FACILITY_CLASSROOM,
        self::FACILITY_HALL,
        self::FACILITY_LABORATORY,
        self::FACILITY_EQUIPMENT,
        self::FACILITY_FIELD,
    ];

    public const string FACILITY_ACTIVE = 'active';

    public const string FACILITY_MAINTENANCE = 'maintenance';

    public const string FACILITY_INACTIVE = 'inactive';

    public const array FACILITY_STATUSES = [
        self::FACILITY_ACTIVE,
        self::FACILITY_MAINTENANCE,
        self::FACILITY_INACTIVE,
    ];

    public const string RESERVATION_PENDING = 'pending';

    public const string RESERVATION_APPROVED = 'approved';

    public const string RESERVATION_REJECTED = 'rejected';

    public const string RESERVATION_CANCELLED = 'cancelled';

    public const array RESERVATION_STATUSES = [
        self::RESERVATION_PENDING,
        self::RESERVATION_APPROVED,
        self::RESERVATION_REJECTED,
        self::RESERVATION_CANCELLED,
    ];

    public const string REPORT_NEW = 'baru';

    public const string REPORT_IN_PROGRESS = 'diproses';

    public const string REPORT_COMPLETED = 'selesai';

    public const string REPORT_REJECTED = 'ditolak';

    public const array REPORT_STATUSES = [
        self::REPORT_NEW,
        self::REPORT_IN_PROGRESS,
        self::REPORT_COMPLETED,
        self::REPORT_REJECTED,
    ];

    public const string REPORT_PHYSICAL_DAMAGE = 'kerusakan_fisik';

    public const string REPORT_ELECTRICAL = 'kelistrikan';

    public const string REPORT_CLEANLINESS = 'kebersihan';

    public const string REPORT_EQUIPMENT = 'perlengkapan';

    public const string REPORT_OTHER = 'lainnya';

    public const array REPORT_CATEGORIES = [
        self::REPORT_PHYSICAL_DAMAGE,
        self::REPORT_ELECTRICAL,
        self::REPORT_CLEANLINESS,
        self::REPORT_EQUIPMENT,
        self::REPORT_OTHER,
    ];

    public const string OPERATING_HOUR_START = '07:00';

    public const string OPERATING_HOUR_END = '20:00';

    public const int SLOT_MINUTES = 30;

    public const int MAX_ADVANCE_DAYS = 90;

    public const int USER_CANCELLATION_NOTICE_MINUTES = 120;

    public const array MAX_DURATION_MINUTES = [
        self::FACILITY_CLASSROOM => 180,
        self::FACILITY_HALL => 360,
        self::FACILITY_LABORATORY => 240,
        self::FACILITY_FIELD => 120,
        self::FACILITY_EQUIPMENT => 780,
    ];
}
