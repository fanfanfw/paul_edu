<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlatformSettingResource\Pages\EditPlatformSetting;
use App\Filament\Resources\PlatformSettingResource\Pages\ListPlatformSettings;
use App\Models\PlatformSetting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlatformSettingResource extends Resource
{
    protected static ?string $model = PlatformSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $recordTitleAttribute = 'key';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('key', 'mentor_commission_rate');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')
                ->disabled()
                ->dehydrated(false),
            TextInput::make('value')
                ->label('Mentor commission rate')
                ->integer()
                ->minValue(0)
                ->maxValue(100)
                ->required()
                ->helperText('Percentage of course price credited to mentor for future purchases only.'),
            TextInput::make('type')
                ->disabled()
                ->dehydrated(false),
            TextInput::make('description')
                ->disabled()
                ->dehydrated(false),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('key'),
            TextEntry::make('value')->suffix('%'),
            TextEntry::make('type'),
            TextEntry::make('description'),
            TextEntry::make('updated_at')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable(),
                TextColumn::make('value')->suffix('%')->sortable(),
                TextColumn::make('description')->limit(80),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformSettings::route('/'),
            'edit' => EditPlatformSetting::route('/{record}/edit'),
        ];
    }
}
