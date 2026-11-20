<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function getBalanceAttribute(?string $value): string
    {
        return number_format((float) ($value ?? 0), 9, '.', '');
    }

    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function hasSufficientBalance(string|float $requiredAmount): bool
    {
        $required = (string) $requiredAmount;
        return bccomp($this->balance ?? '0', $required, 9) >= 0;
    }

    public function deductCredits(string|float $amount, ?Generation $generation = null, string $description = ''): CreditTransaction
    {
        return DB::transaction(function () use ($amount, $generation, $description) {
            $user = self::where('id', $this->id)->lockForUpdate()->first();
            $amountStr = number_format((float) $amount, 9, '.', '');
            $newBalance = bcsub($user->balance, $amountStr, 9);
            
            // Allow floor at 0 or minimal negative if slight variance, but never drop significantly below 0
            $user->balance = $newBalance;
            $user->save();

            $this->balance = $newBalance;

            return CreditTransaction::create([
                'user_id' => $user->id,
                'generation_id' => $generation?->id,
                'type' => 'generation_charge',
                'amount' => '-' . $amountStr,
                'balance_after' => $newBalance,
                'description' => $description ?: 'Image generation charge',
            ]);
        });
    }

    public function addCredits(string|float $amount, string $description = 'Credit purchase', string $type = 'top_up'): CreditTransaction
    {
        return DB::transaction(function () use ($amount, $description, $type) {
            $user = self::where('id', $this->id)->lockForUpdate()->first();
            $amountStr = number_format((float) $amount, 9, '.', '');
            $newBalance = bcadd($user->balance, $amountStr, 9);

            $user->balance = $newBalance;
            $user->save();

            $this->balance = $newBalance;

            return CreditTransaction::create([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amountStr,
                'balance_after' => $newBalance,
                'description' => $description,
            ]);
        });
    }
}
