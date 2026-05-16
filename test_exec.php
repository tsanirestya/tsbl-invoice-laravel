<?php
echo "Testing exec: " . (function_exists('exec') ? "Exists" : "Missing") . "<br>";
echo "Testing system: " . (function_exists('system') ? "Exists" : "Missing") . "<br>";
echo "Testing passthru: " . (function_exists('passthru') ? "Exists" : "Missing") . "<br>";
echo "Testing shell_exec: " . (function_exists('shell_exec') ? "Exists" : "Missing") . "<br>";
