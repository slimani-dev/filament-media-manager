<?php

namespace Slimani\MediaManager\Infolists\Components;

use Closure;
use Filament\Infolists\Components\RepeatableEntry as BaseRepeatableEntry;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Js;

class RepeatableEntry extends BaseRepeatableEntry
{
    protected mixed $currentItem = null;

    protected int|string|null $currentItemKey = null;

    public function getItem(): mixed
    {
        return $this->currentItem;
    }

    public function getItemKey(): int|string|null
    {
        return $this->currentItemKey;
    }

    public function getItems(): array
    {
        // Filament 5.9+ builds and caches the item schemas through getDefaultChildSchemas().
        if (property_exists(BaseRepeatableEntry::class, 'cachedItemsState')) {
            return parent::getItems();
        }

        return $this->getDefaultChildSchemas();
    }

    /**
     * Builds one schema per item, evaluating the schema closure while that item
     * is current so `$component->getItem()` can be used inside it.
     *
     * @return array<Schema>
     */
    public function getDefaultChildSchemas(): array
    {
        $state = method_exists($this, 'normalizeItemsState')
            ? $this->normalizeItemsState($this->getState() ?? [])
            : ($this->getState() ?? []);

        if (property_exists($this, 'cachedItemsState')) {
            $this->cachedItemsState = $state;
        }

        $containers = [];

        foreach ($state as $itemKey => $itemData) {
            $this->currentItem = $itemData;
            $this->currentItemKey = $itemKey;

            $components = $this->evaluate($this->childComponents['default'] ?? []) ?? [];

            $container = Schema::make($this->getLivewire())
                ->parentComponent($this)
                ->components($components)
                ->statePath($itemKey)
                ->inlineLabel(false);

            if ($itemData instanceof Model) {
                $container->record($itemData);
            } elseif (is_array($itemData) || is_object($itemData)) {
                $container->constantState($itemData);
            }

            $containers[$itemKey] = $container;
        }

        $this->currentItem = null;
        $this->currentItemKey = null;

        return $containers;
    }

    public function callMountedAction(array $arguments = []): mixed
    {
        if (isset($arguments['itemKey'])) {
            $this->currentItemKey = $arguments['itemKey'];
            $this->currentItem = $this->getState()[$this->currentItemKey] ?? null;
        }

        return parent::callMountedAction($arguments);
    }

    public function toEmbeddedHtml(): string
    {
        if ($this->isTable()) {
            return $this->toEmbeddedTableHtml();
        }

        $items = $this->getItems();

        $attributes = $this->getExtraAttributeBag()
            ->class([
                'fi-in-repeatable',
                'fi-contained' => $this->isContained(),
            ]);

        if (empty($items)) {
            $attributes = $attributes
                ->merge([
                    'x-tooltip' => filled($tooltip = $this->getEmptyTooltip())
                        ? '{
                            content: '.Js::from($tooltip).',
                            theme: $store.theme,
                            allowHTML: '.Js::from($tooltip instanceof Htmlable).',
                        }'
                        : null,
                ], escape: false);

            $placeholder = $this->getPlaceholder();

            ob_start(); ?>

            <div <?= $attributes->toHtml() ?>>
                <?php if (filled($placeholder)) { ?>
                    <p class="fi-in-placeholder">
                        <?= e($placeholder) ?>
                    </p>
                <?php } ?>
            </div>

            <?php return $this->wrapEmbeddedHtml(ob_get_clean());
        }

        $attributes = $attributes->grid($this->getGridColumns());

        ob_start(); ?>

        <ul <?= $attributes->toHtml() ?>>
            <?php foreach ($items as $itemKey => $item) {
                $this->currentItemKey = $itemKey;
                $this->currentItem = $this->getState()[$itemKey] ?? null;

                $url = $this->getUrl();
                $shouldOpenUrlInNewTab = $this->shouldOpenUrlInNewTab();
                $action = $this->getAction();
                ?>
                <li class="fi-in-repeatable-item" wire:key="<?= $this->getLivewire()->getId() ?>.items.<?= (string) $itemKey ?>">
                    <?php if ($url) { ?>
                        <a 
                            <?= \Filament\Support\generate_href_html($url, $shouldOpenUrlInNewTab)->toHtml() ?> 
                            class="block w-full h-full"
                        >
                            <?= $item->toHtml() ?>
                        </a>
                    <?php } elseif ($action) { ?>
                        <button 
                            type="button" 
                            wire:click="mountAction('<?= $action->getName() ?>', { itemKey: <?= Js::from((string) $itemKey) ?> })"
                            class="block w-full h-full text-start"
                        >
                            <?= $item->toHtml() ?>
                        </button>
                    <?php } else { ?>
                        <?= $item->toHtml() ?>
                    <?php } ?>
                </li>
            <?php }

            $this->currentItemKey = null;
        $this->currentItem = null;
        ?>
        </ul>

        <?php return $this->wrapEmbeddedHtml(ob_get_clean());
    }
}
