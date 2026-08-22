<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FAQControllerTest extends WebTestCase
{
    public function testGenerateFAQWithJsonInput(): void
    {
        $client = static::createClient();
        
        $payload = [
            'text' => 'This is a test document. It contains information about testing. Testing is important for quality.',
            'filename' => 'test.txt',
            'questions' => ['What is this about?'],
        ];
        
        $client->request(
            'POST',
            '/api/faq/generate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        
        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('faqs', $response);
        $this->assertNotEmpty($response['faqs']);
    }

    public function testFAQResponseIncludesSources(): void
    {
        $client = static::createClient();
        
        $payload = [
            'text' => 'Sample document content for FAQ generation testing.',
            'questions' => ['What is in the document?'],
        ];
        
        $client->request(
            'POST',
            '/api/faq/generate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        
        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('faqs', $response);
        $this->assertNotEmpty($response['faqs']);
        
        $firstFaq = $response['faqs'][0];
        $this->assertArrayHasKey('question', $firstFaq);
        $this->assertArrayHasKey('answer', $firstFaq);
        $this->assertArrayHasKey('sources', $firstFaq);
        $this->assertIsArray($firstFaq['sources']);
        
        if (!empty($firstFaq['sources'])) {
            $firstSource = $firstFaq['sources'][0];
            $this->assertArrayHasKey('document', $firstSource);
            $this->assertArrayHasKey('chunk_id', $firstSource);
        }
    }

    public function testMissingTextReturnsError(): void
    {
        $client = static::createClient();
        
        $client->request(
            'POST',
            '/api/faq/generate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['questions' => ['Test?']])
        );
        
        $this->assertResponseStatusCodeSame(400);
    }
}
