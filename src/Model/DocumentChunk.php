<?php

namespace App\Model;

class DocumentChunk
{
    private string $content;
    private string $chunkId;
    private string $sourceDocument;
    private ?int $page;
    private ?string $section;
    private array $metadata;

    public function __construct(
        string $content,
        string $chunkId,
        string $sourceDocument,
        ?int $page = null,
        ?string $section = null,
        array $metadata = []
    ) {
        $this->content = $content;
        $this->chunkId = $chunkId;
        $this->sourceDocument = $sourceDocument;
        $this->page = $page;
        $this->section = $section;
        $this->metadata = $metadata;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getChunkId(): string
    {
        return $this->chunkId;
    }

    public function getSourceDocument(): string
    {
        return $this->sourceDocument;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getSourceReference(): SourceReference
    {
        return new SourceReference(
            $this->sourceDocument,
            $this->chunkId,
            $this->page,
            $this->section
        );
    }
}
