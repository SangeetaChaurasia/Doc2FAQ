<?php

namespace App\Controller;

use App\Service\DocumentLoaderService;
use App\Service\DocumentChunkerService;
use App\Service\FAQGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FAQController extends AbstractController
{
    public function __construct(
        private DocumentLoaderService $docLoader,
        private DocumentChunkerService $docChunker,
        private FAQGeneratorService $faqGen
    ) {}

    #[Route('/api/faq/generate', name: 'faq_generate_api', methods: ['POST'])]
    public function createFAQ(Request $request): JsonResponse
    {
        try {
            $input = $this->getInputData($request);
            
            if (array_key_exists('error', $input)) {
                return $this->json(['error' => $input['error']], 400);
            }
            
            $docInfo = $input['doc'];
            $qList = $input['qs'];
            
            $allChunks = $this->docChunker->createChunks(
                $docInfo['content'],
                $docInfo['filename'],
                $docInfo['metadata']
            );
            
            if (!$allChunks) {
                return $this->json(['error' => 'Chunking failed'], 400);
            }
            
            $results = $this->faqGen->generateFAQs($allChunks, $qList);
            
            $output = [];
            foreach ($results as $r) {
                $output[] = $r->toArray();
            }
            
            return $this->json([
                'status' => 'success',
                'document' => $docInfo['filename'],
                'chunks_created' => count($allChunks),
                'faqs' => $output,
            ]);
            
        } catch (\Throwable $t) {
            return $this->json(['error' => $t->getMessage()], 500);
        }
    }

    private function getInputData(Request $r): array
    {
        $ct = $r->headers->get('Content-Type', '');
        
        if (str_contains($ct, 'multipart')) {
            $f = $r->files->get('document');
            if (!$f) return ['error' => 'No file'];
            
            $d = $this->docLoader->loadFromUploadedFile($f);
            $qj = $r->request->get('questions', '[]');
            $q = json_decode($qj, true);
            if (!is_array($q) || !$q) {
                $q = ['What is the main topic?'];
            }
            
            return ['doc' => $d, 'qs' => $q];
        }
        
        if (str_contains($ct, 'json')) {
            $j = json_decode($r->getContent(), true);
            
            if (!isset($j['text'])) {
                return ['error' => 'No text provided'];
            }
            
            $fn = $j['filename'] ?? 'input.txt';
            $d = $this->docLoader->loadFromText($j['text'], $fn);
            $q = $j['questions'] ?? ['What is the main topic?'];
            
            return ['doc' => $d, 'qs' => $q];
        }
        
        return ['error' => 'Unsupported content type'];
    }
}
