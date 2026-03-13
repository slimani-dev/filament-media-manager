<?php

namespace Slimani\MediaManager\Tests\Components;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Slimani\MediaManager\Form\MediaPicker;

class TestMediaFileUploadForm extends Component implements HasForms
{
    use InteractsWithForms;

    public $data;

    public function mount()
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                MediaPicker::make('avatar_id'),
            ])
            ->statePath('data');
    }

    public function render()
    {
        return '<div>{{ $this->form }}</div>';
    }
}
