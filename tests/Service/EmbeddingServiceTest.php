<?php

namespace App\Tests\Service;

use App\Service\SimpleEmbeddingService;
use App\Exception\EmbeddingException;
use PHPUnit\Framework\TestCase;

class EmbeddingServiceTest extends TestCase
{
    private SimpleEmbeddingService $service;

    protected function setUp(): void
    {
        $this->service = new SimpleEmbeddingService();
    }

    public function testGeneratesEmbedding(): void
    {
        $embedding = $this->service->generateEmbedding('This is a test document');
        
        $this->assertIsArray($embedding);
        $this->assertCount(128, $embedding);
        $this->assertContainsOnly('float', $embedding);
    }

    public function testEmbeddingIsNormalized(): void
    {
        $embedding = $this->service->generateEmbedding('test content');
        
        $magnitude = sqrt(array_sum(array_map(fn($v) => $v * $v, $embedding)));
        
        $this->assertEqualsWithDelta(1.0, $magnitude, 0.0001);
    }

    public function testSimilarTextsProduceSimilarEmbeddings(): void
    {
        $emb1 = $this->service->generateEmbedding('The quick brown fox jumps');
        $emb2 = $this->service->generateEmbedding('The quick brown fox runs');
        $emb3 = $this->service->generateEmbedding('Cars and vehicles information');
        
        $sim12 = $this->cosineSimilarity($emb1, $emb2);
        $sim13 = $this->cosineSimilarity($emb1, $emb3);
        
        $this->assertGreaterThan($sim13, $sim12);
    }

    public function testThrowsExceptionForEmptyText(): void
    {
        $this->expectException(EmbeddingException::class);
        $this->expectExceptionMessage('Cannot generate embedding for empty text');
        
        $this->service->generateEmbedding('');
    }

    private function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0.0;
        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
        }
        
        // Vectors are already normalized, so just return dot product
        return $dotProduct;
    }
}
