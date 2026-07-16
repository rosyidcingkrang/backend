<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Simpan file upload (avatar/logo/poster) ke local disk `storage/` (§2.3 panduan —
 * bebas cara simpan, asal path yang dikembalikan konsisten dengan contoh /storage/...).
 * Validasi tipe & ukuran (jpeg/png, max 2MB) dilakukan di FormRequest terkait
 * (mimes:jpeg,png|max:2048), service ini murni tanggung jawab penyimpanan.
 */
class FileUploadService
{
    /**
     * @param  string  $directory  mis. 'avatars', 'logos', 'posters'
     * @param  string|int  $identifier  dipakai sebagai nama file, mis. user id / band id
     * @return string path publik, mis. /storage/avatars/12.jpg
     */
    public function store(UploadedFile $file, string $directory, string|int $identifier): string
    {
        $filename = "{$identifier}." . $file->getClientOriginalExtension();

        $file->storeAs($directory, $filename, 'public');

        return "/storage/{$directory}/{$filename}";
    }

    /**
     * Hapus file lama sebelum ganti dengan yang baru, supaya tidak menumpuk file
     * orphan di storage. Aman dipanggil walau file lama tidak ada.
     */
    public function deleteIfExists(?string $publicPath): void
    {
        if (! $publicPath) {
            return;
        }

        $relativePath = preg_replace('#^/storage/#', '', $publicPath);

        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
