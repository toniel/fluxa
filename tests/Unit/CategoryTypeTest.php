<?php

use App\Enums\CategoryType;

test('nilai dan label tipe kategori konsisten', function () {
    expect(CategoryType::values())->toBe(['income', 'expense'])
        ->and(CategoryType::Income->value)->toBe('income')
        ->and(CategoryType::Expense->value)->toBe('expense')
        ->and(CategoryType::Income->label())->toBe('Pemasukan')
        ->and(CategoryType::Expense->label())->toBe('Pengeluaran');
});
