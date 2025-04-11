<?php
$folder = __DIR__ . '/uploads';

if (!is_dir($folder)) {
    echo "❌ 'uploads' folder does not exist.";
    exit();
}

if (chmod($folder, 0755)) {
    echo "✅ Folder permissions set to 755.";
} else {
    echo "❌ Failed to set permissions.";
}
?>
