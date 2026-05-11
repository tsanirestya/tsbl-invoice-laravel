<?php
$content = file_get_contents('d:/XAMPP NEW/htdocs/tsbl-invoice-laravel/resources/views/invoices/_form.blade.php');
$opens = substr_count($content, '<div');
$closes = substr_count($content, '</div>');
echo "Opens: $opens, Closes: $closes\n";
