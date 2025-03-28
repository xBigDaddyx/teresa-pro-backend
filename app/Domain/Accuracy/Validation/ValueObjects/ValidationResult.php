<?php

namespace App\Domain\Accuracy\Validation\ValueObjects;

/**
 * Kelas Value Object yang mewakili hasil dari proses validasi.
 *
 * Kelas ini digunakan untuk merepresentasikan hasil validasi, termasuk status keberhasilan,
 * pesan validasi, aturan yang digunakan, dan detail tambahan.
 */
class ValidationResult
{
    /**
     * Menandakan apakah validasi berhasil.
     *
     * @var bool
     */
    private bool $isValid;

    /**
     * Pesan validasi atau alasan kegagalan.
     *
     * @var string
     */
    private string $message;

    /**
     * Aturan akurasi yang diterapkan dalam validasi.
     *
     * @var AccuracyRule|null
     */
    private ?AccuracyRule $rule;

    /**
     * Detail tambahan hasil validasi (opsional).
     *
     * @var array
     */
    private array $details;

    /**
     * Membuat instance baru dari ValidationResult.
     *
     * @param bool $isValid Status keberhasilan validasi
     * @param string $message Pesan validasi
     * @param AccuracyRule|null $rule Aturan akurasi yang digunakan
     * @param array $details Detail tambahan hasil validasi
     */
    public function __construct(bool $isValid, string $message = '', ?AccuracyRule $rule = null, array $details = [])
    {
        $this->isValid = $isValid;
        $this->message = $message;
        $this->rule = $rule;
        $this->details = $details;
    }

    /**
     * Memeriksa apakah validasi berhasil.
     *
     * @return bool True jika validasi berhasil, false jika gagal
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Mendapatkan pesan validasi.
     *
     * @return string Pesan validasi atau alasan kegagalan
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Mendapatkan aturan akurasi yang diterapkan dalam validasi.
     *
     * @return AccuracyRule|null Aturan akurasi atau null jika tidak ada
     */
    public function getRule(): ?AccuracyRule
    {
        return $this->rule;
    }

    /**
     * Mendapatkan detail tambahan hasil validasi.
     *
     * @return array Detail tambahan dalam bentuk array
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Membuat hasil validasi yang berhasil.
     *
     * Metode pabrik (factory method) untuk membuat hasil validasi yang berhasil
     * dengan pesan, aturan, dan detail yang dapat dikonfigurasi.
     *
     * @param string $message Pesan keberhasilan validasi
     * @param AccuracyRule|null $rule Aturan akurasi yang digunakan
     * @param array $details Detail tambahan hasil validasi
     *
     * @return self Instance baru ValidationResult yang menunjukkan keberhasilan
     */
    public static function success(string $message = 'Validation successful', ?AccuracyRule $rule = null, array $details = []): self
    {
        return new self(true, $message, $rule, $details);
    }

    /**
     * Membuat hasil validasi yang gagal.
     *
     * Metode pabrik (factory method) untuk membuat hasil validasi yang gagal
     * dengan pesan kegagalan, aturan, dan detail yang dapat dikonfigurasi.
     *
     * @param string $message Pesan kegagalan validasi
     * @param AccuracyRule|null $rule Aturan akurasi yang digunakan
     * @param array $details Detail tambahan hasil validasi
     *
     * @return self Instance baru ValidationResult yang menunjukkan kegagalan
     */
    public static function failure(string $message = 'Validation failed', ?AccuracyRule $rule = null, array $details = []): self
    {
        return new self(false, $message, $rule, $details);
    }
}
