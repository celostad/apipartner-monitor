<?php

return [
    /*
     * Set the table to be used for monitoring data.
     */
    'table' => 'queue_apipartner_monitor',
    'connection' => null,

    /*
     * Set the model used for monitoring.
     * If using a custom model, be sure to implement the
     *   celostad\QueueMonitor\Models\Contracts\MonitorContract
     * interface or extend the base model.
     */
    'model' => \celostad\QueueMonitor\Models\Monitor::class,

    /*
     * Specify the max character length to use for storing exception backtraces.
     */
    'db_max_length_exception' => 4294967295,
    'db_max_length_exception_message' => 65535,

    /*
     * The optional UI settings.
     */
    'ui' => [
        /*
         * Set the monitored jobs count to be displayed per page.
         */
        'per_page' => 35,

        /*
         *  Show custom data stored on model
         */
        'show_custom_data' => false,

        /**
         * Allow the deletion of single monitor items.
         */
        'allow_deletion' => false,

        /**
         * Allow purging all monitor entries.
         */
        'allow_purge' => false,

        'show_metrics' => true,

        /**
         * Time frame used to calculate metrics values (in days).
         */
        'metrics_time_frame' => 14,
    ],
];
