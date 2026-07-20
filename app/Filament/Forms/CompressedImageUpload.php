<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\FileUpload;

class CompressedImageUpload
{
    public static function make(string $name, string $label, string $directory, int $maximumDimension = 1600): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->acceptedFileTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
            ])
            ->imageEditor()
            ->automaticallyResizeImagesToWidth((string) $maximumDimension)
            ->automaticallyResizeImagesToHeight((string) $maximumDimension)
            ->automaticallyResizeImagesMode('contain')
            ->automaticallyUpscaleImagesWhenResizing(false)
            ->maxSize(5120)
            ->directory($directory)
            ->helperText("Foto akan disesuaikan maksimal {$maximumDimension}px sebelum diunggah. Ukuran file awal maksimal 5 MB.");
    }
}
