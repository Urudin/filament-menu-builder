<?php

namespace Biostate\FilamentMenuBuilder\Filament\Resources;

use Biostate\FilamentMenuBuilder\Enums\MenuItemTarget;
use Biostate\FilamentMenuBuilder\Enums\MenuItemType;
use Biostate\FilamentMenuBuilder\Models\MenuItem;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-menu-builder::menu-builder.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('filament-menu-builder::menu-builder.menu_item');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-menu-builder::menu-builder.menu_items');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-menu-builder::menu-builder.form_labels.name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->label(__('filament-menu-builder::menu-builder.form_labels.url')),
                Tables\Columns\TextColumn::make('menu.name')
                    ->label(__('filament-menu-builder::menu-builder.menu_name')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getFormSchema()
    {
        return [
            TextInput::make('name')
                ->label(__('filament-menu-builder::menu-builder.form_labels.name'))
                ->autofocus()
                ->required()
                ->maxLength(255),
            Select::make('target')
                ->options(MenuItemTarget::class)
                ->default('_self')
                ->required(),
            TextInput::make('link_class')
                ->label(__('filament-menu-builder::menu-builder.form_labels.link_class'))
                ->maxLength(255),
            TextInput::make('link_title')
                ->label('Title')
                ->maxLength(255),
            TextInput::make('wrapper_class')
                ->label(__('filament-menu-builder::menu-builder.form_labels.wrapper_class'))
                ->maxLength(255),
            Fieldset::make('URL')
                ->columns(1)
                ->schema([
                    Select::make('type')
                        ->label(__('filament-menu-builder::menu-builder.form_labels.type'))
                        ->options(MenuItemType::class)
                        ->afterStateUpdated(function (callable $set) {
                            $set('menuable_type', null);
                            $set('menuable_id', null);
                            $set('url', null);
                        })
                        ->default('link')
                        ->required()
                        ->reactive(),
                    // URL
                    TextInput::make('url')
                        ->label(__('filament-menu-builder::menu-builder.form_labels.url'))
                        ->hidden(fn ($get) => $get('type') != 'link')
                        ->maxLength(255)
                        ->required(fn ($get) => $get('type') == 'link'),
                    // MODEL
                    Select::make('menuable_type')
                        ->label(__('filament-menu-builder::menu-builder.form_labels.menuable_type'))
                        ->options(
                            array_flip(config('filament-menu-builder.models', []))
                        )
                        ->reactive()
                        ->required(fn ($get) => $get('type') == 'model')
                        ->afterStateUpdated(fn (callable $set) => $set('menuable_id', null))
                        ->hidden(fn ($get) => empty(config('filament-menu-builder.models', [])) || $get('type') != 'model'),
                    Select::make('menuable_id')
                        ->label(__('filament-menu-builder::menu-builder.form_labels.menuable_id'))
                        ->searchable()
                        ->options(fn ($get) => $get('menuable_type')::all()->pluck($get('menuable_type')::getFilamentSearchLabel(), 'id'))
                        ->getSearchResultsUsing(function (string $search, callable $get) {
                            $className = $get('menuable_type');

                            return $className::filamentSearch($search)->pluck($className::getFilamentSearchLabel(), 'id');
                        })
                        ->required(fn ($get) => $get('menuable_type') != null)
                        ->getOptionLabelUsing(fn ($value, $get): ?string => $get('menuable_type')::find($value)?->getFilamentSearchOptionName())
                        ->hidden(fn ($get) => $get('menuable_type') == null),
                    Toggle::make('use_menuable_name')
                        ->label(__('filament-menu-builder::menu-builder.form_labels.use_menuable_name'))
                        ->hidden(fn ($get) => $get('menuable_type') == null)
                        ->default(false),
                ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Biostate\FilamentMenuBuilder\Filament\Resources\MenuItemResource\Pages\ListMenuItems::route('/'),
            'create' => \Biostate\FilamentMenuBuilder\Filament\Resources\MenuItemResource\Pages\CreateMenuItem::route('/create'),
            'edit' => \Biostate\FilamentMenuBuilder\Filament\Resources\MenuItemResource\Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}
