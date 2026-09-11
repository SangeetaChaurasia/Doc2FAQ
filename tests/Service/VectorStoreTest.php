<?php

namespace App\Tests\Service;

use App\Model\DocumentChunk;
use App\Service\VectorStore;
use App\Service\SimpleEmbeddingService;
use App\Exception\VectorStoreException;
use PHPUnit\Framework\TestCase;

class VectorStoreTest extends TestCase
{
    private VectorStore $vectorStore;
    private SimpleEmbeddingService $embeddingService;

    protected function setUp(): void
    {
        $this->embeddingService = new SimpleEmbeddingService();
        $this->vectorStore = new VectorStore($this->embeddingService);
    }

    public function testIndexesChunk(): void
    {
        $chunk = new DocumentChunk('Test content', 'chunk1', 'doc.txt');
        
        $this->vectorStore->indexChunk($chunk);
        
        $results = $this->vectorStore->search('Test', 1);
        $this->assertCount(1, $results);
    }

    public function testIndexesMultipleChunks(): void
    {
        $chunks = [
            new DocumentChunk('Content about dogs', 'c1', 'doc.txt'),
            new DocumentChunk('Content about cats', 'c2', 'doc.txt'),
            new DocumentChunk('Content about birds', 'c3', 'doc.txt'),
        ];
        
        $this->vectorStore->indexChunks($chunks);
        
        $results = $this->vectorStore->search('animals', 3);
        $this->assertCount(3, $results);
    }

    public function testSearchReturnsRelevantChunks(): void
    {
        $chunks = [
            new DocumentChunk('Information about dogs and training', 'c1', 'doc.txt'),
            new DocumentChunk('Cars and vehicles maintenance', 'c2', 'doc.txt'),
            new DocumentChunk('Dogs behavior and health', 'c3', 'doc.txt'),
        ];
        
        $this->vectorStore->indexChunks($chunks);
        
        $results = $this->vectorStore->search('dogs', 2);
        
        $this->assertCount(2, $results);
        $this->assertContains($results[0]->getChunkId(), ['c1', 'c3']);
        $this->assertContains($results[1]->getChunkId(), ['c1', 'c3']);
    }

    public function testSearchRespectsTopK(): void
    {
        $chunks = [];
        for ($i = 0; $i < 10; $i++) {
            $chunks[] = new DocumentChunk("Content item $i", "chunk_$i", 'test.txt');
        }
        
        $this->vectorStore->indexChunks($chunks);
        
        $results = $this->vectorStore->search('Content', 3);
        
        $this->assertCount(3, $results);
    }

    public function testSearchRespectsSimilarityThreshold(): void
    {
        $chunks = [
            new DocumentChunk('Python programming language', 'c1', 'doc.txt'),
            new DocumentChunk('Java programming language', 'c2', 'doc.txt'),
            new DocumentChunk('Cooking recipes for dinner', 'c3', 'doc.txt'),
        ];
        
        $this->vectorStore->indexChunks($chunks);
        
        // High threshold should return fewer results
        $results = $this->vectorStore->search('programming', 10, 0.5);
        
        // Should get programming-related chunks, not cooking
        $this->assertLessThanOrEqual(2, count($results));
    }

    public function testSearchReturnsEmptyForEmptyIndex(): void
    {
        $results = $this->vectorStore->search('query', 5);
        
        $this->assertEmpty($results);
    }

    public function testThrowsExceptionForEmptyQuery(): void
    {
        $this->expectException(VectorStoreException::class);
        $this->expectExceptionMessage('Cannot search with empty query');
        
        $this->vectorStore->search('', 5);
    }

    public function testThrowsExceptionForInvalidTopK(): void
    {
        $this->expectException(VectorStoreException::class);
        $this->expectExceptionMessage('topK must be at least 1');
        
        $this->vectorStore->search('query', 0);
    }

    public function testThrowsExceptionForEmptyChunkContent(): void
    {
        $this->expectException(VectorStoreException::class);
        $this->expectExceptionMessage('Cannot index chunk with empty content');
        
        $chunk = new DocumentChunk('', 'chunk1', 'doc.txt');
        $this->vectorStore->indexChunk($chunk);
    }

    public function testThrowsExceptionForEmptyChunkList(): void
    {
        $this->expectException(VectorStoreException::class);
        $this->expectExceptionMessage('Cannot index empty chunk list');
        
        $this->vectorStore->indexChunks([]);
    }

    public function testClearRemovesAllChunks(): void
    {
        $chunks = [
            new DocumentChunk('Content 1', 'c1', 'doc.txt'),
            new DocumentChunk('Content 2', 'c2', 'doc.txt'),
        ];
        
        $this->vectorStore->indexChunks($chunks);
        $this->vectorStore->clear();
        
        $results = $this->vectorStore->search('Content', 10);
        $this->assertEmpty($results);
    }

    public function testPreservesChunkMetadata(): void
    {
        $chunk = new DocumentChunk('Test', 'c1', 'doc.txt', 5, 'Section A');
        $this->vectorStore->indexChunk($chunk);
        
        $results = $this->vectorStore->search('Test', 1);
        $this->assertEquals(5, $results[0]->getPage());
        $this->assertEquals('Section A', $results[0]->getSection());
    }
}
