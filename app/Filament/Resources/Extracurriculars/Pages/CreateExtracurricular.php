<?php

namespace App\Filament\Resources\Extracurriculars\Pages;

use App\Filament\Resources\Extracurriculars\ExtracurricularResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateExtracurricular extends CreateRecord
{
    protected static string $resource = ExtracurricularResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        abort_unless(ExtracurricularResource::canCreate(), 403);

        return $data;
    }
}
