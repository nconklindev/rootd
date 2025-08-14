<?php

namespace App;

use Illuminate\Support\Collection;

class Inspiring extends \Illuminate\Foundation\Inspiring
{
    /**
     * Get an inspiring quote.
     *
     * Taylor & Dayle made this commit from Jungfraujoch. (11,333 ft.)
     *
     * May McGinnis always control the board. #LaraconUS2015
     *
     * RIP Charlie - Feb 6, 2018
     */
    public static function quote(): string
    {
        return static::formatForConsole(static::quotes()->random());
    }

    public static function quotes(): Collection
    {
        return new Collection([
            "Where we're going, we don't need roads - Back to the Future (1958)",
            'After all, tomorrow is another day! - Gone with the Wind (1939)',
            "You had me at 'hello.' - Jerry Maguire (1976)",
            "Here's looking at you, kid. - Casablanca (1942)",
            'Elementary, my dear Watson. - The Adventures of Sherlock Holmes (1939)',
            "You can't handle the truth! - A Few Good Men (1992)",

        ]);
    }
}
