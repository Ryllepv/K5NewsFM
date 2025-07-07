<?php
$hash = password_hash('12345678', PASSWORD_DEFAULT);

echo "Hashed password: " . $hash . "\n";