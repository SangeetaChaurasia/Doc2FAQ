<?php

namespace App\Tests\Service;

use App\Model\DocumentChunk;
use App\Service\ChunkRetrieverService;
use App\Service\FAQGeneratorService;
use PHPUnit\Framework\TestCase;

class FAQGeneratorServiceTest extends TestCase
{
    private FAQGeneratorService $service;
    private array $testChunks;

    protected function setUp(): void
    {
        $retriever = new ChunkRetrieverService();
        $this->service = new FAQGeneratorService($retriever);
        
        $this->testChunks = [
            new DocumentChunk(
                'The company was founded in 2020. It specializes in AI technology.',
                'chunk_test_0',
                'company.txt',
                1,
                'Introduction'
            ),
            new DocumentChunk(
                'Our main product is an AI-powered document processor.',
                'chunk_test_1',
                'company.txt',
                1,
                'Products'
            ),
        ];
    }

    public function testFAQGenerationIncludesSources(): void
    {
        $questions = ['When was the company founded?'];
        $faqs = $this->service->generateFAQs($this->testChunks, $questions);
        
        $this->assertCount(1, $faqs);
        $this->assertNotEmpty($faqs[0]->getSources());
    }

    public function testFAQContainsQuestionAndAnswer(): void
    {
        $question = 'What does the company do?';
        $faq = $this->service->generateSingleFAQ($this->testChunks, $question);
        
        $this->assertNotNull($faq);
        $this->assertEquals($question, $faq->getQuestion());
        $this->assertNotEmpty($faq->getAnswer());
    }

    public function testSourceMetadataIsPreserved(): void
    {
        $faq = $this->service->generateSingleFAQ($this->testChunks, 'What products exist?');
        
        $this->assertNotNull($faq);
        $sources = $faq->getSources();
        $this->assertNotEmpty($sources);
        
        $firstSource = $sources[0];
        $this->assertEquals('company.txt', $firstSource->getDocument());
        $this->assertNotEmpty($firstSource->getChunkId());
    }

    public function testFAQArrayConversion(): void
    {
        $faq = $this->service->generateSingleFAQ($this->testChunks, 'Test question?');
        $array = $faq->toArray();
        
        $this->assertArrayHasKey('question', $array);
        $this->assertArrayHasKey('answer', $array);
        $this->assertArrayHasKey('sources', $array);
        $this->assertIsArray($array['sources']);
    }
}
