<?php

namespace App\Filament\Resources;

use App\Enums\CourseStatus;
use App\Filament\Resources\CourseResource\Pages\ListCourses;
use App\Filament\Resources\CourseResource\Pages\ViewCourse;
use App\Models\Course;
use App\Services\CourseStatusService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'title';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title'),
            TextEntry::make('mentor.name')->label('Mentor'),
            TextEntry::make('category.name')->label('Category'),
            TextEntry::make('price')->money('IDR'),
            TextEntry::make('status')->badge(),
            TextEntry::make('short_description')->columnSpanFull(),
            TextEntry::make('description')->columnSpanFull(),
            TextEntry::make('published_at')->dateTime(),
            TextEntry::make('archived_at')->dateTime(),
            TextEntry::make('deleted_by_mentor_at')->dateTime(),
            TextEntry::make('hidden_by_admin_at')->dateTime(),
            TextEntry::make('created_at')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('mentor.name')->label('Mentor')->searchable(),
                TextColumn::make('category.name')->label('Category')->sortable(),
                TextColumn::make('price')->money('IDR')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('archive')
                    ->requiresConfirmation()
                    ->visible(fn (Course $record) => $record->status !== CourseStatus::Archived)
                    ->action(fn (Course $record) => app(CourseStatusService::class)->archive($record)),
                Action::make('hide')
                    ->label('Hide by admin')
                    ->requiresConfirmation()
                    ->visible(fn (Course $record) => $record->status !== CourseStatus::HiddenByAdmin)
                    ->action(fn (Course $record) => app(CourseStatusService::class)->hideByAdmin($record)),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourses::route('/'),
            'view' => ViewCourse::route('/{record}'),
        ];
    }
}
