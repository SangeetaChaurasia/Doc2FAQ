<?php

namespace App\Service;

use App\Model\DocumentChunk;
use App\Exception\VectorStoreException;
use App\Exception\EmbeddingException;

class VectorStore
{
    private array $vectors = [];
    private array $chunkMap = [];
    private EmbeddingServiceInterface $embeddingService;

    public function __construct(EmbeddingServiceInterface $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    /**
     * Index a document chunk by generating and storing its embedding.
     */
    public function indexChunk(DocumentChunk $chunk): void
    {
        if (empty($chunk->getContent())) {
            throw new VectorStoreException('Cannot index chunk with empty content');
        }

        try {
            $embedding = $this->embeddingService->generateEmbedding($chunk->getContent());
            $chunkId = $chunk->getChunkId();
            
            $this->vectors[$chunkId] = $embedding;
            $this->chunkMap[$chunkId] = $chunk;
        } catch (EmbeddingException $e) {
            throw new VectorStoreException('Failed to index chunk: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Index multiple document chunks.
     */
    public function indexChunks(array $chunks): void
    {
        if (empty($chunks)) {
            throw new VectorStoreException('Cannot index empty chunk list');
        }

        foreach ($chunks as $chunk) {
            if (!$chunk instanceof DocumentChunk) {
                throw new VectorStoreException('All items must be DocumentChunk instances');
            }
            $this->indexChunk($chunk);
        }
    }

    /**
     * Search for similar chunks based on query.
     */
    public function search(
        string $query, 
        int $topK = 3, 
        float $similarityThreshold = 0.0
    ): array {
        if (empty($query)) {
            throw new VectorStoreException('Cannot search with empty query');
        }

        if ($topK < 1) {
            throw new VectorStoreException('topK must be at least 1');
        }

        if (empty($this->vectors)) {
            return [];
        }

        try {
            $queryEmbedding = $this->embeddingService->generateEmbedding($query);
        } catch (EmbeddingException $e) {
            throw new VectorStoreException('Failed to generate query embedding: ' . $e->getMessage(), 0, $e);
        }

        $similarities = [];
        foreach ($this->vectors as $chunkId => $embedding) {
            $similarity = $this->cosineSimilarity($queryEmbedding, $embedding);
            
            if ($similarity >= $similarityThreshold) {
                $similarities[] = [
                    'chunk_id' => $chunkId,
                    'similarity' => $similarity,
                ];
            }
        }

        usort($similarities, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        
        $topResults = array_slice($similarities, 0, $topK);
        
        $chunks = [];
        foreach ($topResults as $result) {
            $chunks[] = $this->chunkMap[$result['chunk_id']];
        }
        
        return $chunks;
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    private function cosineSimilarity(array $vec1, array $vec2): float
    {
        if (count($vec1) !== count($vec2)) {
            throw new VectorStoreException('Vectors must have the same dimension');
        }

        $dotProduct = 0.0;
        $mag1 = 0.0;
        $mag2 = 0.0;

        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $mag1 += $vec1[$i] * $vec1[$i];
            $mag2 += $vec2[$i] * $vec2[$i];
        }

        $magnitude = sqrt($mag1) * sqrt($mag2);
        
        if ($magnitude == 0) {
            return 0.0;
        }

        return $dotProduct / $magnitude;
    }

    public function clear(): void
    {
        $this->vectors = [];
        $this->chunkMap = [];
    }
}
