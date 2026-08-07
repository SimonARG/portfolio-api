<?php

declare(strict_types=1);

namespace App\Support;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Renders the content model's Markdown into HTML that is safe to inject with
 * `v-html` on the client (REBUILD_PLAN.md §2.4: "description_html is
 * pre-rendered and sanitised server-side so the client ships no Markdown
 * parser").
 *
 * Two independent passes, because either one alone would be a single point of
 * failure:
 *
 *   1. CommonMark parses with `html_input: strip`, so raw HTML in the source is
 *      discarded rather than passed through, and `allow_unsafe_links: false`,
 *      which drops `javascript:` and `data:` URLs.
 *   2. The result goes through an allowlist sanitiser. CommonMark's own output
 *      is already safe; this pass exists so that a future content path which
 *      reaches the renderer with HTML already in it — a paste into the session-9
 *      admin editor, say — still cannot introduce an element we did not choose.
 */
final class MarkdownRenderer
{
    private readonly CommonMarkConverter $converter;

    private readonly HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            // The content is hand-authored prose; nothing legitimate nests deeply.
            'max_nesting_level' => 20,
        ]);

        $this->sanitizer = new HtmlSanitizer($this->sanitizerConfig());
    }

    /**
     * Render a Markdown string to sanitised HTML.
     *
     * Returns null for null or blank input so a translatable column with no
     * value for a locale renders to no value rather than to an empty string —
     * Spatie treats "" as a present translation, which would defeat the
     * fallback chain.
     */
    public function toHtml(?string $markdown): ?string
    {
        if ($markdown === null || trim($markdown) === '') {
            return null;
        }

        try {
            $rendered = $this->converter->convert($markdown);
        } catch (CommonMarkException) {
            // A document that breaches max_nesting_level is content we do not
            // want to publish half-rendered.
            return null;
        }

        $html = trim($this->sanitizer->sanitize($rendered->getContent()));

        return $html === '' ? null : $html;
    }

    /**
     * The allowlist. Anything not named here is dropped.
     *
     * Deliberately absent:
     *  - `img`: images belong to the `media` table with recorded dimensions and
     *    renditions, not inline in prose where they would be unsized and
     *    unoptimised (and would cost CLS against the §2.8 budget of 0.02).
     *  - `h1`: the page owns its single `h1`. Letting content inject one is how
     *    the legacy popups ended up with inverted heading order (§1.5 defect 26).
     *  - `iframe`, `script`, `style`, `form`: no content need, large attack surface.
     */
    private function sanitizerConfig(): HtmlSanitizerConfig
    {
        return (new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('hr')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('del')
            ->allowElement('code')
            ->allowElement('pre')
            ->allowElement('blockquote')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('h4')
            ->allowElement('a', ['href', 'title'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks()
            // Outbound links from project copy are third-party by definition.
            ->forceAttribute('a', 'rel', 'noopener noreferrer');
    }
}
