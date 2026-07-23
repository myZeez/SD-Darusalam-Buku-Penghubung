<?php

namespace App\Filament\Resources\ActivityComments;

use App\Filament\Resources\ActivityComments\Pages\CreateActivityComment;
use App\Filament\Resources\ActivityComments\Pages\EditActivityComment;
use App\Filament\Resources\ActivityComments\Pages\ListActivityComments;
use App\Filament\Resources\ActivityComments\Pages\ViewActivityComment;
use App\Filament\Resources\ActivityComments\Schemas\ActivityCommentForm;
use App\Filament\Resources\ActivityComments\Schemas\ActivityCommentInfolist;
use App\Filament\Resources\ActivityComments\Tables\ActivityCommentsTable;
use App\Models\ActivityComment;
use App\Models\HomeActivity;
use App\Models\SchoolActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityCommentResource extends Resource
{
    protected static ?string $model = ActivityComment::class;

    protected static ?string $modelLabel = 'Topik Diskusi';

    protected static ?string $pluralModelLabel = 'Topik Diskusi';

    protected static ?string $navigationLabel = 'Topik Diskusi';

    protected static string|\UnitEnum|null $navigationGroup = 'Buku Penghubung';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-forum-o';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where(function (Builder $query) use ($user): void {
                    $query
                        ->where('activity_type', SchoolActivity::class)
                        ->whereIn(
                            'activity_id',
                            SchoolActivity::query()
                                ->select('id')
                                ->whereIn('student_id', $user->accessibleStudents()->select('students.id')),
                        );
                })
                ->orWhere(function (Builder $query) use ($user): void {
                    $studentQuery = $user->hasRole('guru')
                        ? $user->managedStudents()
                        : $user->accessibleStudents();

                    $query
                        ->where('activity_type', HomeActivity::class)
                        ->whereIn(
                            'activity_id',
                            HomeActivity::query()
                                ->select('id')
                                ->whereIn('student_id', $studentQuery->select('students.id')),
                        );
                });
        });
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can('view comments') ?? false)
            && ! ($user?->hasRole('siswa') ?? false);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->can('view comments') ?? false)
            && $user->canAccessActivity($record->activity_type, $record->activity_id);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage comments') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if (! ($user?->can('manage comments') ?? false)) {
            return false;
        }

        return ! $record->threadRoot()->isClosed()
            && ($user->isAdmin() || ($record->user_id === $user->id && static::canView($record)));
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function replyTo(ActivityComment $comment, string $message): ActivityComment
    {
        $user = auth()->user();
        $root = $comment->threadRoot();

        abort_unless($user && static::canCreate() && static::canView($root) && ! $root->isClosed(), 403);

        $reply = ActivityComment::create([
            'parent_id' => $root->getKey(),
            'activity_type' => $root->activity_type,
            'activity_id' => $root->activity_id,
            'user_id' => $user->getKey(),
            'comment' => $message,
        ]);

        $root->touch();

        return $reply;
    }

    public static function form(Schema $schema): Schema
    {
        return ActivityCommentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ActivityCommentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivityCommentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityComments::route('/'),
            'create' => CreateActivityComment::route('/create'),
            'view' => ViewActivityComment::route('/{record}'),
            'edit' => EditActivityComment::route('/{record}/edit'),
        ];
    }
}
