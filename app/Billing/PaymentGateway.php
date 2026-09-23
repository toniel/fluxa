<?php

declare(strict_types=1);

namespace App\Billing;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Kontrak payment gateway. Ganti provider = tulis satu adapter baru +
 * daftarkan namanya di config/billing.php, tanpa menyentuh inti billing
 * (action, controller, halaman, webhook handler).
 */
interface PaymentGateway
{
    /**
     * Nama kanonis, disimpan di kolom subscriptions.gateway supaya webhook
     * dan riwayat tahu baris ini milik provider mana.
     */
    public function name(): string;

    /**
     * Daftarkan tenant sebagai customer di provider. Mengembalikan id
     * eksternal customer untuk disimpan.
     */
    public function createCustomer(Tenant $tenant, User $owner): string;

    /**
     * Buat langganan berulang untuk paket. Return URL dipakai provider
     * yang mewajibkan redirect balik (Xendit tidak, Midtrans ya).
     */
    public function createRecurring(Plan $plan, string $externalCustomerId, string $returnUrl): GatewayCheckout;

    /**
     * Hentikan langganan berulang di sisi provider.
     */
    public function cancelRecurring(string $externalSubscriptionId): void;

    /**
     * Verifikasi keaslian webhook (token/signature khas tiap provider).
     * Wajib dipanggil sebelum parseEvent.
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Terjemahkan payload webhook menjadi event ternormalisasi.
     */
    public function parseEvent(Request $request): GatewayEvent;

    /**
     * Id langganan eksternal dari payload webhook, untuk mencocokkan baris
     * subscriptions. Null bila payload bukan tentang satu langganan.
     */
    public function externalSubscriptionId(Request $request): ?string;
}
