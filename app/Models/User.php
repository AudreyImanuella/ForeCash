<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const PERAN = ['mahasiswa', 'orangtua', 'admin'];

    /** Kolom yang boleh diisi lewat User::create([...]). */
    protected $fillable = [
        'name', 'email', 'password', 'role', 'avatar', 'batas_harian', 'kode_hubung', 'kode_teman',
    ];

    /** Kolom yang tidak ikut saat model diubah ke array/JSON. */
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',        // otomatis di-hash (bcrypt) saat disimpan
            'batas_harian' => 'decimal:2',
        ];
    }

    // ---------- Peran ----------

    public function isMahasiswa(): bool { return $this->role === 'mahasiswa'; }
    public function isOrangtua(): bool  { return $this->role === 'orangtua'; }
    public function isAdmin(): bool     { return $this->role === 'admin'; }

    /** Halaman awal sesuai peran, dipakai setelah login. */
    public function halamanAwal(): string
    {
        return match ($this->role) {
            'orangtua' => route('pantau'),
            'admin' => route('admin.index'),
            default => route('beranda'),
        };
    }

    public function namaDepan(): string
    {
        return Str::before(trim($this->name), ' ') ?: $this->name;
    }

    /** Kode teman 6 karakter yang belum dipakai user lain. */
    public static function buatKodeTeman(): string
    {
        do {
            $kode = Str::upper(Str::random(6));
        } while (self::where('kode_teman', $kode)->exists());

        return $kode;
    }

    // ---------- Relasi ----------

    public function wallets(): HasMany { return $this->hasMany(Wallet::class); }
    public function transactions(): HasMany { return $this->hasMany(Transaction::class); }
    public function categories(): HasMany { return $this->hasMany(Category::class); }
    public function incomeSchedules(): HasMany { return $this->hasMany(IncomeSchedule::class); }
    public function debts(): HasMany { return $this->hasMany(Debt::class); }
    public function anggaran(): HasMany { return $this->hasMany(Anggaran::class); }
    public function targetTabungan(): HasMany { return $this->hasMany(TargetTabungan::class); }
    public function rencana(): HasMany { return $this->hasMany(RencanaPengeluaran::class); }
    public function pets(): HasMany { return $this->hasMany(UserPet::class); }

    /** Teman ForeCash (tabel teman, disimpan dua arah). */
    public function teman(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teman', 'user_id', 'teman_id')->withTimestamps();
    }

    /** Untuk akun orang tua: anak yang dipantau. */
    public function anak(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_links', 'orangtua_id', 'anak_id')->withTimestamps();
    }

    /** Untuk akun mahasiswa: orang tua yang memantau. */
    public function orangtua(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_links', 'anak_id', 'orangtua_id')->withTimestamps();
    }
}
