<?php

namespace App\Services;

use App\Rules\SafeUrl;
use DOMDocument;
use DOMElement;
use DOMNode;

class HtmlSanitizer
{
    private const ALLOWED = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'a', 'img', 'figure', 'figcaption', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];

    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'form', 'input', 'button', 'textarea', 'select', 'meta', 'link', 'base'];

    public function clean(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $old = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET);
            $body = $document->getElementsByTagName('body')->item(0);
            if (! $body) {
                return '';
            }
            $this->walk($body);
            $output = '';
            foreach ($body->childNodes as $child) {
                $output .= $document->saveHTML($child);
            }

            return $output;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }
    }

    private function walk(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if (in_array($tag, self::DROP, true)) {
                    $parent->removeChild($node);

                    continue;
                }
                $this->walk($node);
                if (! in_array($tag, self::ALLOWED, true)) {
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);

                    continue;
                }
                foreach (iterator_to_array($node->attributes) as $attribute) {
                    $name = strtolower($attribute->name);
                    $allowed = in_array($name, ['title'], true)
                        || ($tag === 'a' && $name === 'href')
                        || ($tag === 'img' && in_array($name, ['src', 'alt'], true));
                    if (! $allowed || (in_array($name, ['href', 'src'], true) && ! SafeUrl::allowed($attribute->value))) {
                        $node->removeAttributeNode($attribute);
                    }
                }
                if ($tag === 'a') {
                    $node->setAttribute('rel', 'nofollow noopener noreferrer');
                }
            } elseif ($node->nodeType !== XML_TEXT_NODE) {
                $parent->removeChild($node);
            }
        }
    }
}
