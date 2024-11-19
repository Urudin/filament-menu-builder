<?php

namespace Biostate\FilamentMenuBuilder\Filament\Resources;

use Biostate\FilamentMenuBuilder\Enums\MenuItemType;
use Biostate\FilamentMenuBuilder\Models\MenuItem;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    public static function getNavigationGroup(): ?string
    {
        return __('filament-menu-builder::menu-builder.navigation_group');
    }

    /**
     * @return string|null
     */
    public static function getModelLabel(): string
    {
        return __('filament-menu-builder::menu-builder.menu_item');
    }

    /**
     * @return string|null
     */
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
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('url'),
                Tables\Columns\TextColumn::make('menu.name'),
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
                ->autofocus()
                ->required()
                ->maxLength(255),
            Select::make('target')
                ->required()
                ->default('_self')
                ->options([
                    '_self' => 'Same window',
                    '_blank' => 'New window',
                ]),
            TextInput::make('link_class')
                ->maxLength(255),
            TextInput::make('wrapper_class')
                ->maxLength(255),
            Fieldset::make('URL')
                ->columns(1)
                ->schema([
                    Select::make('type')
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
                        ->hidden(fn ($get) => $get('type') != 'link')
                        ->maxLength(255)
                        ->required(fn ($get) => $get('type') == 'link'),
                    // ROUTE
                    Select::make('route')
                        ->searchable()
                        ->helperText('Choose a route to see its parameters. Named routes are only available.')
                        ->options(
                            function () {
                                $excludedRoutes = config('filament-menu-builder.exclude_route_names', []);

                                $routes = collect(Route::getRoutes())
                                    ->filter(function ($route) use ($excludedRoutes) {
                                        $routeName = $route->getName();
                                        if (! $routeName) {
                                            return false;
                                        }

                                        // Check if the route name matches any of the excluded patterns
                                        $isExcluded = false;
                                        foreach ($excludedRoutes as $pattern) {
                                            if (preg_match($pattern, $routeName)) {
                                                $isExcluded = true;

                                                break;
                                            }
                                        }

                                        return ! $isExcluded;
                                    })
                                    ->mapWithKeys(function ($route) {
                                        return [$route->getName() => $route->getName()];
                                    });

                                return $routes;
                            }
                        )
                        ->hidden(fn ($get) => $get('type') != 'route')
                        ->required(fn ($get) => $get('type') == 'route')
                        ->reactive(),
                    KeyValue::make('route_parameters')
                        ->hidden(fn ($get) => $get('type') != 'route')
                        ->helperText(function ($get, $set) {
                            if ($get('route') === null) {
                                return 'Choose a route to see its parameters.';
                            }
                            $route = app('router')->getRoutes()->getByName($get('route'));
                            if (! $route) {
                                return 'Route parameters not found.';
                            }

                            $uri = $route->uri();

                            preg_match_all('/\{(\w+?)\}/', $uri, $matches);
                            $parameters = $matches[1];

                            if (empty($parameters)) {
                                return 'No parameters required for this route. But you can use query parameters.';
                            }

                            $set('route_parameters', array_fill_keys($parameters, null));

                            return 'Route parameters: ' . implode(', ', $parameters) . '. Also, you can use query parameters too.';
                        }),
                    // MODEL
                    Select::make('menuable_type')
                        ->options(
                            array_flip(config('filament-menu-builder.models', []))
                        )
                        ->reactive()
                        ->required(fn ($get) => $get('type') == 'model')
                        ->afterStateUpdated(fn (callable $set) => $set('menuable_id', null))
                        ->hidden(fn ($get) => empty(config('filament-menu-builder.models', [])) || $get('type') != 'model'),
                    Select::make('menuable_id')
                        ->searchable()
                        ->options(fn ($get) => $get('menuable_type')::all()->pluck($get('menuable_type')::getFilamentSearchLabel(), 'id'))
                        ->getSearchResultsUsing(function (string $search, callable $get) {
                            $className = $get('menuable_type');

                            return $className::filamentSearch($search)->pluck($className::getFilamentSearchLabel(), 'id');
                        })
                        ->required(fn ($get) => $get('menuable_type') != null)
                        ->getOptionLabelUsing(fn ($value, $get): ?string => $get('menuable_type')::find($value)?->getFilamentSearchOptionName())
                        ->hidden(fn ($get) => $get('menuable_type') == null),
                ]),
            KeyValue::make('parameters')
                ->helperText('mega_menu, mega_menu_columns'),
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
