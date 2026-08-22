<?php

namespace App\Service;

use App\Model\DocumentChunk;

class ChunkRetrieverService
{
    public function findRelevantChunks(array $allChunks, string $query, int $topK = 3): array
    {
        $scoredChunks = [];
        
        foreach ($allChunks as $chunk) {
            $relevanceScore = $this->calculateRelevance($chunk->getContent(), $query);
            $scoredChunks[] = [
                'chunk' => $chunk,
                'score' => $relevanceScore,
            ];
        }
        
        usort($scoredChunks, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        $topChunks = array_slice($scoredChunks, 0, $topK);
        
        return array_map(function($item) {
            return $item['chunk'];
        }, $topChunks);
    }

    private function calculateRelevance(string $chunkContent, string $query): float
    {
        $queryLower = strtolower($query);
        $contentLower = strtolower($chunkContent);
        $queryWords = preg_split('/\s+/', $queryLower);
        
        return array_reduce($queryWords, function($score, $word) use ($contentLower) {
            return $score + substr_count($contentLower, $word);
        }, 0);
    }
}
