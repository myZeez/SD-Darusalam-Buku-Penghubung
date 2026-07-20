<?php

namespace App\Filament\Resources\ActivityComments\Pages;

use App\Filament\Resources\ActivityComments\ActivityCommentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivityComments extends ListRecords
{
    protected static string $resource = ActivityCommentResource::class;

    public function getSubheading(): ?string
    {
        return 'Pilih nama orang tua untuk membuka riwayat percakapan yang terhubung dengan murid.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Pesan Baru')
                ->icon('gmdi-add-comment-o')
                ->visible(fn (): bool => ActivityCommentResource::canCreate()),
        ];
    }
}
