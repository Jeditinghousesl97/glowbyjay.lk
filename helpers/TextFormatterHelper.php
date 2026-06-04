<?php

class TextFormatterHelper
{
    public static function renderSimpleMarkup($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $blocks = preg_split("/\n\s*\n/", $normalized) ?: [];
        $html = [];

        foreach ($blocks as $block) {
            $block = trim((string) $block);
            if ($block === '') {
                continue;
            }

            $lines = array_values(array_filter(array_map('trim', explode("\n", $block)), static function ($line) {
                return $line !== '';
            }));

            if (empty($lines)) {
                continue;
            }

            $isList = true;
            foreach ($lines as $line) {
                if (!preg_match('/^-\s+/', $line)) {
                    $isList = false;
                    break;
                }
            }

            if ($isList) {
                $items = [];
                foreach ($lines as $line) {
                    $items[] = '<li>' . self::applyInlineMarkup(preg_replace('/^-\s+/', '', $line)) . '</li>';
                }
                $html[] = '<ul>' . implode('', $items) . '</ul>';
                continue;
            }

            $paragraph = array_map([self::class, 'applyInlineMarkup'], $lines);
            $html[] = '<p>' . implode('<br>', $paragraph) . '</p>';
        }

        return implode('', $html);
    }

    private static function applyInlineMarkup($text)
    {
        $escaped = htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');

        $patterns = [
            '/\*([^*\n]+)\*/' => '<strong>$1</strong>',
            '/_([^_\n]+)_/' => '<em>$1</em>',
            '/~([^~\n]+)~/' => '<del>$1</del>',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $escaped = preg_replace($pattern, $replacement, $escaped);
        }

        return $escaped;
    }
}
