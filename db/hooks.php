<?php
$callbacks = [
    [
        'hook' => core\hook\output\before_standard_top_of_body_html_generation::class,
        'callback' => 'mod_mumie\hook\output\before_standard_top_of_body_html_generation::callback',
        'priority' => 0,
    ],
];
