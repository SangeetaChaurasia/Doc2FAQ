<?php

namespace App\Service;

use App\Model\FAQItem;
use App\Model\DocumentChunk;

class FAQGeneratorService
{
    private ChunkRetrieverService $retriever;
    private int $topK;
    private float $similarityThreshold;

    public function __construct(
        ChunkRetrieverService $retriever,
        int $topK = 3,
        float $similarityThreshold = 0.0
    )
    {
        $this->retriever = $retriever;
        $this->topK = $topK;
        $this->similarityThreshold = $similarityThreshold;
    }

    public function generateFAQs(
        array $chunks, 
        array $questions,
        ?int $topK = null,
        ?float $similarityThreshold = null
    ): array
    {
        $faqs = [];
        
        foreach ($questions as $question) {
            $faq = $this->generateSingleFAQ($chunks, $question, $topK, $similarityThreshold);
            if ($faq !== null) {
                $faqs[] = $faq;
            }
        }
        
        return $faqs;
    }

    public function generateSingleFAQ(
        array $chunks, 
        string $question,
        ?int $topK = null,
        ?float $similarityThreshold = null
    ): ?FAQItem
    {
        $k = $topK ?? $this->topK;
        $threshold = $similarityThreshold ?? $this->similarityThreshold;
        
        $relevantChunks = $this->retriever->findRelevantChunks($chunks, $question, $k, $threshold);
        
        if (empty($relevantChunks)) {
            return null;
        }
        
        $contextText = $this->buildContext($relevantChunks);
        $answer = $this->generateAnswerFromContext($question, $contextText);
        
        $sources = [];
        foreach ($relevantChunks as $chunk) {
            $sources[] = $chunk->getSourceReference();
        }
        
        return new FAQItem($question, $answer, $sources);
    }

    private function buildContext(array $chunks): string
    {
        $contextParts = [];
        
        foreach ($chunks as $index => $chunk) {
            $contextParts[] = sprintf(
                "[Context %d from %s]:\n%s",
                $index + 1,
                $chunk->getSourceDocument(),
                $chunk->getContent()
            );
        }
        
        return implode("\n\n", $contextParts);
    }

    private function generateAnswerFromContext(string $question, string $context): string
    {
        $prompt = $this->buildPrompt($question, $context);
        
        return $this->mockLLMCall($prompt, $context);
    }

    private function buildPrompt(string $question, string $context): string
    {
        return "Based ONLY on the following context, answer the question.\n\n" .
               "Context:\n{$context}\n\n" .
               "Question: {$question}\n\n" .
               "Answer:";
    }

    private function mockLLMCall(string $prompt, string $context): string
    {
        $sentences = preg_split('/[.!?]+/', $context, -1, PREG_SPLIT_NO_EMPTY);
        $selectedSentences = array_slice($sentences, 0, 3);
        return trim(implode('. ', array_map('trim', $selectedSentences))) . '.';
    }
}
