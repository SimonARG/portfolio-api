<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Setting;

/**
 * Advances `settings.content_version` whenever any content row is written.
 *
 * That single integer is the cache-invalidation lever from §2.3: it forms part
 * of every Laravel response-cache key and every Nitro ISR key, and the publish
 * action additionally fires a Cloudflare purge. One write, one coherent flush
 * across all four layers — instead of four independent invalidation paths that
 * can disagree with each other.
 *
 * Registered centrally in AppServiceProvider rather than through #[ObservedBy]
 * on each model, so the set of things that counts as "content" is visible in
 * one place. Sessions 3 and 9 both need that list.
 *
 * `created` / `updated` / `deleted` rather than `saved`: Eloquent fires `saved`
 * even when save() short-circuits on a clean model, and its change set is stale
 * at that point — `getChanges()` still holds the *previous* write's changes, so
 * a no-op save is indistinguishable from a real one. `updated` only fires from
 * performUpdate(), which runs only when the model is genuinely dirty.
 *
 * Known gap: attaching or detaching a technology writes the pivot directly and
 * fires no model event here. Use Project::syncTechnologies(), which bumps
 * explicitly, rather than touching the relation and assuming the cache flushed.
 */
class BumpsContentVersion
{
    public function created(): void
    {
        Setting::bumpContentVersion();
    }

    public function updated(): void
    {
        Setting::bumpContentVersion();
    }

    public function deleted(): void
    {
        Setting::bumpContentVersion();
    }
}
