<?php

use App\Enums\InvitationStatus;

test('nilai dan label status undangan konsisten', function () {
    expect(InvitationStatus::values())->toBe(['pending', 'accepted', 'expired', 'revoked'])
        ->and(InvitationStatus::Pending->label())->toBe('Menunggu')
        ->and(InvitationStatus::Accepted->label())->toBe('Diterima')
        ->and(InvitationStatus::Expired->label())->toBe('Kedaluwarsa')
        ->and(InvitationStatus::Revoked->label())->toBe('Dibatalkan');
});
