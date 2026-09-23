<?php

declare(strict_types=1);

namespace App\Billing;

use RuntimeException;

/**
 * Kegagalan memanggil provider (kunci kosong, respons tak terduga).
 * Bukan kesalahan validasi input user, jadi tidak memakai ValidationException.
 */
class GatewayException extends RuntimeException {}
