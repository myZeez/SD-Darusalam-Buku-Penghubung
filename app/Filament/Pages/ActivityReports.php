<?php

namespace App\Filament\Pages;

use App\Services\StudentReportService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class ActivityReports extends Page
{
    use InteractsWithSchemas;

    protected static ?string $title = 'Laporan Siswa';

    protected static ?string $navigationLabel = 'Laporan Siswa';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'gmdi-picture-as-pdf-o';

    protected static ?string $slug = 'laporan-aktivitas';

    protected string $view = 'filament.pages.activity-reports';

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->can('view reports') ?? false)
            && (($user?->isAdmin() ?? false) || $user?->hasAnyRole(['guru', 'orang_tua']));
    }

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->hasRole('orang_tua') ? 'Laporan Anak' : 'Laporan Siswa';
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public function canViewScores(): bool
    {
        return ! (auth()->user()?->hasRole('orang_tua') ?? false);
    }

    public function canExportPdf(): bool
    {
        return ! (auth()->user()?->hasRole('orang_tua') ?? false);
    }

    public function mount(): void
    {
        $user = auth()->user();
        $studentIds = $user?->hasRole('orang_tua')
            ? $user->accessibleStudents()->limit(2)->pluck('students.id')
            : collect();

        $this->form->fill([
            'from' => now()->startOfMonth()->toDateString(),
            'to' => today()->toDateString(),
            'student_id' => $studentIds->count() === 1 ? $studentIds->first() : null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Filter Laporan')
                    ->description(fn (): string => auth()->user()?->hasRole('orang_tua')
                        ? 'Pilih periode dan anak Anda. Laporan hanya menampilkan data anak yang terhubung ke akun ini.'
                        : 'Pilih periode dan siswa. Data tetap mengikuti hak akses akun Anda.')
                    ->icon('gmdi-filter-alt-o')
                    ->columns(['md' => 3])
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->live()
                            ->required(),
                        DatePicker::make('to')
                            ->label('Sampai Tanggal')
                            ->afterOrEqual('from')
                            ->live()
                            ->required(),
                        Select::make('student_id')
                            ->label('Siswa')
                            ->options(fn (): array => auth()->user()
                                ->accessibleStudents()
                                ->with('class')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($student): array => [
                                    $student->id => sprintf(
                                        '%s (%s)',
                                        $student->name,
                                        $student->class?->name ?? 'belum ada kelas',
                                    ),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->placeholder(auth()->user()?->hasRole('orang_tua') ? 'Semua anak saya' : 'Semua siswa yang dapat diakses'),
                    ]),
            ]);
    }

    public function reportUrl(): string
    {
        return route('reports.activity-summary', array_filter([
            'from' => $this->data['from'] ?? null,
            'to' => $this->data['to'] ?? null,
            'student_id' => $this->data['student_id'] ?? null,
        ], fn ($value): bool => filled($value)));
    }

    /** @return array<string, mixed> */
    public function reportData(): array
    {
        return app(StudentReportService::class)->build(
            auth()->user(),
            $this->data['from'] ?? now()->startOfMonth()->toDateString(),
            $this->data['to'] ?? today()->toDateString(),
            filled($this->data['student_id'] ?? null) ? (int) $this->data['student_id'] : null,
        );
    }
}
