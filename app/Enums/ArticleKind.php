<?php

namespace App\Enums;

/**
 * Parts of Om NPF: the welcome text, the topics (cards) and the situations
 * in "Känner du igen dig?".
 */
enum ArticleKind: string
{
    case Intro = 'intro';
    case Topic = 'topic';
    case Situation = 'situation';

    public function label(): string
    {
        return match ($this) {
            self::Intro => 'Välkomsttext',
            self::Topic => 'Ämne',
            self::Situation => 'Känner du igen dig?',
        };
    }
}
