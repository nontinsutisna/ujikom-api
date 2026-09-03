<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'password', 'role', 'no_hp', 'alamat',
    'foto_profile'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verifled_at' => 'datatime',
            'password' => 'hashed', //laravel otomatis meng-hash tekss apapun yang masuk ke properti password!
        ];
    }

    public function peminjaman(): HasMany {
        return $this->hasMany(Peminjaman::class);
    }

    public function logAktivitas(): HasMany {
        return $this->hasMany(LogAktivitas::class);
    }

    public function scopeTersedia($query)
    {
        return $query->where('stok', '>', 0)->where('status_kondisi','Baik');
    }
}