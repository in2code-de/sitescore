<?php

declare(strict_types=1);

namespace In2code\Sitescore\Utility;

/**
 * Technical HTML analyzer for precise element counting and structure validation
 * Used for reliable checks that LLMs often get wrong
 */
class HtmlAnalyzer
{
    /**
     * Analyze HTML and return technical findings as suggestions
     *
     * @param string $html Raw HTML content
     * @return array Array of suggestion arrays: [['type' => 'warning', 'message' => '...']]
     */
    public static function analyzeTechnical(string $html): array
    {
        $suggestions = [];

        // Check H1 count
        $h1Count = self::countElement($html, 'h1');
        if ($h1Count === 0) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'No H1 heading found - every page should have exactly one H1',
            ];
        } elseif ($h1Count > 1) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'Multiple H1 headings found (' . $h1Count . 'x) - use only one H1 per page',
            ];
        }

        // Check images without alt attributes
        $imagesWithoutAlt = self::countImagesWithoutAlt($html);
        if ($imagesWithoutAlt > 0) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => $imagesWithoutAlt . ' image(s) missing alt attributes - add descriptive alt text for accessibility',
            ];
        }

        // Check for empty alt attributes (decorative images should use alt="")
        $imagesWithEmptyAlt = self::countImagesWithEmptyAlt($html);
        if ($imagesWithEmptyAlt > 0) {
            $suggestions[] = [
                'type' => 'info',
                'message' => $imagesWithEmptyAlt . ' image(s) with empty alt text - ensure these are truly decorative',
            ];
        }

        // Check meta description
        if (self::hasMetaDescription($html) === false) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'No meta description found - add a description for better SEO',
            ];
        } else {
            $descLength = self::getMetaDescriptionLength($html);
            if ($descLength < 120) {
                $suggestions[] = [
                    'type' => 'warning',
                    'message' => 'Meta description too short (' . $descLength . ' chars) - aim for 150-160 characters',
                ];
            } elseif ($descLength > 160) {
                $suggestions[] = [
                    'type' => 'warning',
                    'message' => 'Meta description too long (' . $descLength . ' chars) - may be truncated in search results',
                ];
            }
        }

        if (self::hasTitle($html) === false) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'No title tag found - critical for SEO',
            ];
        }

        return $suggestions;
    }

    protected static function countElement(string $html, string $tag): int
    {
        preg_match_all('/<' . $tag . '[\s>]/i', $html, $matches);
        return count($matches[0]);
    }

    protected static function countImagesWithoutAlt(string $html): int
    {
        preg_match_all('/<img[^>]*>/i', $html, $imgTags);

        $count = 0;
        foreach ($imgTags[0] as $imgTag) {
            // Check if alt attribute is missing
            if (!preg_match('/\salt=/i', $imgTag)) {
                $count++;
            }
        }

        return $count;
    }

    protected static function countImagesWithEmptyAlt(string $html): int
    {
        preg_match_all('/alt=["\']\s*["\']/i', $html, $matches);
        return count($matches[0]);
    }

    protected static function hasMetaDescription(string $html): bool
    {
        return (bool)preg_match('/<meta\s+name=["\']description["\']/i', $html);
    }

    protected static function getMetaDescriptionLength(string $html): int
    {
        if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
            return mb_strlen($matches[1]);
        }
        return 0;
    }

    protected static function hasTitle(string $html): bool
    {
        return (bool)preg_match('/<title[^>]*>.*?<\/title>/is', $html);
    }
}
