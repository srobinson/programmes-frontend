<?php
declare(strict_types = 1);

namespace App\Twig;

use Twig_Extension;
use Twig_Function;

class AdditionalJavascriptExtension extends Twig_Extension
{
    private $buttons = [];
    private $showPopup = false;

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new Twig_Function('add_button', [$this, 'addButton']),
            new Twig_Function('get_buttons', [$this, 'getButtons']),
            new Twig_Function('add_popup', [$this, 'addPopup']),
            new Twig_Function('show_popup', [$this, 'showPopup']),
        ];
    }

    public function addButton(string $elementId, string $id, string $type, string $contextId, string $title, ?string $profile = null)
    {
        $button = [
            'element_id' => $elementId,
            'id' => $id,
            'type' => $type,
            'context_id' => $contextId,
            'title' => $title,
        ];
        if ($profile) {
            $button['profile'] = $profile;
        }
        $this->buttons[] = $button;
    }

    public function getButtons(): ?array
    {
        return $this->buttons;
    }

    public function addPopup()
    {
        $this->showPopup = true;
    }

    public function showPopup()
    {
        return $this->showPopup;
    }
}
