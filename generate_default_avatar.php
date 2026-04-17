<?php
// Run this file once to generate the default avatar
// Navigate to: http://localhost/grading_system/generate_default_avatar.php

$target_dir = "uploads/profiles/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Create a 200x200 default avatar
$image = imagecreate(200, 200);
$bg_color = imagecolorallocate($image, 56, 189, 248); // Light blue background
$circle_color = imagecolorallocate($image, 14, 165, 233); // Darker blue for circle
$text_color = imagecolorallocate($image, 255, 255, 255); // White text

// Draw a circle background
imagefilledellipse($image, 100, 100, 180, 180, $circle_color);

// Draw a user icon
// Head
imagefilledellipse($image, 100, 70, 60, 60, $bg_color);
// Body
imagefilledrectangle($image, 70, 110, 130, 170, $bg_color);

// Add text "B" for Bethel
$font_size = 5;
$text = "B";
$text_width = imagefontwidth($font_size) * strlen($text);
$x = (200 - $text_width) / 2;
imagestring($image, $font_size, $x, 90, $text, $text_color);

// Save the image
imagepng($image, $target_dir . "default-avatar.png");
imagedestroy($image);

echo "Default avatar created successfully!";
echo "<br><br>You can now delete this file (generate_default_avatar.php)";
?>