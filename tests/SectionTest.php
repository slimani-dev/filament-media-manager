<?php

namespace Slimani\MediaManager\Tests;

use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Slimani\MediaManager\Components\Section;

uses(TestCase::class);

class TestSectionComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->heading([
                        Action::make('headingAction')
                            ->label('Heading Action'),
                    ])
                    ->beforeHeader([
                        Action::make('goUp')
                            ->label('Up Button')
                            ->icon('heroicon-m-arrow-left'),
                    ])
                    ->afterHeader([
                        Action::make('otherAction')
                            ->label('Other Action'),
                    ]),
            ]);
    }

    public function render()
    {
        return '<div>{{ $this->form }}</div>';
    }
}

it('renders section with before header content and component heading', function () {
    Livewire::test(TestSectionComponent::class)
        ->assertSee('Heading Action')
        ->assertSee('Up Button')
        ->assertSee('Other Action')
        ->assertSeeInOrder(['Up Button', 'Heading Action', 'Other Action']);
});
