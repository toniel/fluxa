<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\CreditCardDetail;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Menentukan periode tagihan dan jatuh tempo dari tanggal transaksi.
 *
 * Contoh: siklus 16-15, transaksi 19 September masuk statement Oktober
 * (periodEnd 15 Oktober), dueDate = periodEnd + offset.
 */
final class CreditCardBillingResolver
{
    /**
     * @return array{period_start: CarbonInterface, period_end: CarbonInterface, due_date: CarbonInterface}
     */
    public function resolvePeriod(CreditCardDetail $detail, CarbonInterface $date): array
    {
        // Bekukan ke immutable dulu: pemanggil mengirim atribut model yang
        // tidak boleh berubah, dan Carbon mutable akan bergeser di tempat.
        $day = CarbonImmutable::parse($date->toDateString());
        $endDay = $detail->billing_cycle_end_day;

        // Hari siklus dijepit ke panjang bulan supaya end_day 29-31 tidak
        // melompat ke bulan berikutnya di Februari.
        $thisMonthEnd = min($endDay, $day->daysInMonth);

        if ($day->day > $thisMonthEnd) {
            $nextMonth = $day->addMonthNoOverflow()->startOfMonth();
            $periodEnd = $nextMonth->day(min($endDay, $nextMonth->daysInMonth));
        } else {
            $periodEnd = $day->day($thisMonthEnd);
        }

        $periodStart = $periodEnd->subMonthNoOverflow()->addDay();
        $dueDate = $periodEnd->addDays($detail->payment_due_offset_days);

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'due_date' => $dueDate,
        ];
    }
}
