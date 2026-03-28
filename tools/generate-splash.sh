#!/bin/bash

# DTZ Lernplattform - Splash Screen Generator
# Requires: ImageMagick

OUTPUT_DIR="resources/splash"

# Background color and text settings
BG_COLOR="#0f0f1a"
TEXT_COLOR="#ffffff"
LOGO_COLOR="#6366f1"

echo "🎨 Generating Splash Screens..."

mkdir -p "$OUTPUT_DIR/ios"
mkdir -p "$OUTPUT_DIR/android"

# iOS Splash Screens
IOS_SIZES=(
    "320x568:Default~iphone"      # iPhone SE
    "375x667:Default-667h"        # iPhone 8
    "414x736:Default-736h"        # iPhone 8 Plus
    "375x812:Default-812h"        # iPhone X/XS/11 Pro
    "414x896:Default-896h"        # iPhone XS Max/11 Pro Max
    "768x1024:Default-Portrait"   # iPad
    "1024x768:Default-Landscape"  # iPad Landscape
    "834x1194:Default-Portrait-1112h"  # iPad Pro 10.5
    "1194x834:Default-Landscape-1112h" # iPad Pro 10.5 Landscape
    "1024x1366:Default-Portrait-1366h" # iPad Pro 12.9
    "1366x1024:Default-Landscape-1366h" # iPad Pro 12.9 Landscape
)

for size_info in "${IOS_SIZES[@]}"; do
    IFS=':' read -r size filename <<< "$size_info"
    width=${size%x*}
    height=${size#*x}
    
    convert -size ${width}x${height} xc:"$BG_COLOR" \
        -gravity center \
        -pointsize 72 \
        -fill "$LOGO_COLOR" \
        -font Arial-Bold \
        -annotate +0+0 "DTZ" \
        "$OUTPUT_DIR/ios/$filename.png"
    
    echo "  ✅ $filename.png (${width}x${height})"
done

# Android Splash Screen (XML ile yapılacak, sadece background için)
mkdir -p "$OUTPUT_DIR/android/values"

cat > "$OUTPUT_DIR/android/values/colors.xml" << EOF
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <color name="splash_background">$BG_COLOR</color>
    <color name="splash_text">$TEXT_COLOR</color>
    <color name="splash_primary">$LOGO_COLOR</color>
</resources>
EOF

echo "  ✅ Android colors.xml"

# Android drawable splash
mkdir -p "$OUTPUT_DIR/android/drawable"

for size in "480x800" "720x1280" "1080x1920" "1440x2560"; do
    width=${size%x*}
    height=${size#*x}
    
    convert -size ${width}x${height} xc:"$BG_COLOR" \
        -gravity center \
        -pointsize 96 \
        -fill "$LOGO_COLOR" \
        -font Arial-Bold \
        -annotate +0+0 "DTZ" \
        "$OUTPUT_DIR/android/drawable/splash_${width}x${height}.png"
done

echo "  ✅ Android splash images"

echo ""
echo "📁 Output: $OUTPUT_DIR/"
echo ""
echo "🚀 Copy files:"
echo "  iOS: $OUTPUT_DIR/ios/* → ios/App/App/Assets.xcassets/LaunchImage.launchimage/"
echo "  Android: $OUTPUT_DIR/android/* → android/app/src/main/res/"
