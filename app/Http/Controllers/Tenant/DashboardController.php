<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $monthTransactions = Transaction::query()
            ->inMonth($now)
            ->get(['type', 'amount', 'transaction_date']);

        $incomeThisMonth = $this->sumOfType($monthTransactions, [TransactionType::Income]);
        $expenseThisMonth = $this->sumOfType($monthTransactions, [TransactionType::Expense, TransactionType::BillPayment]);

        $breakdown = Transaction::query()
            ->ofType(TransactionType::Expense)
            ->inMonth($now)
            ->sumPerCategory()
            ->map(fn (Transaction $row): array => $this->breakdownRow($row))
            ->values()
            ->all();

        $accounts = Account::query()
            ->active()
            ->orderedForListing()
            ->get(['id', 'name', 'type', 'balance', 'is_archived']);

        $recent = Transaction::query()
            ->with(['account', 'category'])
            ->latestFirst()
            ->limit(4)
            ->get()
            ->map(static fn (Transaction $transaction): array => [
                'id' => $transaction->getKey(),
                'type' => $transaction->type->value,
                'amount' => (string) $transaction->amount,
                'description' => $transaction->description,
                'date' => $transaction->transaction_date->toDateString(),
                'account' => $transaction->account->name,
                'category' => $transaction->category?->name,
                'icon' => $transaction->category?->icon,
            ])
            ->all();

        $now->locale('id');

        return Inertia::render('Dashboard', [
            'summary' => [
                'total_balance' => $this->money($accounts->sum('balance')),
                'income_this_month' => $incomeThisMonth,
                'expense_this_month' => $expenseThisMonth,
                'period_label' => $now->translatedFormat('F Y'),
                'greeting_name' => strtok((string) $request->user()->name, ' '),
                'cashflow' => $this->weeklyBuckets($monthTransactions, $monthStart, $monthEnd),
            ],
            'breakdown' => $breakdown,
            'accounts' => $accounts,
            'recent' => $recent,
        ]);
    }

    /**
     * Nominal mentah desimal:2 untuk frontend, apa pun bentuk keluar agregat
     * SUM (int bila bulat). Tepat untuk decimal(15,2): kelipatan sen di
     * bawah 2^53 selalu muat persis di float.
     */
    private function money(int|float|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    /**
     * Satu iris donat: nama + ikon kategori dengan totalnya. Total berasal
     * dari alias agregat SUM, jadi dibaca lewat getAttribute.
     *
     * @return array{category: string, icon: string|null, total: string}
     */
    private function breakdownRow(Transaction $row): array
    {
        $categoryName = $row->category_id === null ? null : $row->category->name;

        return [
            'category' => $categoryName ?? 'Tanpa kategori',
            'icon' => $row->category_id === null ? null : $row->category->icon,
            'total' => $this->money($row->getAttribute('total') ?? 0),
        ];
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  list<TransactionType>  $types
     */
    private function sumOfType(iterable $transactions, array $types): string
    {
        $total = '0.00';

        foreach ($transactions as $transaction) {
            if (in_array($transaction->type, $types, true)) {
                $total = bcadd($total, $transaction->amount, 2);
            }
        }

        return $total;
    }

    /**
     * Ember bulan berjalan per pekan (1-7, 8-14, ...). Satu query diambil
     * di pemanggil, pengelompokan murni koleksi dengan perbandingan string
     * Y-m-d (aman untuk Carbon mutable maupun immutable).
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return list<array{label: string, income: string, expense: string}>
     */
    private function weeklyBuckets(iterable $transactions, Carbon $monthStart, Carbon $monthEnd): array
    {
        $prefix = $monthStart->format('Y-m');
        $lastDay = $monthEnd->day;
        $buckets = [];
        $week = 1;

        for ($day = 1; $day <= $lastDay; $day += 7, $week++) {
            $from = sprintf('%s-%02d', $prefix, $day);
            $to = sprintf('%s-%02d', $prefix, min($day + 6, $lastDay));

            $income = '0';
            $expense = '0';

            foreach ($transactions as $transaction) {
                $date = $transaction->transaction_date->toDateString();

                if ($date < $from || $date > $to) {
                    continue;
                }

                if ($transaction->type === TransactionType::Income) {
                    $income = bcadd($income, $transaction->amount, 2);
                } else {
                    $expense = bcadd($expense, $transaction->amount, 2);
                }
            }

            $buckets[] = ['label' => "Mgg {$week}", 'income' => $income, 'expense' => $expense];
        }

        return $buckets;
    }
}
