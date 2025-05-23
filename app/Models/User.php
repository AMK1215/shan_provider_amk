<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\HasWalletFloat;
use App\Models\Admin\Role;
use App\Models\Admin\Permission;
use App\Enums\UserType;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable implements Wallet
{
    use HasApiTokens, HasFactory, Notifiable, HasWalletFloat;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_name',
        'name',
        'profile',
        'email',
        'password',
        'game_provider_password',
        'profile',
        'phone',
        'balance',
        'max_score',
        'agent_id',
        'status',
        'type',
        'is_changed_password',
        'referral_code',
        'agent_logo',
        'site_name',
        'site_link',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasRole($role)
    {
        return $this->roles->contains('title', $role);
    }
    // A user can have children (e.g., Admin has many Agents, or Agent has many Players)
    public function children()
    {
        return $this->hasMany(User::class, 'agent_id');
    }
    public function poneWinePlayer()
    {
        return $this->hasMany(PoneWinePlayerBet::class);
    }

    public static function adminUser()
    {
        return self::where('type', UserType::SystemWallet)->first();
    }

     /**
     * Get the game provider password for this user.
     *
     * @return string|null
     */
    public function getGameProviderPassword(): ?string
    {
        if ($this->game_provider_password) {
            try {
                return Crypt::decryptString($this->game_provider_password);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Log the error or handle it as appropriate (e.g., return null to regenerate)
                \Log::error('Failed to decrypt game_provider_password for user ' . $this->id, ['error' => $e->getMessage()]);
                return null;
            }
        }
        return null;
    }

    /**
     * Set the game provider password for this user.
     *
     * @param string $password
     * @return void
     */
    public function setGameProviderPassword(string $password): void
    {
        $this->game_provider_password = Crypt::encryptString($password);
        $this->save(); // Save the user model to persist the password
    }
    
}
