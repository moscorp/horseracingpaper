<?php
// test_font.php
$fontPaths = [
    __DIR__ . '/fonts/NotoSansCJKjp-Regular.otf',
    __DIR__ . '/fonts/NotoSansCJKjp-Regular.ttf',
    __DIR__ . '/fonts/SourceHanSansSC-Regular.otf',
    __DIR__ . '/fonts/SourceHanSansSC-Regular.ttf',
    __DIR__ . '/fonts/wqy-microhei.ttc',
    __DIR__ . '/fonts/NotoSansTC-VariableFont_wght.ttf'
     
];

foreach ($fontPaths as $path) {
    if (file_exists($path)) {
        echo "✅ 字体文件存在: " . basename($path) . "\n";
        echo "   路径: " . $path . "\n";
        echo "   大小: " . round(filesize($path) / 1024 / 1024, 2) . " MB\n\n";
    } else {
        echo "❌ 字体文件不存在: " . basename($path) . "\n";
    }
}

echo "GD 版本: " . gd_info()['GD Version'] . "\n";
echo "FreeType 支持: " . (function_exists('imagettftext') ? 'Yes' : 'No') . "\n";

$font = __DIR__ . '/fonts/SourceHanSansTC-Regular.ttf';
if (file_exists($font)) {
    echo "字体存在: " . $font . "\n";
    echo "字体大小: " . filesize($font) . " bytes\n";
} else {
    echo "字体不存在: " . $font . "\n";
}