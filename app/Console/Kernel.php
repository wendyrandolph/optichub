<?php

// app/Console/Kernel.php
$schedule->job(new \App\Jobs\ProcessInboundMailbox)->everyTwoMinutes();
