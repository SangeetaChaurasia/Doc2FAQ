<?php

namespace App\Tests\Service;

use App\Service\DocumentChunkerService;
use PHPUnit\Framework\TestCase;

class DocumentChunkerServiceTest extends TestCase
{
    private DocumentChunkerService $service;

    protected function setUp(): void
    {
        $this->service = new DocumentChunkerService();
    }

    public function testChunksPreserveMetadata(): void
    {
        $text = str_repeat('This is test content. ', 100);
        $filename = 'test.txt';
        $metadata = ['author' => 'Test Author'];
        
        $chunks = $this->service->createChunks($text, $filename, $metadata);
        
        $this->assertNotEmpty($chunks);
        
        foreach ($chunks as $chunk) {
            $this->assertEquals($filename, $chunk->getSourceDocument());
            $this->assertNotEmpty($chunk->getChunkId());
            $this->assertEquals($metadata, $chunk->getMetadata());
        }
    }

    public function testChunkIdsAreUnique(): void
    {
        $text = str_repeat('Some text for chunking. ', 100);
        $chunks = $this->service->createChunks($text, 'doc.txt');
        
        $chunkIds = array_map(fn($c) => $c->getChunkId(), $chunks);
        $uniqueIds = array_unique($chunkIds);
        
        $this->assertCount(count($chunkIds), $uniqueIds);
    }

    public function testHeadingDetection(): void
    {
        $text = "INTRODUCTION:\n\nThis is the introduction text.\n\nMain Content\n\nThis is the main content.";
        $chunks = $this->service->createChunks($text, 'doc.txt');
        
        $hasHeading = false;
        foreach ($chunks as $chunk) {
            if ($chunk->getSection() !== null) {
                $hasHeading = true;
                break;
            }
        }
        
        $this->assertTrue($hasHeading);
    }

    public function testEmptyDocumentHandling(): void
    {
        $chunks = $this->service->createChunks('', 'empty.txt');
        
        $this->assertEmpty($chunks);
    }

    public function testSmallDocumentCreatesOneChunk(): void
    {
        $text = 'This is a small document with limited content.';
        $chunks = $this->service->createChunks($text, 'small.txt');
        
        $this->assertCount(1, $chunks);
        $this->assertStringContainsString('small document', $chunks[0]->getContent());
    }

    public function testSourceReferenceGeneration(): void
    {
        $chunks = $this->service->createChunks('Test content', 'ref.txt');
        $sourceRef = $chunks[0]->getSourceReference();
        
        $this->assertEquals('ref.txt', $sourceRef->getDocument());
        $this->assertNotEmpty($sourceRef->getChunkId());
    }
}
