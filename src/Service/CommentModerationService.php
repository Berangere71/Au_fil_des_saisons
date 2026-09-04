<?php

namespace App\Service;

final class CommentModerationService
{
    /**
     * @param list<string> $forbiddenTerms
     */
    public function __construct(
        private readonly array $forbiddenTerms,
    ) {
    }

    public function findForbiddenTerm(string $content): ?string
    {
        $normalizedContent = $this->normalize($content);

        foreach ($this->forbiddenTerms as $term) {
            $normalizedTerm = $this->normalize($term);
            $pattern = '/(^| )'.preg_quote($normalizedTerm, '/').'( |$)/u';

            if (preg_match($pattern, $normalizedContent) === 1) {
                return $term;
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $lower = mb_strtolower($value);
        $spaced = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $lower);

        return trim((string) preg_replace('/\s+/u', ' ', (string) $spaced));
    }
}
