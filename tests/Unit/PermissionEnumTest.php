<?php

use App\Enums\PermissionEnum;

test('label dan nilai setiap permission terdefinisi', function () {
    foreach (PermissionEnum::cases() as $case) {
        expect($case->value)->not->toBeEmpty()
            ->and($case->label())->toBeString();
    }

    expect(PermissionEnum::values())->toHaveCount(count(PermissionEnum::cases()));
    expect(PermissionEnum::values())->toContain('categories.manage');
});
