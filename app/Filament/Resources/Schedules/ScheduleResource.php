<?php

namespace App\Filament\Resources\Schedules;

use App\Filament\Resources\Schedules\Pages\CreateSchedule;
use App\Filament\Resources\Schedules\Pages\EditSchedule;
use App\Filament\Resources\Schedules\Pages\ListSchedules;
use App\Filament\Resources\Schedules\Pages\ViewSchedule;
use App\Filament\Resources\Schedules\Schemas\ScheduleForm;
use App\Filament\Resources\Schedules\Schemas\ScheduleInfolist;
use App\Filament\Resources\Schedules\Tables\SchedulesTable;
use App\Models\Schedule;
use App\Models\ScheduleResponse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $modelLabel = 'Agenda Kegiatan';

    protected static ?string $pluralModelLabel = 'Agenda Kegiatan';

    protected static ?string $navigationLabel = 'Agenda Kegiatan';

    protected static string|\UnitEnum|null $navigationGroup = 'Informasi';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-calendar-month-o';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return $query;
        }

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $classes = $user->hasRole('guru') ? $user->managedClasses() : $user->accessibleClasses();

        return $query->where(function (Builder $query) use ($classes): void {
            $query
                ->whereNull('class_id')
                ->orWhereIn('class_id', $classes->select('classes.id'));
        });
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can('view schedules') ?? false)
            && ! ($user?->hasRole('siswa') ?? false);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if (! ($user?->can('view schedules') ?? false)) {
            return false;
        }

        $classes = $user->hasRole('guru') ? $user->managedClasses() : $user->accessibleClasses();

        return $user->isAdmin() || blank($record->class_id) || $classes->whereKey($record->class_id)->exists();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->can('manage schedules') ?? false)
            && (($user?->isAdmin() ?? false) || $user?->managedClasses()->exists());
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if (! ($user?->can('manage schedules') ?? false)) {
            return false;
        }

        return $user->isAdmin()
            || ($record->created_by === $user->id
                && filled($record->class_id)
                && $user->managedClasses()->whereKey($record->class_id)->exists());
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function saveParentResponse(Schedule $schedule, array $data): ScheduleResponse
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('orang_tua') && static::canView($schedule), 403);

        $validated = Validator::make($data, [
            'response' => ['required', Rule::in(['attending', 'unavailable', 'reschedule'])],
            'proposed_date' => ['nullable', 'required_if:response,reschedule', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        if ($validated['response'] !== 'reschedule') {
            $validated['proposed_date'] = null;
        }

        return ScheduleResponse::updateOrCreate(
            [
                'schedule_id' => $schedule->getKey(),
                'user_id' => $user->getKey(),
            ],
            $validated,
        );
    }

    public static function form(Schema $schema): Schema
    {
        return ScheduleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ScheduleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchedulesTable::configure($table);
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
            'index' => ListSchedules::route('/'),
            'create' => CreateSchedule::route('/create'),
            'view' => ViewSchedule::route('/{record}'),
            'edit' => EditSchedule::route('/{record}/edit'),
        ];
    }
}
