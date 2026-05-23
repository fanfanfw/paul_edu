<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages\ListWalletTransactions;
use App\Filament\Resources\WalletTransactionResource\Pages\ViewWalletTransaction;
use App\Models\WalletTransaction;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('wallet_id'),
            TextEntry::make('owner_type'),
            TextEntry::make('owner_id'),
            TextEntry::make('type')->badge(),
            TextEntry::make('direction')->badge(),
            TextEntry::make('amount')->money('IDR'),
            TextEntry::make('balance_before')->money('IDR'),
            TextEntry::make('balance_after')->money('IDR'),
            TextEntry::make('status')->badge(),
            TextEntry::make('reference_type'),
            TextEntry::make('reference_id'),
            TextEntry::make('description')->columnSpanFull(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('wallet_id')->sortable(),
                TextColumn::make('owner_type')->searchable()->sortable(),
                TextColumn::make('owner_id')->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('direction')->badge()->sortable(),
                TextColumn::make('amount')->money('IDR')->sortable(),
                TextColumn::make('balance_before')->money('IDR')->sortable(),
                TextColumn::make('balance_after')->money('IDR')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWalletTransactions::route('/'),
            'view' => ViewWalletTransaction::route('/{record}'),
        ];
    }
}
