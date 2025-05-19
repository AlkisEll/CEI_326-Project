<?php
$folder = __DIR__ . '/uploads';

if (!is_dir($folder)) {
    echo "❌ 'uploads' folder does not exist at: " . $folder;
    exit();
}

if (chmod($folder, 0755)) {
    echo "✅ Folder permissions for 'uploads/' set to 755 successfully.";
} else {
    echo "❌ Failed to set folder permissions. Check with your hosting provider.";
}
?>
