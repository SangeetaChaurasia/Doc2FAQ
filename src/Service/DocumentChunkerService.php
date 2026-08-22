<?php

namespace App\Service;

use App\Model\DocumentChunk;

class DocumentChunkerService
{
    public function createChunks(string $documentText, string $sourceFileName, array $additionalMeta = []): array
    {
        $chunkMaxLength = 500;
        $chunkOverlap = 50;
        
        $outputChunks = [];
        $textSegments = explode("\n\n", $documentText);
        
        $workingBuffer = '';
        $chunkNumber = 0;
        $currentPageNumber = 1;
        $currentHeading = null;
        
        foreach ($textSegments as $segment) {
            $segment = trim($segment);
            if ($segment === '') continue;
            
            $foundHeading = $this->isHeading($segment);
            if ($foundHeading) {
                $currentHeading = $foundHeading;
            }
            
            $newLength = strlen($workingBuffer) + strlen($segment) + 2;
            
            if ($newLength > $chunkMaxLength && $workingBuffer !== '') {
                $chunkId = 'chunk_' . md5($sourceFileName) . '_' . $chunkNumber;
                $outputChunks[] = new DocumentChunk(
                    trim($workingBuffer), $chunkId, $sourceFileName, 
                    $currentPageNumber, $currentHeading, $additionalMeta
                );
                
                $keepText = substr($workingBuffer, -$chunkOverlap);
                $workingBuffer = $keepText . "\n\n" . $segment;
                $chunkNumber++;
            } else {
                $workingBuffer .= ($workingBuffer ? "\n\n" : '') . $segment;
            }
        }
        
        if (trim($workingBuffer) !== '') {
            $chunkId = 'chunk_' . md5($sourceFileName) . '_' . $chunkNumber;
            $outputChunks[] = new DocumentChunk(
                trim($workingBuffer), $chunkId, $sourceFileName, 
                $currentPageNumber, $currentHeading, $additionalMeta
            );
        }
        
        return $outputChunks;
    }

    private function isHeading(string $text): ?string
    {
        $firstLine = strtok($text, "\n");
        if (strlen($firstLine) < 80 && (str_ends_with($firstLine, ':') || 
            (strlen($firstLine) < 40 && ctype_upper(str_replace(' ', '', $firstLine))))) {
            return rtrim($firstLine, ':');
        }
        return null;
    }
}
