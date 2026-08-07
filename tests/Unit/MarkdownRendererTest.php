<?php

declare(strict_types=1);

use App\Support\MarkdownRenderer;

/**
 * The renderer's output is injected into the page as HTML, so these are as much
 * security assertions as formatting ones.
 */
function renderer(): MarkdownRenderer
{
    static $renderer;

    return $renderer ??= new MarkdownRenderer;
}

it('renders a lead paragraph and a bullet list', function (): void {
    $html = renderer()->toHtml("Lead line:\n\n- one\n- two");

    expect($html)
        ->toContain('<p>Lead line:</p>')
        ->toContain('<ul>')
        ->toContain('<li>one</li>')
        ->toContain('<li>two</li>');
});

it('renders separate paragraphs for blank-line-separated prose', function (): void {
    $html = renderer()->toHtml("First paragraph.\n\nSecond paragraph.");

    expect(substr_count((string) $html, '<p>'))->toBe(2);
});

it('preserves CJK content unescaped', function (): void {
    $html = renderer()->toHtml("リード：\n\n- 項目");

    expect($html)->toContain('リード：')->toContain('<li>項目</li>');
});

it('preserves the accented and typographic characters the copy actually uses', function (): void {
    $html = renderer()->toHtml('Tres idiomas —español, inglés y japonés— con configuración');

    expect($html)
        ->toContain('—español')
        ->toContain('inglés')
        ->toContain('configuración');
});

describe('sanitisation', function (): void {
    it('strips script tags', function (): void {
        expect(renderer()->toHtml("Hi\n\n<script>alert(1)</script>"))
            ->toBe('<p>Hi</p>');
    });

    it('strips inline event handlers by removing the raw HTML block', function (): void {
        expect(renderer()->toHtml('<p onclick="steal()">hi</p>'))->toBeNull();
    });

    it('strips iframes', function (): void {
        expect(renderer()->toHtml('<iframe src="//evil"></iframe>'))->toBeNull();
    });

    it('drops javascript: hrefs but keeps the link text', function (): void {
        $html = renderer()->toHtml('[click](javascript:alert(1))');

        expect($html)->not->toContain('javascript:')->toContain('click');
    });

    it('drops images, which belong to the media table', function (): void {
        expect(renderer()->toHtml('text ![x](/a.png) more'))->not->toContain('<img');
    });

    it('drops h1, because the page owns its single h1', function (): void {
        $html = renderer()->toHtml("# Big\n\n## Section");

        expect($html)->not->toContain('<h1')->toContain('<h2>Section</h2>');
    });

    it('keeps http links and marks them noopener', function (): void {
        $html = renderer()->toHtml('[PDA](https://example.com)');

        expect($html)
            ->toContain('href="https://example.com"')
            ->toContain('rel="noopener noreferrer"');
    });
});

describe('empty input', function (): void {
    it('returns null rather than an empty string', function (mixed $input): void {
        // An empty string would be a *present* translation as far as Spatie is
        // concerned, which would stop the `requested → es` fallback resolving.
        expect(renderer()->toHtml($input))->toBeNull();
    })->with([
        'null' => null,
        'empty' => '',
        'whitespace' => "   \n  ",
    ]);
});
