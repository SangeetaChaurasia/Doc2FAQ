<?php

namespace App\Model;

class FAQItem
{
    private string $question;
    private string $answer;
    private array $sources;

    public function __construct(
        string $question,
        string $answer,
        array $sources = []
    ) {
        $this->question = $question;
        $this->answer = $answer;
        $this->sources = $sources;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function getSources(): array
    {
        return $this->sources;
    }

    public function addSource(SourceReference $source): void
    {
        $this->sources[] = $source;
    }

    public function toArray(): array
    {
        $sourcesArray = [];
        foreach ($this->sources as $source) {
            $sourcesArray[] = $source->toArray();
        }
        
        return [
            'question' => $this->question,
            'answer' => $this->answer,
            'sources' => $sourcesArray,
        ];
    }
}
