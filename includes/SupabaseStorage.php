<?php
/**
 * SupabaseStorage — uploads/deletes files using PHP's built-in cURL.
 * No Composer or Guzzle required.
 */
class SupabaseStorage {
    private $url;
    private $key;
    private $bucket;

    public function __construct() {
        $this->url    = rtrim($_ENV['SUPABASE_URL'], '/');
        $this->key    = $_ENV['SUPABASE_KEY'];
        $this->bucket = 'avatars';
    }

    /**
     * Upload a file to Supabase Storage.
     *
     * @param string $filePath  Path to the temp file (e.g. $_FILES['avatar']['tmp_name'])
     * @param string $filename  Destination filename inside the bucket
     * @return bool
     */
    public function upload(string $filePath, string $filename): bool {
        $endpoint = $this->url . '/storage/v1/object/' . $this->bucket . '/' . $filename;
        $mimeType = function_exists('mime_content_type')
            ? mime_content_type($filePath)
            : $this->guessMimeType($filename);
        $fileData = file_get_contents($filePath);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fileData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->key,
                'apikey: '              . $this->key,
                'Content-Type: '        . $mimeType,
                'x-upsert: true',       // overwrite if same filename exists
            ],
        ]);

        $response   = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        if ($curlError) {
            error_log("SupabaseStorage upload cURL error: $curlError");
            return false;
        }

        // Supabase returns 200 on success
        if ($statusCode !== 200) {
            error_log("SupabaseStorage upload failed ($statusCode): $response");
            return false;
        }

        return true;
    }

    /**
     * Delete a file from Supabase Storage.
     *
     * @param string $filename  Filename inside the bucket
     * @return bool
     */
    public function delete(string $filename): bool {
        $endpoint = $this->url . '/storage/v1/object/' . $this->bucket . '/' . $filename;

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->key,
                'apikey: '              . $this->key,
            ],
        ]);

        $response   = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        if ($curlError) {
            error_log("SupabaseStorage delete cURL error: $curlError");
            return false;
        }

        return $statusCode === 200;
    }

    /**
     * Guess MIME type from filename extension (fallback when fileinfo is disabled).
     */
    private function guessMimeType(string $filename): string {
        $map = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
        ];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return $map[$ext] ?? 'application/octet-stream';
    }

    /**
     * Get the public URL for a file in the bucket.
     *
     * @param string $filename
     * @return string|null
     */
    public function getUrl(string $filename): ?string {
        if (empty($filename)) return null;
        return $this->url . '/storage/v1/object/public/' . $this->bucket . '/' . $filename;
    }
}
