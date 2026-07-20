<?php

namespace App\Filament\Resources\ActivityComments\Tables;

use App\Models\HomeActivity;
use App\Models\SchoolActivity;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityCommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->whereNull('parent_id')
                ->with([
                    'activity.student.class.teacher.user',
                    'activity.student.parent.user',
                    'latestReply.user',
                    'user',
                ])
                ->withCount('replies'))
            ->columns([
                ViewColumn::make('conversation')
                    ->label('Percakapan')
                    ->view('filament.tables.columns.activity-conversation')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where(function (Builder $query) use ($search): void {
                            $query
                                ->where('comment', 'like', "%{$search}%")
                                ->orWhereHas('replies', fn (Builder $query): Builder => $query
                                    ->where('comment', 'like', "%{$search}%"))
                                ->orWhereHasMorph(
                                    'activity',
                                    [SchoolActivity::class, HomeActivity::class],
                                    fn (Builder $query): Builder => $query->whereHas(
                                        'student',
                                        fn (Builder $query): Builder => $query
                                            ->where('name', 'like', "%{$search}%")
                                            ->orWhereHas('parent.user', fn (Builder $query): Builder => $query
                                                ->where('name', 'like', "%{$search}%")),
                                    ),
                                );
                        })),
            ])
            ->recordUrl(null)
            ->filters([])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('Belum ada percakapan')
            ->emptyStateDescription('Mulai pesan baru untuk membahas perkembangan murid bersama orang tua.')
            ->emptyStateIcon('gmdi-forum-o');
    }
}
