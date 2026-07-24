<?php

namespace App\Models;

use Carbon\CarbonInterval;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $user_id
 * @property string $nip
 * @property string $nama
 * @property Carbon|null $tanggal_lahir
 * @property Carbon $tanggal_bergabung
 * @property string|null $status_kawin
 * @property int $jumlah_tanggungan
 * @property string $status_kepegawaian
 * @property Carbon|null $tanggal_mulai_probation
 * @property Carbon|null $tanggal_akhir_probation
 * @property string $status_aktif
 * @property Carbon|null $tanggal_resign
 * @property string|null $alasan_resign
 * @property int|null $unit_id
 * @property int|null $atasan_id
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'nip',
        'nama',
        'tanggal_lahir',
        'tanggal_bergabung',
        'status_kawin',
        'jumlah_tanggungan',
        'status_kepegawaian',
        'tanggal_mulai_probation',
        'tanggal_akhir_probation',
        'status_aktif',
        'tanggal_resign',
        'alasan_resign',
        'unit_id',
        'atasan_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_bergabung' => 'date',
            'tanggal_mulai_probation' => 'date',
            'tanggal_akhir_probation' => 'date',
            'tanggal_resign' => 'date',
        ];
    }

    // Data sensitif (status kepegawaian, unit, atasan, resign, dst.) dicatat di Audit Log
    // terpusat (docs/SRS.md FR-18.1) — bukan semua kolom, supaya log tidak berisik oleh
    // perubahan yang tidak relevan.
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status_kepegawaian',
                'status_aktif',
                'unit_id',
                'atasan_id',
                'tanggal_resign',
                'alasan_resign',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Atasan langsung — approver pertama untuk izin/lembur (bukan selalu sama dengan
     * kepala unit, lihat catatan resolusi atasan di docs/DESIGN.md §3.1).
     *
     * @return BelongsTo<Employee, $this>
     */
    public function atasan(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'atasan_id');
    }

    /** @return HasMany<Employee, $this> */
    public function bawahan(): HasMany
    {
        return $this->hasMany(Employee::class, 'atasan_id');
    }

    /** @return HasOne<Unit, $this> */
    public function unitDipimpin(): HasOne
    {
        return $this->hasOne(Unit::class, 'kepala_unit_id');
    }

    /**
     * "Masa Kerja" — field hitungan dari tanggal_bergabung, bukan kolom tersimpan. Dipakai
     * juga oleh notifikasi hari jadi kerja (SRS FR-12.2). Lihat docs/DESIGN.md §3.1.
     *
     * Sengaja berupa method biasa, bukan Eloquent attribute cast — nilainya murni turunan,
     * tidak pernah disimpan/di-mass-assign.
     */
    public function masaKerja(): CarbonInterval
    {
        return $this->tanggal_bergabung->diff(now());
    }
}
