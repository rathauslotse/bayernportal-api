<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Html;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Thin XPath wrapper around a parsed HTML page.
 *
 * Page parsers anchor on stable hooks (element ids, aria attributes, URL
 * patterns and visible labels), never on the site's utility CSS classes.
 *
 * @internal
 */
final class Document
{
    /**
     * The intro paragraph after the page title, if it comes before the first section heading.
     */
    public const string SUMMARY = '//h1/following::p[1][count(preceding::h2) = count(//h1/preceding::h2)]';

    private readonly DOMXPath $xpath;

    public function __construct(public readonly string $html, public readonly string $url = '')
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        // The XML declaration makes libxml read the markup as UTF-8.
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->xpath = new DOMXPath($document);
    }

    /**
     * @return list<DOMElement>
     */
    public function all(string $expression, ?DOMNode $context = null): array
    {
        $nodes = $this->xpath->query($expression, $context);
        $elements = [];

        if ($nodes === false) {
            return [];
        }

        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = $node;
            }
        }

        return $elements;
    }

    public function first(string $expression, ?DOMNode $context = null): ?DOMElement
    {
        return $this->all($expression, $context)[0] ?? null;
    }

    public function byId(string $id): ?DOMElement
    {
        return $this->first(sprintf('//*[@id=%s]', self::literal($id)));
    }

    /**
     * Trimmed text of the first match, with whitespace collapsed.
     */
    public function text(string $expression, ?DOMNode $context = null): ?string
    {
        $element = $this->first($expression, $context);

        return $element === null ? null : self::clean($element->textContent);
    }

    /**
     * Inner HTML of a node, without comments and surrounding whitespace.
     */
    public function innerHtml(DOMNode $node): ?string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                continue;
            }

            $html .= $node->ownerDocument?->saveHTML($child) ?? '';
        }

        $html = trim((string) preg_replace('/\s+/u', ' ', $html));

        return $html === '' ? null : $html;
    }

    /**
     * Visible text lines of a node: <br>, paragraphs and other block
     * elements start a new line.
     *
     * @return list<string>
     */
    public static function lines(DOMNode $node): array
    {
        $buffer = '';
        self::collectLines($node, $buffer);

        return array_values(array_filter(
            array_map(static fn (string $line): string => (string) self::clean($line), explode("\n", $buffer)),
            static fn (string $line): bool => $line !== '',
        ));
    }

    private static function collectLines(DOMNode $node, string &$buffer): void
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $buffer .= $child->textContent;
            } elseif ($child instanceof DOMElement) {
                $block = in_array(strtolower($child->tagName), ['br', 'p', 'div', 'li', 'ul', 'h1', 'h2', 'h3', 'h4', 'h5'], true);
                $buffer .= $block ? "\n" : '';
                self::collectLines($child, $buffer);
                $buffer .= $block ? "\n" : '';
            }
        }
    }

    /**
     * Collapse whitespace; empty strings become null.
     */
    public static function clean(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $text));

        return $text === '' ? null : $text;
    }

    /**
     * Quote a string as an XPath literal.
     */
    public static function literal(string $value): string
    {
        if (! str_contains($value, "'")) {
            return "'".$value."'";
        }

        if (! str_contains($value, '"')) {
            return '"'.$value.'"';
        }

        return "concat('".str_replace("'", "', \"'\", '", $value)."')";
    }
}
