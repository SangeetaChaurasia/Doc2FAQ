<?php

namespace App\Model;

class SourceReference
{
    private string $document;
    private string $chunkId;
    private ?int $page;
    private ?string $section;

    public function __construct(
        string $document,
        string $chunkId,
        ?int $page = null,
        ?string $section = null
    ) {
        $this->document = $document;
        $this->chunkId = $chunkId;
        $this->page = $page;
        $this->section = $section;
    }

    public function getDocument(): string
    {
        return $this->document;
    }

    public function getChunkId(): string
    {
        return $this->chunkId;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function toArray(): array
    {
        return array_filter([
            'document' => $this->document,
            'chunk_id' => $this->chunkId,
            'page' => $this->page,
            'section' => $this->section,
        ], fn($value) => $value !== null);
    }
}
