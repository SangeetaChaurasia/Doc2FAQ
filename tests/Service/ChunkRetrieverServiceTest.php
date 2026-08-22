<?php

namespace App\Tests\Service;

use App\Model\DocumentChunk;
use App\Service\ChunkRetrieverService;
use PHPUnit\Framework\TestCase;

class ChunkRetrieverServiceTest extends TestCase
{
    private ChunkRetrieverService $service;

    protected function setUp(): void
    {
        $this->service = new ChunkRetrieverService();
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
}
