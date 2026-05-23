<?php

namespace App\Services;

use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class WalletService
{
    public function credit(Wallet $wallet, int $amount, string|WalletTransactionType $type, ?Model $reference = null, ?string $description = null, array $metadata = []): WalletTransaction
    {
        $this->ensurePositiveAmount($amount);

        $lockedWallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();
        $before = $lockedWallet->balance;
        $after = $before + $amount;

        $lockedWallet->update(['balance' => $after]);

        return $this->recordTransaction($lockedWallet, $amount, $type, WalletTransactionDirection::Credit, $before, $after, $reference, $description, $metadata);
    }

    public function debit(Wallet $wallet, int $amount, string|WalletTransactionType $type, ?Model $reference = null, ?string $description = null, array $metadata = []): WalletTransaction
    {
        $this->ensurePositiveAmount($amount);

        $lockedWallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

        if ($lockedWallet->balance < $amount) {
            throw new RuntimeException('Saldo tidak mencukupi.');
        }

        $before = $lockedWallet->balance;
        $after = $before - $amount;

        $lockedWallet->update(['balance' => $after]);

        return $this->recordTransaction($lockedWallet, $amount, $type, WalletTransactionDirection::Debit, $before, $after, $reference, $description, $metadata);
    }

    public function getOrCreateUserWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['owner_type' => 'user', 'owner_id' => $user->id],
            ['balance' => 0, 'currency' => 'IDR', 'status' => 'active']
        );
    }

    public function getOrCreatePlatformWallet(): Wallet
    {
        return Wallet::firstOrCreate(
            ['owner_type' => 'platform', 'owner_id' => 0],
            ['balance' => 0, 'currency' => 'IDR', 'status' => 'active']
        );
    }

    private function recordTransaction(Wallet $wallet, int $amount, string|WalletTransactionType $type, WalletTransactionDirection $direction, int $before, int $after, ?Model $reference, ?string $description, array $metadata): WalletTransaction
    {
        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'owner_type' => $wallet->owner_type,
            'owner_id' => $wallet->owner_id,
            'type' => $type instanceof WalletTransactionType ? $type : WalletTransactionType::from($type),
            'direction' => $direction,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'status' => WalletTransactionStatus::Success,
            'reference_type' => $reference ? $reference->getMorphClass() : null,
            'reference_id' => $reference?->getKey(),
            'description' => $description,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    private function ensurePositiveAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }
    }
}
