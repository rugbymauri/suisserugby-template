<?php

return [
    'suisserugby:migrate' => [
        'class' => \MaurizioMonticelli\SuisseRugby\Command\MigrateCommand::class,
    ],
    'suisserugby:update' => [
        'class' => \MaurizioMonticelli\SuisseRugby\Command\UpdateCommand::class,
    ],
    'suisserugby:update-link' => [
        'class' => \MaurizioMonticelli\SuisseRugby\Command\UpdateImageLinksCommand::class,
    ],
    'suisserugby:update-orient' => [
        'class' => \MaurizioMonticelli\SuisseRugby\Command\UpdateImageOrientCommand::class,
    ],
];
