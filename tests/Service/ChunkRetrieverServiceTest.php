<?php

namespace App\Tests\Service;

use App\Model\DocumentChunk;
use App\Service\ChunkRetrieverService;
use App\Service\VectorStore;
use App\Service\SimpleEmbeddingService;
use PHPUnit\Framework\TestCase;

class ChunkRetrieverServiceTest extends TestCase
{
    private ChunkRetrieverService $service;
    private VectorStore $vectorStore;

    protected function setUp(): void
    {
        $embeddingService = new SimpleEmbeddingService();
        $this->vectorStore = new VectorStore($embeddingService);
        $this->service = new ChunkRetrieverService(0.0, false);
        $this->service->setVectorStore($this->vectorStore);
    }

    public function testRetrievesRelevantChunks(): void
    {
        $chunks = [
            new DocumentChunk('Information about dogs and pets', 'c1', 'doc.txt'),
            new DocumentChunk('Details about cars and vehicles', 'c2', 'doc.txt'),
            new DocumentChunk('More about dogs and training', 'c3', 'doc.txt'),
        ];
        
        $relevant = $this->service->findRelevantChunks($chunks, 'dogs', 2);
        
        $this->assertCount(2, $relevant);
    }

    public function testReturnsTopKChunks(): void
    {
        $chunks = [];
        for ($i = 0; $i < 10; $i++) {
            $chunks[] = new DocumentChunk("Content $i", "chunk_$i", 'test.txt');
        }
        
        $relevant = $this->service->findRelevantChunks($chunks, 'Content', 3);
        
        $this->assertCount(3, $relevant);
    }

    public function testHandlesEmptyChunkList(): void
    {
        $relevant = $this->service->findRelevantChunks([], 'query', 5);
        
        $this->assertEmpty($relevant);
    }

    public function testRanksChunksByRelevance(): void
    {
        $chunks = [
            new DocumentChunk('The quick brown fox', 'c1', 'doc.txt'),
            new DocumentChunk('Quick quick quick', 'c2', 'doc.txt'),
        ];
        
        $relevant = $this->service->findRelevantChunks($chunks, 'quick', 1);
        
        $this->assertEquals('c2', $relevant[0]->getChunkId());
    }

    public function testSemanticRetrievalWithVectorStore(): void
    {
        $service = new ChunkRetrieverService(0.0, true);
        $service->setVectorStore($this->vectorStore);
        
        $chunks = [
            new DocumentChunk('Python programming tutorial', 'c1', 'doc.txt'),
            new DocumentChunk('Java programming guide', 'c2', 'doc.txt'),
            new DocumentChunk('Cooking pasta recipe', 'c3', 'doc.txt'),
        ];
        
        $this->vectorStore->indexChunks($chunks);
        
        $relevant = $service->findRelevantChunks($chunks, 'programming', 2);
        
        $this->assertCount(2, $relevant);
    }

    public function testRespectsSimilarityThreshold(): void
    {
        $service = new ChunkRetrieverService(0.3, true);
        $service->setVectorStore($this->vectorStore);
        
        $chunks = [
            new DocumentChunk('Machine learning algorithms', 'c1', 'doc.txt'),
            new DocumentChunk('Deep learning networks', 'c2', 'doc.txt'),
            new DocumentChunk('Cooking techniques', 'c3', 'doc.txt'),
        ];
        
        $this->vectorStore->indexChunks($chunks);
        
        $relevant = $service->findRelevantChunks($chunks, 'learning', 10, 0.3);
        
        // Should filter out irrelevant chunks based on threshold
        $this->assertLessThanOrEqual(2, count($relevant));
    }

    public function testFallsBackToKeywordRetrievalOnError(): void
    {
        // Don't set vector store - should fall back to keyword-based
        $service = new ChunkRetrieverService(0.0, true);
        
        $chunks = [
            new DocumentChunk('Information about dogs', 'c1', 'doc.txt'),
            new DocumentChunk('Information about cats', 'c2', 'doc.txt'),
        ];
        
        $relevant = $service->findRelevantChunks($chunks, 'dogs', 1);
        
        $this->assertCount(1, $relevant);
        $this->assertEquals('c1', $relevant[0]->getChunkId());
    }

    public function testHandlesEmptyQuery(): void
    {
        $chunks = [
            new DocumentChunk('Content', 'c1', 'doc.txt'),
        ];
        
        $relevant = $this->service->findRelevantChunks($chunks, '', 5);
        
        $this->assertEmpty($relevant);
    }
}
