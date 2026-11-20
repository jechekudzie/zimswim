<?php

namespace App\Support\Content;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * The small vocabulary a description on a public page may use.
 *
 * The editing screen offers emphasis, links and lists and nothing else, but
 * an editor is only a suggestion: a paste from Word, a browser extension or
 * somebody typing straight into the table can all put anything at all in the
 * box. So the vocabulary is enforced here, on the way in and again on the way
 * out, and everything outside it is discarded.
 *
 * What survives is bold, italic, links, bulleted and numbered lists, and
 * paragraphs. No headings, no colours, no font sizes, no classes, no inline
 * styles. The page's own typography stays in charge of how the words look,
 * which is the point: a description can be written, but it cannot be
 * restyled into something that fights the design around it.
 */
final class RichText
{
    /**
     * Every tag that may survive, mapped to the form it is stored in.
     *
     * Browsers still produce `b` and `i` in places, so they are kept but
     * rewritten: the stored markup says what the words mean, not how an
     * editor happened to spell it.
     */
    private const ALLOWED = [
        'p' => 'p',
        'br' => 'br',
        'strong' => 'strong',
        'b' => 'strong',
        'em' => 'em',
        'i' => 'em',
        'ul' => 'ul',
        'ol' => 'ol',
        'li' => 'li',
        'a' => 'a',
    ];

    /**
     * Tags thrown away with everything inside them.
     *
     * Anything else unknown is unwrapped, keeping its words — a `<span>` or a
     * `<div>` from a paste is a wrapper around real writing. These are not:
     * unwrapping a script would leave its source showing as prose.
     */
    private const DISCARD = [
        'script', 'style', 'iframe', 'object', 'embed', 'template',
        'noscript', 'svg', 'math', 'form', 'input', 'button', 'head', 'title', 'hr',
    ];

    /**
     * Tags outside the vocabulary that still stood for a block of text.
     *
     * A heading or a table cell from a paste becomes a paragraph rather than
     * being unwrapped, because unwrapping would run it into the words that
     * followed it and lose the break the writer meant.
     */
    private const BLOCKS = [
        'div', 'section', 'article', 'header', 'footer', 'aside', 'main', 'nav',
        'blockquote', 'pre', 'figure', 'figcaption', 'address', 'dl', 'dt', 'dd',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'caption',
    ];

    /** Link schemes a description may point at. */
    private const SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /** @var array<string, string> */
    private static array $canonical = [];

    /**
     * The canonical, safe form of some markup.
     *
     * Returns an empty string for anything with no words in it, which is how
     * emptying the editor reads as "put the original back".
     */
    public static function sanitise(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = self::parse($html);
        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body instanceof DOMElement) {
            return '';
        }

        self::clean($body);
        self::wrapLooseText($body);

        $markup = '';

        foreach ($body->childNodes as $child) {
            $markup .= (string) $document->saveHTML($child);
        }

        return self::toPlain($markup) === '' && ! str_contains($markup, '<br>')
            ? ''
            : trim($markup);
    }

    /**
     * Safe markup for a value that may not be markup at all.
     *
     * Everything written since the editor arrived is stored as markup, but
     * the rows that predate it hold plain prose — and a line of plain prose
     * is not markup. Printing it as though it were would swallow an
     * ampersand and lose the paragraph breaks somebody typed, so a value with
     * no tag in it is treated as the writing it is.
     */
    public static function render(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return preg_match('/<[a-z][a-z0-9]*(\\s[^<>]*)?>/i', $value) === 1
            ? self::sanitise($value)
            : self::fromPlain($value);
    }

    /**
     * Markup for a plain sentence written in the catalogue.
     *
     * The catalogue holds the wording the site ships with as ordinary prose,
     * because it is also the readable account of what the site says. This is
     * how that prose becomes the same kind of markup an edit produces, so
     * that the two can be compared.
     */
    public static function fromPlain(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        $paragraphs = preg_split('/\R{2,}/u', $text) ?: [$text];

        return implode('', array_map(
            static fn (string $paragraph): string => '<p>'.nl2br(e(trim($paragraph)), false).'</p>',
            array_filter($paragraphs, static fn (string $paragraph): bool => trim($paragraph) !== ''),
        ));
    }

    /**
     * The canonical markup for a block's original wording.
     *
     * Both sides of "has this actually changed?" have to come through the
     * same parser, or an apostrophe spelled one way by the editor and another
     * way here would look like an edit and be stored as one.
     */
    public static function canonical(string $default): string
    {
        return self::$canonical[$default] ??= self::sanitise(self::fromPlain($default));
    }

    /**
     * Just the words, for comparing, counting and showing the original.
     */
    public static function toPlain(string $html): string
    {
        $spaced = str_ireplace(['</p>', '</li>', '<br>', '<br/>', '<br />'], ' ', $html);
        $decoded = html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $decoded));
    }

    private static function parse(string $html): DOMDocument
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        /*
         * The charset hint is not decoration: without it libxml reads the
         * fragment as Latin-1 and every apostrophe and dash in the
         * federation's wording comes back as mojibake.
         */
        $document->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><body>'.$html.'</body>',
            LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    /**
     * Walk a node's children, keeping the vocabulary and dropping the rest.
     */
    private static function clean(DOMNode $node): void
    {
        /*
         * Snapshotted, because unwrapping or removing a child mutates the
         * live child list underneath the loop.
         */
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $node->removeChild($child);

                continue;
            }

            $name = strtolower($child->nodeName);

            if (in_array($name, self::DISCARD, true)) {
                $node->removeChild($child);

                continue;
            }

            if (! isset(self::ALLOWED[$name])) {
                self::clean($child);

                /*
                 * A wrapper around paragraphs is only a wrapper, so it goes
                 * and they stay. A block holding nothing but words becomes
                 * the paragraph it was standing in for.
                 */
                if (! in_array($name, self::BLOCKS, true) || self::holdsBlock($child)) {
                    self::unwrap($child);

                    continue;
                }

                self::dropIfEmpty($node, self::rename($child, 'p'));

                continue;
            }

            // A list item loose on the page is a paragraph, not a bullet.
            if ($name === 'li' && ! in_array(strtolower($node->nodeName), ['ul', 'ol'], true)) {
                $child = self::rename($child, 'p');
                $name = 'p';
            }

            if (self::ALLOWED[$name] !== $name) {
                $child = self::rename($child, self::ALLOWED[$name]);
                $name = self::ALLOWED[$name];
            }

            self::attributes($child, $name);
            self::clean($child);

            if ($name === 'li') {
                self::flattenListItem($child);
            }

            if ($name === 'a' && ! $child->hasAttribute('href')) {
                // A link pointing nowhere, or somewhere we will not send a
                // reader, is left as the words it was written around.
                self::unwrap($child);

                continue;
            }

            if ($name === 'p') {
                self::dropIfEmpty($node, $child);
            }
        }
    }

    /**
     * Drop the paragraph an editor wraps a one-line bullet in.
     *
     * A list item holding a single paragraph and nothing else is just a
     * bullet, and saying so in the markup keeps the list's spacing in the
     * hands of the list rather than the paragraph inside it. An item that
     * really does hold two paragraphs, or a nested list, is left as it is.
     */
    private static function flattenListItem(DOMElement $item): void
    {
        $paragraphs = [];

        foreach ($item->childNodes as $node) {
            if ($node instanceof DOMElement && strtolower($node->nodeName) === 'p') {
                $paragraphs[] = $node;

                continue;
            }

            if ($node instanceof DOMText && trim($node->textContent) === '') {
                continue;
            }

            return;
        }

        if (count($paragraphs) === 1) {
            self::unwrap($paragraphs[0]);
        }
    }

    /**
     * Whether an element holds something that lays out as a block.
     */
    private static function holdsBlock(DOMElement $element): bool
    {
        foreach (['p', 'ul', 'ol'] as $tag) {
            if ($element->getElementsByTagName($tag)->length > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a paragraph with nothing in it.
     *
     * An editor leaves one behind wherever somebody pressed return twice,
     * and on the page that is a gap the design never asked for. A deliberate
     * line break is words enough to keep.
     */
    private static function dropIfEmpty(DOMNode $parent, DOMElement $paragraph): void
    {
        if (trim($paragraph->textContent) !== '' || $paragraph->getElementsByTagName('br')->length > 0) {
            return;
        }

        $parent->removeChild($paragraph);
    }

    /**
     * Strip every attribute, then put back the one a link needs.
     */
    private static function attributes(DOMElement $element, string $name): void
    {
        $href = $name === 'a' ? self::href($element->getAttribute('href')) : null;

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $element->removeAttribute($attribute->nodeName);
        }

        if ($href === null) {
            return;
        }

        $element->setAttribute('href', $href);

        // Links in editable copy point off the site by nature, so they carry
        // the usual precautions rather than relying on whoever wrote them.
        $element->setAttribute('rel', 'noopener nofollow');
    }

    /**
     * A link target, or null if a reader should not be sent there.
     */
    private static function href(string $href): ?string
    {
        $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        // Control characters are how `java\0script:` gets past a check that
        // only looks at the scheme it can see.
        $href = (string) preg_replace('/[\x00-\x20]/', '', $href);

        if ($href === '') {
            return null;
        }

        // Same page or same site, which carries no scheme to vet.
        if (str_starts_with($href, '/') || str_starts_with($href, '#')) {
            return str_starts_with($href, '//') ? null : $href;
        }

        $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));

        return in_array($scheme, self::SCHEMES, true) ? $href : null;
    }

    /**
     * Replace an element with its own children.
     */
    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    /**
     * The same content under a different tag.
     *
     * DOM has no rename, so the node is rebuilt; the children move across
     * rather than being copied, so nothing inside is re-parsed.
     */
    private static function rename(DOMElement $element, string $to): DOMElement
    {
        $replacement = $element->ownerDocument?->createElement($to);
        $parent = $element->parentNode;

        if (! $replacement instanceof DOMElement || $parent === null) {
            return $element;
        }

        while ($element->firstChild !== null) {
            $replacement->appendChild($element->firstChild);
        }

        $parent->replaceChild($replacement, $element);

        return $replacement;
    }

    /**
     * Put words sitting loose at the top level into a paragraph.
     *
     * The editor never produces these, but a pasted or hand-written value
     * can, and a page that lays out paragraphs needs paragraphs to lay out.
     */
    private static function wrapLooseText(DOMElement $body): void
    {
        $blocks = ['p', 'ul', 'ol'];
        $paragraph = null;

        foreach (iterator_to_array($body->childNodes) as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->nodeName), $blocks, true)) {
                $paragraph = null;

                continue;
            }

            /*
             * Whitespace between two runs of words belongs to the paragraph
             * being built — dropping it would weld the words together. Only
             * the whitespace sitting between blocks is discarded.
             */
            if ($paragraph === null && $child instanceof DOMText && trim($child->textContent) === '') {
                continue;
            }

            if ($paragraph === null) {
                $paragraph = $body->ownerDocument?->createElement('p');

                if (! $paragraph instanceof DOMElement) {
                    return;
                }

                $body->insertBefore($paragraph, $child);
            }

            $paragraph->appendChild($child);
        }
    }
}
