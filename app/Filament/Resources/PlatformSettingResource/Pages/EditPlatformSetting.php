<?php

namespace App\Filament\Resources\PlatformSettingResource\Pages;

use App\Filament\Resources\PlatformSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditPlatformSetting extends EditRecord
{
    protected static string $resource = PlatformSettingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['type'] = 'integer';
        $data['description'] = 'Default mentor commission percentage';

        return $data;
    }
}
