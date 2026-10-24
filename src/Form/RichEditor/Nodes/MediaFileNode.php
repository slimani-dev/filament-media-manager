<?php

namespace Slimani\MediaManager\Form\RichEditor\Nodes;

use Illuminate\Support\Facades\View;
use Slimani\MediaManager\MediaManagerPlugin;
use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class MediaFileNode extends Node
{
    public static $name = 'mediaFile';

    public function addAttributes(): array
    {
        return [
            'id' => [
                'default' => null,
                'parseHTML' => fn ($DOMNode) => $DOMNode->getAttribute('data-id'),
                'renderHTML' => fn ($attributes) => [
                    'data-id' => $attributes->id,
                ],
            ],
            'name' => [
                'default' => null,
                'parseHTML' => fn ($DOMNode) => $DOMNode->getAttribute('data-name'),
                'renderHTML' => fn ($attributes) => [
                    'data-name' => $attributes->name,
                ],
            ],
            'extension' => [
                'default' => null,
                'parseHTML' => fn ($DOMNode) => $DOMNode->getAttribute('data-extension'),
                'renderHTML' => fn ($attributes) => [
                    'data-extension' => $attributes->extension,
                ],
            ],
            'size' => [
                'default' => null,
                'parseHTML' => fn ($DOMNode) => $DOMNode->getAttribute('data-size'),
                'renderHTML' => fn ($attributes) => [
                    'data-size' => $attributes->size,
                ],
            ],
        ];
    }

    public function parseHTML(): array
    {
        return [
            [
                'tag' => 'div[data-type="'.self::$name.'"]',
            ],
        ];
    }

    public function renderHTML($node, $HTMLAttributes = []): array
    {
        /** @var MediaManagerPlugin $plugin */
        $plugin = filament('media-manager');
        $fileModel = $plugin->getFileModel();
        $file = $fileModel::find($node->attrs->id);
        $url = $file?->getUrl() ?? '#';

        $html = View::make('media-manager::rich-editor.nodes.media-file', [
            'id' => $node->attrs->id,
            'name' => $node->attrs->name,
            'extension' => $node->attrs->extension,
            'size' => $node->attrs->size,
            'url' => $url,
        ])->render();

        return [
            'div',
            HTML::mergeAttributes($HTMLAttributes, [
                'data-type' => self::$name,
                'class' => 'media-file-node-container',
            ]),
            ['raw' => $html],
        ];
    }
}
