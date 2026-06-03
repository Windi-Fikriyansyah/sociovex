<?php
$file = 'C:/Users/sitoh/.gemini/antigravity-ide/brain/be6fd8e2-a831-4a16-b282-8c7494229b2a/.system_generated/steps/169/content.md';
$lines = file($file);
foreach ($lines as $i => $line) {
    if (stripos($line, 'post.published') !== false && stripos($line, 'properties') !== false) {
        printf("%d: %s", $i + 1, $line);
    }
}
