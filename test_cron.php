<?php
file_put_contents(__DIR__ . '/cron_test_ok.txt', "[" . date('Y-m-d H:i:s') . "] Cron ejecutado\n", FILE_APPEND);
