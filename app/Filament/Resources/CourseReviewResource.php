<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseReviewResource\Pages\ListCourseReviews;
use App\Filament\Resources\CourseReviewResource\Pages\ViewCourseReview;
use App\Models\CourseReview;
use App\Services\CourseReviewService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CourseReviewResource extends Resource
{
    protected static ?string $model = CourseReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('user.name')->label('User'),
            TextEntry::make('course.title')->label('Course'),
            TextEntry::make('rating')->suffix('/5'),
            IconEntry::make('is_published')->boolean(),
            TextEntry::make('comment')->columnSpanFull(),
            TextEntry::make('edited_at')->dateTime(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('course.title')->label('Course')->searchable(),
                TextColumn::make('rating')->suffix('/5')->sortable(),
                TextColumn::make('comment')->limit(80)->searchable(),
                IconColumn::make('is_published')->boolean()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('hide')
                    ->label('Hide')
                    ->requiresConfirmation()
                    ->visible(fn (CourseReview $record) => $record->is_published)
                    ->action(fn (CourseReview $record) => app(CourseReviewService::class)->hide($record)),
                Action::make('publish')
                    ->label('Publish')
                    ->requiresConfirmation()
                    ->visible(fn (CourseReview $record) => ! $record->is_published)
                    ->action(fn (CourseReview $record) => app(CourseReviewService::class)->publish($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseReviews::route('/'),
            'view' => ViewCourseReview::route('/{record}'),
        ];
    }
}
