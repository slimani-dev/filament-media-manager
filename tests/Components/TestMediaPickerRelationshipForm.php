<?php

namespace Slimani\MediaManager\Tests\Components;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Slimani\MediaManager\Form\MediaPicker;
use Slimani\MediaManager\Tests\Models\User;

class TestMediaPickerRelationshipForm extends Component implements HasForms
{
    use InteractsWithForms;

    public $user;

    public $data;

    public function mount(User $user)
    {
        $this->user = $user;
        $this->form->fill($user->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model($this->user)
            ->schema([
                MediaPicker::make('avatar_id'),
                MediaPicker::make('documents')
                    ->multiple(),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $this->form->saveRelationships();
    }

    public function render()
    {
        return '<div>{{ $this->form }}</div>';
    }
}
