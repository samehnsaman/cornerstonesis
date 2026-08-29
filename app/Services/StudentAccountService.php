<?php

namespace App\Services;

use App\Models\LedgerTransaction;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentAccountService
{
    public function __construct(private readonly AuditService $audit) {}

    public function post(array $attributes, array $entries): LedgerTransaction
    {
        $debits = BigDecimal::zero();
        $credits = BigDecimal::zero();

        foreach ($entries as $entry) {
            $debits = $debits->plus((string) ($entry['debit'] ?? '0'));
            $credits = $credits->plus((string) ($entry['credit'] ?? '0'));
        }

        if (! $debits->isEqualTo($credits) || $debits->isZero()) {
            throw ValidationException::withMessages(['entries' => __('finance.unbalanced')]);
        }

        return DB::transaction(function () use ($attributes, $entries): LedgerTransaction {
            $transaction = LedgerTransaction::create([
                ...$attributes,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
                'status' => 'posted',
            ]);

            foreach ($entries as $entry) {
                $transaction->entries()->create([
                    'account_code' => $entry['account_code'],
                    'debit' => $entry['debit'] ?? 0,
                    'credit' => $entry['credit'] ?? 0,
                ]);
            }

            $this->audit->record('ledger.posted', $transaction, after: [
                'reference' => $transaction->reference,
                'currency' => $transaction->currency,
                'entries' => $entries,
            ]);

            return $transaction->load('entries');
        });
    }

    public function reverse(LedgerTransaction $original, string $reason): LedgerTransaction
    {
        if ($original->reversal_of_id || $original->status !== 'posted') {
            throw ValidationException::withMessages(['transaction' => __('finance.not_reversible')]);
        }

        $original->loadMissing('entries');

        return $this->post([
            'person_id' => $original->person_id,
            'academic_period_id' => $original->academic_period_id,
            'reversal_of_id' => $original->id,
            'type' => 'reversal',
            'reference' => 'REV-'.now()->format('YmdHis').'-'.substr($original->id, 0, 8),
            'currency' => $original->currency,
            'description' => $reason,
            'effective_on' => today(),
            'metadata' => ['original_reference' => $original->reference],
        ], $original->entries->map(fn ($entry): array => [
            'account_code' => $entry->account_code,
            'debit' => $entry->credit,
            'credit' => $entry->debit,
        ])->all());
    }
}
