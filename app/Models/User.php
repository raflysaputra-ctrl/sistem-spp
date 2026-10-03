<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_TU = 'tu';

    public const ROLE_KEPALA_SEKOLAH = 'kepala_sekolah';

    public const ROLE_SISWA = 'siswa';

    public const INTERNAL_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_TU,
        self::ROLE_KEPALA_SEKOLAH,
    ];

    protected $table = 'users';

    protected $primaryKey = 'id_user';

    protected $fillable = [
        'nama',
        'username',
        'password',
        'role',
        'id_siswa',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isInternal(): bool
    {
        return in_array($this->role, self::INTERNAL_ROLES, true);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_TU => 'Tata Usaha',
            self::ROLE_KEPALA_SEKOLAH => 'Kepala Sekolah',
            self::ROLE_SISWA => 'Siswa',
            default => $this->role,
        };
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'id_user', 'id_user');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function pembatalanPembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'dibatalkan_oleh', 'id_user');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id_user');
    }

    public function accountLogs(): HasMany
    {
        return $this->hasMany(AccountLog::class, 'user_id', 'id_user');
    }
}
