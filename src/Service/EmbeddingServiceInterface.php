<?php

namespace App\Service;

use App\Exception\EmbeddingException;

interface EmbeddingServiceInterface
{
    /**
     * Generate an embedding vector for the given text.
     *
     * @param string $text The text to generate an embedding for
     * @return array<float> The embedding vector
     * @throws EmbeddingException If embedding generation fails
     */
    public function generateEmbedding(string $text): array;
}
