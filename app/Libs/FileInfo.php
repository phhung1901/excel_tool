<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-02
 * Time: 10:22
 */

namespace App\Libs;

use Illuminate\Http\UploadedFile;

class FileInfo
{
    /** Loại mã hóa hash */
    const ALGO = 'sha256';

    /**
     * Get hash of a file
     *
     * @param  string|resource|array|UploadedFile  $document  can be a path to file, resource or an Uploaded file
     * @return string
     *
     * @throws \Exception if argument type it's not supported
     */
    public static function hashDocument($document)
    {
        if (is_string($document)) {
            return hash_file(self::ALGO, $document);
        } elseif ($document instanceof UploadedFile) {
            if ($document->getError()) {
                throw new \Exception('Error: '.$document->getErrorMessage());
            }

            return hash_file(self::ALGO, $document->getRealPath());
        } elseif (is_resource($document)) {
            rewind($document);

            return hash(self::ALGO, stream_get_contents($document));
        } else {
            throw new \Exception('Không hỗ trợ lấy hash của loại document đầu vào');
        }
    }

    public static function hashDocumentContent(string $document_content)
    {
        return hash(self::ALGO, $document_content);
    }
}
