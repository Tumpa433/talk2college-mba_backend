<?php
// setup_uploads.php - Run this once to setup upload directories
$base_dir = dirname(__DIR__);
$upload_dir = $base_dir . "/uploads/question_papers/";
$log_dir = $base_dir . "/logs/";

echo "Setting up directories...<br>";

// Create uploads directory
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "✓ Created upload directory: $upload_dir<br>";
    } else {
        echo "✗ Failed to create upload directory<br>";
    }
} else {
    echo "✓ Upload directory already exists<br>";
}

// Create logs directory
if (!file_exists($log_dir)) {
    if (mkdir($log_dir, 0777, true)) {
        echo "✓ Created logs directory: $log_dir<br>";
    } else {
        echo "✗ Failed to create logs directory<br>";
    }
} else {
    echo "✓ Logs directory already exists<br>";
}

// Check permissions
echo "<br>Checking permissions...<br>";
echo "Upload directory is writable: " . (is_writable($upload_dir) ? "Yes" : "No") . "<br>";
echo "Logs directory is writable: " . (is_writable($log_dir) ? "Yes" : "No") . "<br>";

// Create .htaccess for uploads
$htaccess_content = <<<HTACCESS
# Allow access to PDF files
<FilesMatch "\.(pdf|PDF)$">
    ForceType application/pdf
    Header set Content-Disposition inline
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Disable PHP execution in uploads
<Files *.php>
    Order Deny,Allow
    Deny from all
</Files>
HTACCESS;

file_put_contents($upload_dir . ".htaccess", $htaccess_content);
echo "✓ Created .htaccess file<br>";

echo "<br><strong>Setup complete!</strong>";
?>