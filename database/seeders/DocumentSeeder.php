<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Document;
use Database\Seeders\Support\ContentInventory;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, array{label: array<string, string>}> $overrides */
        $overrides = ContentInventory::overrides()['documents'] ?? [];

        foreach (ContentInventory::section('documents') as $document) {
            /** @var string $key */
            $key = $document['key'];

            // Both CVs had a single untranslated <span>; the override names each
            // document's language in the reader's language.
            $label = $overrides[$key]['label'] ?? $document['label'];

            Document::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => ContentInventory::present($label),
                    'locale_code' => $document['locale_code'],
                    // media_id stays null until session 4 populates the media
                    // table; the PDFs themselves live on the legacy branches.
                    'sort_order' => $document['sort_order'],
                    'is_published' => $document['is_published'],
                ],
            );
        }
    }
}
