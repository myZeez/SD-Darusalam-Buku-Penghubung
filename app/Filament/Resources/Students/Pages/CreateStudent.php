<?php

namespace App\Filament\Resources\Students\Pages;

use App\Actions\Students\SaveStudentWithFamily;
use App\Filament\Resources\Students\StudentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    public function mount(): void
    {
        parent::mount();

        $classId = request()->integer('class_id');

        if ($classId > 0 && StudentResource::canManageClass($classId)) {
            $this->form->fillPartially(['class_id' => $classId], ['class_id']);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        abort_unless(StudentResource::canManageClass((int) $data['class_id']), 403);

        return app(SaveStudentWithFamily::class)->create(
            $data,
            allowClassFallback: auth()->user()?->isAdmin() ?? false,
        );
    }
}
