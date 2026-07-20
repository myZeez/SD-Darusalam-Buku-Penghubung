<?php

namespace App\Filament\Resources\LessonPeriods\Pages;

use App\Filament\Resources\LessonPeriods\LessonPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLessonPeriods extends ListRecords
{
    protected static string $resource = LessonPeriodResource::class;

    public function getSubheading(): ?string
    {
        return 'Susun blok waktu sekolah. Mata pelajaran ditempatkan sesudahnya melalui menu Jadwal Pelajaran.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Susun Waktu Sekolah')->icon('gmdi-add-circle-o')];
    }
}
