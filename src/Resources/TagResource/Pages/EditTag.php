<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Tag\Resources\TagResource;
use Override;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    public ?string $selectedLang = null;

    public function mount($record): void
    {
        parent::mount($record);
        $this->selectedLang = request()->get('lang');
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    #[Override]
    protected function getFormActions(): array
    {
        return [];
    }
}
