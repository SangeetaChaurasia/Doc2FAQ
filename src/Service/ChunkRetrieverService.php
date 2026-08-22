<?php

namespace App\Service;

use App\Model\DocumentChunk;
use App\Exception\VectorStoreException;

class ChunkRetrieverService
{
    private ?VectorStore $vectorStore = null;
    private float $similarityThreshold;
    private bool $useSemanticRetrieval;

    public function __construct(
        float $similarityThreshold = 0.0,
        bool $useSemanticRetrieval = true
    ) {
        $this->similarityThreshold = $similarityThreshold;
        $this->useSemanticRetrieval = $useSemanticRetrieval;
    }

    public function setVectorStore(VectorStore $vectorStore): void
    {
        $this->vectorStore = $vectorStore;
    }

    public function findRelevantChunks(
        array $allChunks, 
        string $query, 
        int $topK = 3,
        ?float $similarityThreshold = null
    ): array {
        if (empty($query)) {
            return [];
        }

        if (empty($allChunks)) {
            return [];
        }

        $threshold = $similarityThreshold ?? $this->similarityThreshold;

        // Use semantic retrieval if vector store is available
        if ($this->useSemanticRetrieval && $this->vectorStore !== null) {
            try {
                return $this->vectorStore->search($query, $topK, $threshold);
            } catch (VectorStoreException $e) {
                // Fall back to keyword-based retrieval on error
                return $this->keywordBasedRetrieval($allChunks, $query, $topK);
            }
        }

        // Fall back to keyword-based retrieval
        return $this->keywordBasedRetrieval($allChunks, $query, $topK);
    }

    private function keywordBasedRetrieval(array $allChunks, string $query, int $topK): array
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
