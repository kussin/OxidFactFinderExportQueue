<?php

declare(strict_types=1);

/**
 * Cronjob declarations for the Kussin Cronjob Manager.
 *
 * Schedules are the ones OXID 6 production actually runs, observed from its
 * access logs. This is the shop's highest-frequency pipeline: the queue is
 * processed every two minutes and the FACT-Finder CSV is written twice every
 * half hour, once per channel.
 */

return [
    [
        'code'        => 'ffqueue_process',
        'title'       => 'FACT-Finder: process export queue',
        'description' => 'Walks pending rows in wmdk_ff_export_queue and builds each article\'s export record — prices, from-price, category path, attributes, cloned attributes, SEO URL, stock, topseller flag.',
        'command'     => 'wmdkffexport:cron:queue',
        'arguments'   => '--cron',
        'schedule'    => '*/2 * * * *',
        'timeout'     => 900,
        'active'      => true,
    ],
    [
        'code'        => 'ffqueue_export_de',
        'title'       => 'FACT-Finder: export CSV (DE)',
        'description' => 'Dumps the queue into the pipe-delimited FACT-Finder CSV for the German channel.',
        'command'     => 'wmdkffexport:export:factfinder',
        'arguments'   => '--channel=wh1_live_de --lang=0 --shop-id=1 --cron',
        'schedule'    => '15,45 * * * *',
        'timeout'     => 900,
        'priority'    => 90,
        'active'      => true,
    ],
    [
        'code'        => 'ffqueue_export_en',
        'title'       => 'FACT-Finder: export CSV (EN)',
        'description' => 'The same for the English channel. Production staggers it two minutes after the German run.',
        'command'     => 'wmdkffexport:export:factfinder',
        'arguments'   => '--channel=wh1_live_en --lang=1 --shop-id=1 --cron',
        'schedule'    => '17,47 * * * *',
        'timeout'     => 900,
        'priority'    => 95,
        'active'      => true,
    ],
    [
        'code'        => 'ffqueue_reset',
        'title'       => 'FACT-Finder: re-queue changed articles',
        'description' => 'Re-queues articles, variants and siblings changed in the look-back window, and adds products missing from the queue entirely.',
        'command'     => 'wmdkffexport:cron:reset',
        'arguments'   => '--cron',
        'schedule'    => '10 */3 * * *',
        'timeout'     => 900,
        'active'      => true,
    ],
    [
        'code'        => 'ffqueue_export_flour',
        'title'       => 'flour POS: partner feed',
        'description' => 'Writes the semicolon-delimited feed for the flour point-of-sale system, with its own field mapping.',
        'command'     => 'wmdkffexport:export:flour',
        'arguments'   => '--channel=wh1_live_de --lang=0 --flour-id=1 --shop-id=1 --cron',
        'schedule'    => '35 */2 * * *',
        'timeout'     => 900,
        'active'      => true,
    ],

    // -----------------------------------------------------------------------
    // Registered in OXID 6 but effectively unscheduled there — paused.
    // -----------------------------------------------------------------------
    [
        'code'        => 'ffqueue_import_trusted_shops',
        'title'       => 'Trusted Shops: import product reviews',
        'description' => 'Pulls product reviews from the Trusted Shops API and imports them onto articles. Production called this once in three days, so it is not on a schedule here.',
        'command'     => 'wmdkffexport:import:trusted-shops',
        'arguments'   => '--channel=wh1_live_de --shop-id=1 --cron',
        'schedule'    => '@daily',
        'timeout'     => 1800,
        'active'      => false,
    ],
    [
        'code'        => 'ffqueue_export_sooqr',
        'title'       => 'Spotler/Sooqr: product feed',
        'description' => 'Alternative search-provider feed built from the same queue. Never called in production.',
        'command'     => 'wmdkffexport:export:sooqr',
        'arguments'   => '--channel=wh1_live_de --lang=0 --shop-id=1 --cron',
        'schedule'    => '@daily',
        'timeout'     => 900,
        'active'      => false,
    ],
    [
        'code'        => 'ffqueue_export_doofinder',
        'title'       => 'Doofinder: product feed',
        'description' => 'Alternative search-provider feed built from the same queue. Never called in production.',
        'command'     => 'wmdkffexport:export:doofinder',
        'arguments'   => '--channel=wh1_live_de --lang=0 --shop-id=1 --cron',
        'schedule'    => '@daily',
        'timeout'     => 900,
        'active'      => false,
    ],
    [
        'code'        => 'ffqueue_cleanup',
        'title'       => 'FACT-Finder: clean invalid queue records',
        'description' => 'Housekeeping that removes invalid rows from the export queue. New in OXID 7 — no production ancestor, so it needs a deliberate decision before it runs.',
        'command'     => 'wmdkffexport:maintenance:cleanup',
        'schedule'    => '@weekly',
        'timeout'     => 900,
        'active'      => false,
    ],
];
