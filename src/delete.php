<?php
unlink(__DIR__ . '/config.json');
copy(__DIR__ . '/assets/default_conf.json', __DIR__ . '/config.json');
header('Location: /setup.php');
?>