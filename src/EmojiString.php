<?php

namespace Astrotomic\Twemoji;

use Astrotomic\Twemoji\Concerns\Configurable;
use Closure;
use Spatie\Emoji\Emoji;

/**
 * @internal
 */
class EmojiString
{
    use Configurable;

    protected string $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function toMarkdown(?Closure $alt = null): string
    {
        return $this->replace('![%{alt}](%{src})', $alt);
    }

    public function toHtml(?Closure $alt = null, array $attributes = []): string
    {
        $attributes = array_merge([
            'width' => 72,
            'height' => 72,
            'loading' => 'lazy',
            'class' => 'twemoji',
        ], $attributes);

        $attrs = implode(' ', array_map(
            fn (string $key, string $value): string => "{$key}=\"{$value}\"",
            array_keys($attributes),
            array_values($attributes)
        ));

        return $this->replace('<img src="%{src}" alt="%{alt}" '.$attrs.' />', $alt);
    }

    protected function replace(string $replacement, ?Closure $alt = null): string
    {
        $text = $this->text;

        foreach (array_chunk(Emoji::all(), 1000) as $emojis) {
            $text = preg_replace_callback(
                '/('.implode('|', array_map('preg_quote', $emojis)).')/',
                fn (array $matches): string => str_replace(
                    ['%{alt}', '%{src}'],
                    [
                        $alt
                            ? $alt($matches[0])
                            : $matches[0],
                        Twemoji::emoji($matches[0])
                            ->base($this->base)
                            ->type($this->type)
                            ->url(),
                    ],
                    $replacement
                ),
                $text
            );
        }

        return $text;
    }
}
