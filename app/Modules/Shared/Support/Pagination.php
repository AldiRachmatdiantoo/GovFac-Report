<?php

namespace App\Modules\Shared\Support;

/**
 * Konvensi paginasi API (lihat API contract): bawaan 20 item per halaman,
 * klien boleh meminta jumlah lain lewat `per_page`, maksimal 50.
 *
 * Pakai konstanta ini di FormRequest dan Repository agar semua daftar seragam.
 */
final class Pagination
{
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 50;

    /**
     * Aturan validasi untuk parameter query `per_page`.
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        return ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE];
    }
}
