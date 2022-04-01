<?php

return celostad\Fixer\Config::make()
    ->in(__DIR__)
    ->preset(
        new celostad\Fixer\Presets\PrettyLaravel()
    )
    ->out();
