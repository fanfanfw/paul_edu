<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('order_number'),
            TextEntry::make('user.name')->label('Buyer'),
            TextEntry::make('course.title')->label('Course'),
            TextEntry::make('mentor.name')->label('Mentor'),
            TextEntry::make('total_amount')->money('IDR'),
            TextEntry::make('commission_rate_snapshot')->suffix('%'),
            TextEntry::make('mentor_amount')->money('IDR'),
            TextEntry::make('platform_amount')->money('IDR'),
            TextEntry::make('status')->badge(),
            TextEntry::make('paid_at')->dateTime(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Buyer')->searchable(),
                TextColumn::make('course_title_snapshot')->label('Course')->searchable(),
                TextColumn::make('mentor.name')->label('Mentor')->searchable(),
                TextColumn::make('total_amount')->money('IDR')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('paid_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
