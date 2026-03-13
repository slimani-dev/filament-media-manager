<?php

namespace Slimani\MediaManager\Tests\Components;

use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Slimani\MediaManager\Form\MediaPicker;

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
        $this->user->update($this->form->getState());
        $this->form->saveRelationships();
    }

    public function render()
    {
        return '<div>{{ $this->form }}</div>';
    }
}
