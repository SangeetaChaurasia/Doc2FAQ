<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentLoaderService
{
    public function loadFromUploadedFile(UploadedFile $file): array
    {
        $content = file_get_contents($file->getPathname());
        $originalName = $file->getClientOriginalName();
        
        return [
            'content' => $content,
            'filename' => $originalName,
            'metadata' => $this->extractMetadata($file, $content),
        ];
    }

    public function loadFromText(string $text, string $filename = 'text_input.txt'): array
    {
        return [
            'content' => $text,
            'filename' => $filename,
            'metadata' => [
                'type' => 'text',
                'length' => strlen($text),
            ],
        ];
    }

    private function extractMetadata(UploadedFile $file, string $content): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
        ];

        // For PDF files, attempt to extract page count
        if ($file->getMimeType() === 'application/pdf') {
            $metadata['type'] = 'pdf';
            $pageCount = $this->extractPdfPageCount($content);
            if ($pageCount !== null) {
                $metadata['page_count'] = $pageCount;
            }
        } else {
            $metadata['type'] = 'text';
        }

        return $metadata;
    }

    private function extractPdfPageCount(string $content): ?int
    {
        // Basic PDF page count extraction - matches /Count N pattern
        if (preg_match('/\/Count\s+(\d+)/', $content, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }
}
