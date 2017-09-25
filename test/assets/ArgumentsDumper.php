<?php

$argv = $_SERVER['argv'];
array_shift($argv);
$argv = array_values($argv);
echo json_encode($argv);

exit(0);
