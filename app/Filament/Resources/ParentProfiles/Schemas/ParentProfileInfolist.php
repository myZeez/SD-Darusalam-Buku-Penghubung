<?php

namespace App\Filament\Resources\ParentProfiles\Schemas;

use App\Filament\Resources\ParentProfiles\ParentProfileResource;
use App\Models\ParentProfile;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

class ParentProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil Orang Tua')
                    ->description('Identitas akun dan kontak utama orang tua.')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->columns([
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        ImageEntry::make('user.avatar')
                            ->label('Foto Profil')
                            ->imageSize(112)
                            ->circular(),
                        TextEntry::make('user.name')
                            ->label('Nama Akun Orang Tua'),
                        TextEntry::make('user.email')
                            ->label('Alamat Email')
                            ->copyable(),
                        TextEntry::make('user.status')
                            ->label('Status Akun')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger')
                            ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Aktif' : 'Tidak Aktif'),
                        TextEntry::make('phone')
                            ->label('Nomor Telepon')
                            ->copyable()
                            ->placeholder('Belum diisi'),
                    ]),
                Section::make('Data Keluarga')
                    ->description('Informasi ayah, ibu, dan alamat yang terhubung dengan siswa.')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('father_name')
                            ->label('Nama Ayah')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('mother_name')
                            ->label('Nama Ibu')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('address')
                            ->label('Alamat Keluarga')
                            ->placeholder('Belum diisi')
                            ->columnSpanFull(),
                    ]),
                Section::make('Informasi Sistem')
                    ->icon(Heroicon::OutlinedClock)
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y, H:i'),
                        TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d M Y, H:i'),
                    ])
                    ->footerActions([
                        Action::make('editProfile')
                            ->label('Ubah Profil')
                            ->icon(Heroicon::OutlinedPencilSquare)
                            ->url(fn (ParentProfile $record): string => ParentProfileResource::getUrl('edit', ['record' => $record]))
                            ->visible(fn (): bool => auth()->user()?->hasRole('orang_tua') ?? false),
                    ])
                    ->footerActionsAlignment(Alignment::End),
            ]);
    }
}
