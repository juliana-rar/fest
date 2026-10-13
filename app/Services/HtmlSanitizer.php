<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Whitelist sanitizer for the admin rich-text editor. Keeps a small set of
 * formatting tags, drops every attribute except safe http(s)/mailto links.
 */
class HtmlSanitizer
{
    private const ALLOWED = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'a', 'ul', 'ol', 'li', 'h3', 'blockquote'];

    public function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $body) {
            return '';
        }

        $this->walk($body);

        $out = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        // Normalise b/i to their semantic equivalents.
        $out = preg_replace(['#<(/?)b>#i', '#<(/?)i>#i'], ['<$1strong>', '<$1em>'], $out);

        return trim(preg_replace('#<p>\s*</p>#', '', $out));
    }

    private function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'template'], true)) {
                    $node->removeChild($child);

                    continue;
                }

                $this->walk($child);

                if (! in_array($tag, self::ALLOWED, true)) {
                    // Unwrap unknown tags (div, span…) but keep their content.
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);

                    continue;
                }

                $href = $tag === 'a' ? trim($child->getAttribute('href')) : null;
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $child->removeAttribute($attr->nodeName);
                }

                if ($tag === 'a') {
                    if ($href && preg_match('#^(https?://|mailto:)#i', $href)) {
                        $child->setAttribute('href', $href);
                        $child->setAttribute('rel', 'noopener nofollow');
                    }
                }
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
            }
        }
    }
}
