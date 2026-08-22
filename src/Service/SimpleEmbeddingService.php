<?php

namespace App\Service;

use App\Exception\EmbeddingException;

class SimpleEmbeddingService implements EmbeddingServiceInterface
{
    private const EMBEDDING_SIZE = 128;

    public function generateEmbedding(string $text): array
    {
        if (empty($text)) {
            throw new EmbeddingException('Cannot generate embedding for empty text');
        }

        try {
            $words = $this->extractWords($text);
            
            if (empty($words)) {
                throw new EmbeddingException('No valid tokens found in text');
            }

            $vector = array_fill(0, self::EMBEDDING_SIZE, 0.0);
            
            foreach ($words as $word) {
                $idx1 = abs(crc32($word)) % self::EMBEDDING_SIZE;
                $idx2 = abs(crc32(strrev($word))) % self::EMBEDDING_SIZE;
                
                $vector[$idx1] += 1.0;
                $vector[$idx2] += 0.5;
            }
            
            return $this->normalizeVector($vector);
        } catch (EmbeddingException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new EmbeddingException('Failed to generate embedding: ' . $e->getMessage(), 0, $e);
        }
    }

    private function extractWords(string $text): array
    {
        $lowerText = strtolower($text);
        $words = preg_split('/\W+/', $lowerText, -1, PREG_SPLIT_NO_EMPTY);
        
        $commonWords = [
            'the', 'is', 'at', 'which', 'on', 'a', 'an', 'as', 'are', 
            'was', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 
            'does', 'did', 'will', 'would', 'could', 'should', 'may', 
            'might', 'must', 'can', 'to', 'of', 'in', 'for', 'with', 
            'by', 'from', 'up', 'about', 'into', 'through', 'during',
            'before', 'after', 'above', 'below', 'between', 'under',
            'again', 'further', 'then', 'once', 'here', 'there', 'when',
            'where', 'why', 'how', 'all', 'both', 'each', 'few', 'more',
            'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only',
            'own', 'same', 'so', 'than', 'too', 'very'
        ];
        
        return array_filter($words, function($word) use ($commonWords) {
            return strlen($word) > 2 && !in_array($word, $commonWords);
        });
    }

    private function normalizeVector(array $vector): array
    {
        $squareSum = 0.0;
        foreach ($vector as $val) {
            $squareSum += $val * $val;
        }
        $magnitude = sqrt($squareSum);
        
        if ($magnitude == 0) {
            return $vector;
        }
        return array_map(fn($v) => $v / $magnitude, $vector);
    }
}
