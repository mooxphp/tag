<?php

namespace Moox\Tag\Forms;

use Camya\Filament\Forms\Components\TitleWithSlugInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;

class TaxonomyCreateForm
{
    public static function getSchema(): array
    {
        return [
            TitleWithSlugInput::make(
                fieldTitle: 'title',
                fieldSlug: 'slug',
            ),
            FileUpload::make('featured_image_url')
                ->label(__('core::core.featured_image_url')),
            MarkdownEditor::make('content')
                ->label(__('core::core.content')),
            Grid::make(2)
                ->schema([
                    ColorPicker::make('color'),
                    TextInput::make('weight'),
                ]),
        ];
    }
}