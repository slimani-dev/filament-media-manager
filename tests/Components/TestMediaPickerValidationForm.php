<?php

namespace Slimani\MediaManager\Tests\Components;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Slimani\MediaManager\Form\MediaPicker;

class TestMediaPickerValidationForm extends Component implements HasForms
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
                MediaPicker::make('avatar_id')
                    ->image()
                    ->required(),
                MediaPicker::make('documents')
                    ->multiple()
                    ->maxFiles(2),
                MediaPicker::make('contract_id')
                    ->rule(fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                        if (filled($value) && (string) $value === '999') {
                            $fail('This contract is not allowed.');
                        }
                    }),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $this->form->getState();
    }

    public function render()
    {
        return '<div>{{ $this->form }}</div>';
    }
}
