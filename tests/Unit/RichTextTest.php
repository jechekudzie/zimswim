<?php

use App\Support\Content\RichText;

it('keeps the vocabulary a description is allowed', function (string $html): void {
    expect(RichText::sanitise($html))->toBe($html);
})->with([
    '<p>Plain words</p>',
    '<p>With <strong>emphasis</strong> and <em>stress</em></p>',
    '<p>One</p><p>Two</p>',
    '<ul><li>First</li><li>Second</li></ul>',
    '<ol><li>Step one</li></ol>',
    '<p>Line<br>break</p>',
]);

it('drops the paragraph an editor wraps a one-line bullet in', function (): void {
    expect(RichText::sanitise('<ul><li><p>One</p></li><li><p>Two</p></li></ul>'))
        ->toBe('<ul><li>One</li><li>Two</li></ul>');
});

it('leaves a list item that really does hold two paragraphs', function (): void {
    expect(RichText::sanitise('<ul><li><p>One</p><p>Two</p></li></ul>'))
        ->toBe('<ul><li><p>One</p><p>Two</p></li></ul>');
});

it('rewrites presentational tags as what the words mean', function (): void {
    expect(RichText::sanitise('<p><b>Bold</b> and <i>slanted</i></p>'))
        ->toBe('<p><strong>Bold</strong> and <em>slanted</em></p>');
});

it('strips every attribute, including the ones that run code', function (): void {
    expect(RichText::sanitise('<p class="x" style="color:red" onclick="steal()">Words</p>'))
        ->toBe('<p>Words</p>');
});

it('throws away a script rather than leaving its source as prose', function (): void {
    // Unwrapping would put "alert(1)" on the page as visible text, which is
    // harmless but wrong; the tag and its contents both go.
    expect(RichText::sanitise('<script>alert(1)</script><p>After</p>'))
        ->toBe('<p>After</p>');
});

it('refuses a link that would run code, keeping the words it wrapped', function (string $href): void {
    expect(RichText::sanitise('<a href="'.$href.'">Click</a>'))->toBe('<p>Click</p>');
})->with([
    'javascript:alert(1)',
    // The tab is how a scheme check that only reads what it can see is got past.
    "java\tscript:alert(1)",
    'data:text/html;base64,PHNjcmlwdD4=',
    'vbscript:msgbox',
    // Protocol-relative: a reader would leave the site without a scheme in sight.
    '//evil.example',
]);

it('keeps a link somewhere a reader may be sent, with the usual precautions', function (): void {
    expect(RichText::sanitise('<p><a href="https://zimbabweswimming.org" target="_blank" onmouseover="x()">Site</a></p>'))
        ->toBe('<p><a href="https://zimbabweswimming.org" rel="noopener nofollow">Site</a></p>');
});

it('allows a link to a page on this site', function (): void {
    expect(RichText::sanitise('<a href="/clubs">Clubs</a>'))
        ->toBe('<p><a href="/clubs" rel="noopener nofollow">Clubs</a></p>');
});

it('turns a pasted heading into a paragraph instead of running it into the next line', function (): void {
    expect(RichText::sanitise('<h1>Heading</h1><p>Body</p>'))
        ->toBe('<p>Heading</p><p>Body</p>');
});

it('drops a wrapper but keeps the paragraphs inside it', function (): void {
    expect(RichText::sanitise('<div class="word-paste"><p>One</p><p>Two</p></div>'))
        ->toBe('<p>One</p><p>Two</p>');
});

it('keeps the space between two runs of words it unwraps', function (): void {
    expect(RichText::sanitise('<span>Inline</span> <span>run</span>'))
        ->toBe('<p>Inline run</p>');
});

it('puts loose words into a paragraph so the page has one to lay out', function (): void {
    expect(RichText::sanitise('Bare words'))->toBe('<p>Bare words</p>');
});

it('removes the empty paragraph left behind by pressing return twice', function (): void {
    expect(RichText::sanitise('<p>Real</p><p></p><p>   </p>'))->toBe('<p>Real</p>');
});

it('reads markup with no words in it as empty', function (string $html): void {
    // This is what makes clearing the editor put the original wording back.
    expect(RichText::sanitise($html))->toBe('');
})->with([
    '',
    '   ',
    '<p></p>',
    '<p><strong></strong></p>',
    '<img src="x" onerror="alert(1)">',
]);

it('reads an apostrophe the same way the editor writes one', function (): void {
    // Both sides of "has this changed?" come through the same parser, or the
    // original wording would look like an edit and be stored as one.
    $plain = "At the water's edge — timekeepers";

    expect(RichText::canonical($plain))
        ->toBe(RichText::sanitise('<p>'.$plain.'</p>'))
        ->and(RichText::toPlain(RichText::canonical($plain)))
        ->toBe($plain);
});

it('reads a blank line in the catalogue as a new paragraph', function (): void {
    expect(RichText::canonical("First line.\n\nSecond line."))
        ->toBe('<p>First line.</p><p>Second line.</p>');
});

it('escapes wording that happens to contain markup characters', function (): void {
    expect(RichText::canonical('Under 11 & over'))->toBe('<p>Under 11 &amp; over</p>');
});

it('reduces markup to the words in it', function (): void {
    expect(RichText::toPlain('<ul><li>One</li><li>Two</li></ul>'))->toBe('One Two')
        ->and(RichText::toPlain('<p>A</p><p>B</p>'))->toBe('A B');
});
