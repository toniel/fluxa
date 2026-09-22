<?php

use App\Enums\AccountType;

test('nilai dan label tipe kantong konsisten', function () {
    expect(AccountType::values())->toBe(['cash', 'bank', 'ewallet', 'other'])
        ->and(AccountType::Cash->value)->toBe('cash')
        ->and(AccountType::Bank->value)->toBe('bank')
        ->and(AccountType::Ewallet->value)->toBe('ewallet')
        ->and(AccountType::Other->value)->toBe('other')
        ->and(AccountType::Cash->label())->toBe('Tunai')
        ->and(AccountType::Bank->label())->toBe('Rekening bank')
        ->and(AccountType::Ewallet->label())->toBe('E-wallet')
        ->and(AccountType::Other->label())->toBe('Lainnya');
});
