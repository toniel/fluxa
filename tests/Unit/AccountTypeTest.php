<?php

use App\Enums\AccountType;

test('nilai dan label tipe kantong konsisten', function () {
    expect(AccountType::values())->toBe(['cash', 'bank', 'ewallet', 'credit_card', 'paylater', 'other'])
        ->and(AccountType::Cash->value)->toBe('cash')
        ->and(AccountType::Bank->value)->toBe('bank')
        ->and(AccountType::Ewallet->value)->toBe('ewallet')
        ->and(AccountType::CreditCard->value)->toBe('credit_card')
        ->and(AccountType::Paylater->value)->toBe('paylater')
        ->and(AccountType::Other->value)->toBe('other')
        ->and(AccountType::Cash->label())->toBe('Tunai')
        ->and(AccountType::Bank->label())->toBe('Rekening bank')
        ->and(AccountType::Ewallet->label())->toBe('E-wallet')
        ->and(AccountType::CreditCard->label())->toBe('Kartu kredit')
        ->and(AccountType::Paylater->label())->toBe('Paylater')
        ->and(AccountType::Other->label())->toBe('Lainnya');
});

test('arah saldo aset dan liabilitas berlawanan', function () {
    expect(AccountType::Cash->balanceDirection())->toBe(1)
        ->and(AccountType::Bank->balanceDirection())->toBe(1)
        ->and(AccountType::Ewallet->balanceDirection())->toBe(1)
        ->and(AccountType::Other->balanceDirection())->toBe(1)
        ->and(AccountType::CreditCard->balanceDirection())->toBe(-1)
        ->and(AccountType::Paylater->balanceDirection())->toBe(-1)
        ->and(AccountType::CreditCard->isLiability())->toBeTrue()
        ->and(AccountType::Paylater->isLiability())->toBeTrue()
        ->and(AccountType::Cash->isLiability())->toBeFalse();
});
