<?php

declare(strict_types=1);

namespace Moox\Tag\Resources;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Media\Tables\Columns\CustomImageColumn;
use Moox\Tag\Models\Tag;
use Moox\Tag\Resources\TagResource\Pages\CreateTag;
use Moox\Tag\Resources\TagResource\Pages\EditTag;
use Moox\Tag\Resources\TagResource\Pages\ListTags;
use Moox\Tag\Resources\TagResource\Pages\ViewTag;
use Override;

class TagResource extends Resource
{
    use TabsInResource;

    protected static ?string $model = Tag::class;

    protected static ?string $currentTab = null;

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    protected static ?string $navigationIcon = 'gmdi-label';

    protected static ?string $authorModel = null;

    #[Override]
    public static function form(Form $form): Form
    {
        static::initUserModel();

        return $form->schema([
            Grid::make(2)
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    MediaPicker::make('featured_image_url')
                                        ->label(__('core::core.featured_image_url')),
                                    TextInput::make('title')
                                        ->live(onBlur: true)
                                        ->label(__('core::core.title'))
                                        ->required()
                                        ->afterStateHydrated(function (TextInput $component) {
                                            $lang = request()->get('lang');
                                            if ($lang && $component->getRecord()->hasTranslation($lang)) {
                                                $component->state($component->getRecord()->translateOrNew($lang)->title);
                                            } else {
                                                $component->state($component->getRecord()->title ?? '');
                                            }
                                        })
                                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state)))
                                        ->dehydrateStateUsing(function (string $state, $record, $livewire) {
                                            if (! $livewire->selectedLang) {
                                                $record->title = $state;

                                                return $state;
                                            }

                                            $record->translateOrNew($livewire->selectedLang)->title = $state;

                                            return $state;
                                        }),
                                    TextInput::make('slug')
                                        ->label(__('core::core.slug'))
                                        ->required()
                                        ->afterStateHydrated(function (TextInput $component) {
                                            $lang = request()->get('lang');
                                            if ($lang && $component->getRecord()->hasTranslation($lang)) {
                                                $component->state($component->getRecord()->translateOrNew($lang)->slug);
                                            } else {
                                                $component->state($component->getRecord()->slug ?? '');
                                            }
                                        })
                                        ->dehydrateStateUsing(function (string $state, $record, $livewire) {
                                            if (! $livewire->selectedLang) {
                                                $record->slug = $state;

                                                return $state;
                                            }

                                            $record->translateOrNew($livewire->selectedLang)->slug = $state;

                                            return $state;
                                        }),
                                    MarkdownEditor::make('content')
                                        ->label(__('core::core.content'))
                                        ->required()
                                        ->afterStateHydrated(function (MarkdownEditor $component) {
                                            $lang = request()->get('lang');
                                            if ($lang && $component->getRecord()->hasTranslation($lang)) {
                                                $component->state($component->getRecord()->translateOrNew($lang)->content);
                                            } else {
                                                $component->state($component->getRecord()->content ?? '');
                                            }
                                        })
                                        ->dehydrateStateUsing(function (string $state, $record, $livewire) {
                                            if (! $livewire->selectedLang) {
                                                $record->content = $state;

                                                return $state;
                                            }

                                            $record->translateOrNew($livewire->selectedLang)->content = $state;

                                            return $state;
                                        }),

                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    Actions::make([
                                        Action::make('restore')
                                            ->label(__('core::core.restore'))
                                            ->color('success')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->restore())
                                            ->visible(fn ($livewire, $record): bool => $record && $record->trashed() && $livewire instanceof ViewTag),
                                        Action::make('save')
                                            ->label(__('core::core.save'))
                                            ->color('primary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(function ($livewire): void {
                                                $livewire instanceof CreateTag ? $livewire->create() : $livewire->save();
                                            })
                                            ->visible(fn ($livewire): bool => $livewire instanceof CreateTag || $livewire instanceof EditTag),
                                        Action::make('saveAndCreateAnother')
                                            ->label(__('core::core.save_and_create_another'))
                                            ->color('secondary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(function ($livewire): void {
                                                $livewire->saveAndCreateAnother();
                                            })
                                            ->visible(fn ($livewire): bool => $livewire instanceof CreateTag),
                                        Action::make('cancel')
                                            ->label(__('core::core.cancel'))
                                            ->color('secondary')
                                            ->outlined()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->url(fn (): string => static::getUrl('index'))
                                            ->visible(fn ($livewire): bool => $livewire instanceof CreateTag),
                                        Action::make('edit')
                                            ->label(__('core::core.edit'))
                                            ->color('primary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->url(fn ($record): string => static::getUrl('edit', ['record' => $record]))
                                            ->visible(fn ($livewire, $record): bool => $livewire instanceof ViewTag && ! $record->trashed()),
                                        Action::make('restore')
                                            ->label(__('core::core.restore'))
                                            ->color('success')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->restore())
                                            ->visible(fn ($livewire, $record): bool => $record && $record->trashed() && $livewire instanceof EditTag),
                                        Action::make('delete')
                                            ->label(__('core::core.delete'))
                                            ->color('danger')
                                            ->link()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->delete())
                                            ->visible(fn ($livewire, $record): bool => $record && ! $record->trashed() && $livewire instanceof EditTag),
                                    ]),
                                    ColorPicker::make('color'),
                                    TextInput::make('weight'),
                                    TextInput::make('count')
                                        ->disabled()
                                        ->visible(fn ($livewire, $record): bool => ($record && $livewire instanceof EditTag) || ($record && $livewire instanceof ViewTag)),
                                    DateTimePicker::make('created_at')
                                        ->disabled()
                                        ->visible(fn ($livewire, $record): bool => ($record && $livewire instanceof EditTag) || ($record && $livewire instanceof ViewTag)),
                                    DateTimePicker::make('updated_at')
                                        ->disabled()
                                        ->visible(fn ($livewire, $record): bool => ($record && $livewire instanceof EditTag) || ($record && $livewire instanceof ViewTag)),
                                    DateTimePicker::make('deleted_at')
                                        ->disabled()
                                        ->visible(fn ($livewire, $record): bool => $record && $record->trashed() && $livewire instanceof ViewTag),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(['lg' => 3]),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        static::initUserModel();

        $currentTab = static::getCurrentTab();

        return $table
            ->columns([
                CustomImageColumn::make('featured_image_url')
                    ->label(__('core::core.image'))
                    ->defaultImageUrl(url('/moox/core/assets/noimage.svg'))
                    ->alignment('center'),
                TextColumn::make('title')
                    ->label(__('core::core.title'))
                    ->searchable()
                    ->limit(30)
                    ->toggleable()
                    ->sortable()
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->title;
                        }

                        return $record->title;
                    }),
                TextColumn::make('slug')
                    ->label(__('core::core.slug'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable()
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->title;
                        }

                        return $record->title;
                    }),
                TextColumn::make('content')
                    ->label(__('core::core.content'))
                    ->sortable()
                    ->limit(30)
                    ->searchable()
                    ->toggleable()
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->title;
                        }

                        return $record->title;
                    }),
                TextColumn::make('count')
                    ->label(__('core::core.count'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('weight')
                    ->label(__('tag::translations.weight'))
                    ->sortable()
                    ->toggleable(),
                ColorColumn::make('color')
                    ->label(__('tag::translations.color'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                ViewAction::make()->url(
                    fn ($record) => request()->has('lang')
                    ? route('filament.admin.resources.tags.view', ['record' => $record, 'lang' => request()->get('lang')])
                    : route('filament.admin.resources.tags.view', $record)
                ),
                EditAction::make()
                    ->url(
                        fn ($record) => request()->has('lang')
                        ? route('filament.admin.resources.tags.edit', ['record' => $record, 'lang' => request()->get('lang')])
                        : route('filament.admin.resources.tags.edit', $record)
                    )
                    ->hidden(fn (): bool => in_array(static::getCurrentTab(), ['trash', 'deleted'])),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->hidden(fn (): bool => in_array($currentTab, ['trash', 'deleted'])),
                RestoreBulkAction::make()->visible(fn (): bool => in_array($currentTab, ['trash', 'deleted'])),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
            'edit' => EditTag::route('/{record}/edit'),
            'create' => CreateTag::route('/create'),
            'view' => ViewTag::route('/{record}'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('tag.resources.tag.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('tag.resources.tag.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('tag.resources.tag.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('tag.resources.tag.single');
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('tag.navigation_group');
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        return config('tag.navigation_sort') + 3;
    }

    protected static function initUserModel(): void
    {
        if (static::$authorModel === null) {
            static::$authorModel = config('tag.user_model');
        }
    }

    protected static function getUserOptions(): array
    {
        return static::$authorModel::query()->get()->pluck('name', 'id')->toArray();
    }

    protected static function shouldShowAuthorField(): bool
    {
        return static::$authorModel && class_exists(static::$authorModel);
    }

    public static function getCurrentTab(): ?string
    {
        if (static::$currentTab === null) {
            static::$currentTab = request()->query('tab', '');
        }

        return static::$currentTab ?: null;
    }

    public static function getTableQuery(?string $currentTab = null): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes();

        if ($currentTab === 'trash' || $currentTab === 'deleted') {
            $model = static::getModel();
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query->whereNotNull($model::make()->getQualifiedDeletedAtColumn());
            }
        }

        return $query;
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
