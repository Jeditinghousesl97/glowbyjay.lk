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
        $html = [];
        $lines = explode("\n", $normalized);
        $currentLines = [];
        $currentType = null;
        $blankLineCount = 0;

        foreach ($lines as $rawLine) {
            $line = trim((string) $rawLine);

            if ($line === '') {
                if (!empty($currentLines)) {
                    $html[] = self::renderBlock($currentLines, $currentType);
                    $currentLines = [];
                    $currentType = null;
                }
                $blankLineCount++;
                continue;
            }

            if ($blankLineCount > 1) {
                $html[] = '<div class="text-spacer text-spacer-' . min(4, $blankLineCount) . '"></div>';
            }
            $blankLineCount = 0;

            $lineType = preg_match('/^-\s+/', $line) ? 'list' : 'paragraph';
            if ($currentType !== null && $lineType !== $currentType) {
                $html[] = self::renderBlock($currentLines, $currentType);
                $currentLines = [];
            }

            $currentType = $lineType;
            $currentLines[] = $line;
        }

        if (!empty($currentLines)) {
            $html[] = self::renderBlock($currentLines, $currentType);
        }

        return implode('', $html);
    }

    private static function renderBlock(array $lines, $type)
    {
        if (empty($lines)) {
            return '';
        }

        if ($type === 'list') {
            $items = [];
            foreach ($lines as $line) {
                $items[] = '<li>' . self::applyInlineMarkup(preg_replace('/^-\s+/', '', $line)) . '</li>';
            }
            return '<ul>' . implode('', $items) . '</ul>';
        }

        $paragraph = array_map([self::class, 'applyInlineMarkup'], $lines);
        return '<p>' . implode('<br>', $paragraph) . '</p>';
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
