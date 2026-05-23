<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages\ListWallets;
use App\Filament\Resources\WalletResource\Pages\ViewWallet;
use App\Models\Wallet;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('owner_type'),
            TextEntry::make('owner_id'),
            TextEntry::make('balance')->money('IDR'),
            TextEntry::make('currency'),
            TextEntry::make('status')->badge(),
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('updated_at')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('owner_type')->searchable()->sortable(),
                TextColumn::make('owner_id')->sortable(),
                TextColumn::make('balance')->money('IDR')->sortable(),
                TextColumn::make('currency')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWallets::route('/'),
            'view' => ViewWallet::route('/{record}'),
        ];
    }
}
