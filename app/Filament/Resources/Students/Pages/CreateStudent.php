<?php

namespace App\Filament\Resources\Students\Pages;

use App\Actions\Students\SaveStudentWithFamily;
use App\Filament\Resources\Students\StudentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveStudentWithFamily::class)->create($data);
    }
}
