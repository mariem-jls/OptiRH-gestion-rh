<?php

namespace App\Service;

use Smalot\PdfParser\Parser;

class PdfParserService
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function extract(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Le fichier PDF n'existe pas : " . $filePath);
        }

        try {
            $pdf = $this->parser->parseFile($filePath);
            $text = $pdf->getText();
            if (empty($text)) {
                throw new \RuntimeException('Aucun texte n\'a pu être extrait du PDF.');
            }
            return $this->cleanText($text);
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de l\'analyse du PDF : ' . $e->getMessage());
        }
    }

    private function cleanText(string $text): string
    {
        // Normalize whitespace (replace multiple spaces, newlines, tabs with a single space)
        $text = preg_replace('/\s+/', ' ', $text);
        // Remove common PDF artifacts (e.g., page numbers)
        $text = preg_replace('/\bPage \d+\b/i', '', $text);
        // Remove unwanted special characters, but preserve dots, @, and basic punctuation
        $text = preg_replace('/[^\w\s.,;:-@]/u', '', $text);
        // Fix spacing around punctuation (e.g., "Email : mohamed" -> "Email: mohamed")
        $text = preg_replace('/\s*([.,;:-])\s*/', '$1 ', $text);
        // Ensure single space after colons in emails or labels
        $text = preg_replace('/(\w):(\w)/', '$1: $2', $text);
        // Trim leading/trailing spaces
        return trim($text);
    }
}