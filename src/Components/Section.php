<?php

namespace Slimani\MediaManager\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section as BaseSection;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;

class Section extends BaseSection
{
    /** @var view-string */
    protected string $view = 'media-manager::filament.components.section';

    const BEFORE_HEADER_SCHEMA_KEY = 'before_header';

    const HEADING_CONTENT_SCHEMA_KEY = 'heading_content';

    public function heading(string|array|Htmlable|Closure|null $heading = null): static
    {
        if (is_array($heading)) {
            $this->childComponents($heading, static::HEADING_CONTENT_SCHEMA_KEY);

            return $this;
        }

        return parent::heading($heading);
    }

    /**
     * @param  array<Component | Action | ActionGroup | string | Htmlable> | Schema | Component | Action | ActionGroup | string | Htmlable | Closure | null  $components
     */
    public function beforeHeader(array|Schema|Component|Action|ActionGroup|string|Htmlable|Closure|null $components): static
    {
        $this->childComponents($components, static::BEFORE_HEADER_SCHEMA_KEY);

        return $this;
    }

    protected function makeChildSchema(string $key): Schema
    {
        $schema = parent::makeChildSchema($key);

        if ($key === static::BEFORE_HEADER_SCHEMA_KEY) {
            $schema->alignStart();
        }

        return $schema;
    }

    protected function configureChildSchema(Schema $schema, string $key): Schema
    {
        $schema = parent::configureChildSchema($schema, $key);

        if ($key === static::BEFORE_HEADER_SCHEMA_KEY || $key === static::HEADING_CONTENT_SCHEMA_KEY) {
            $schema
                ->inline()
                ->embeddedInParentComponent();

            $schema
                ->modifyActionsUsing(fn (Action $action) => $action
                    ->defaultSize(Size::Small)
                    ->defaultView(Action::ICON_BUTTON_VIEW))
                ->modifyActionGroupsUsing(fn (ActionGroup $actionGroup) => $actionGroup->defaultSize(Size::Small));
        }

        return $schema;
    }
}
