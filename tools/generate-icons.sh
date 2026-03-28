#!/bin/bash

# DTZ Lernplattform - Icon Generator Script
# Requires: ImageMagick (brew install imagemagick)

ICON_SOURCE="frontend/img/icon.svg"
OUTPUT_DIR="resources"

# Check if ImageMagick is installed
if ! command -v convert &> /dev/null; then
    echo "❌ ImageMagick not found!"
    echo "Install with: brew install imagemagick"
    exit 1
fi

# Create output directories
mkdir -p "$OUTPUT_DIR/ios/AppIcon.appiconset"
mkdir -p "$OUTPUT_DIR/android/mipmap-mdpi"
mkdir -p "$OUTPUT_DIR/android/mipmap-hdpi"
mkdir -p "$OUTPUT_DIR/android/mipmap-xhdpi"
mkdir -p "$OUTPUT_DIR/android/mipmap-xxhdpi"
mkdir -p "$OUTPUT_DIR/android/mipmap-xxxhdpi"
mkdir -p "$OUTPUT_DIR/play-store"

echo "🎨 Generating iOS App Icons..."

# iOS App Icons
convert -background none -resize 20x20 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-20.png"
convert -background none -resize 40x40 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-20@2x.png"
convert -background none -resize 60x60 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-20@3x.png"
convert -background none -resize 29x29 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-29.png"
convert -background none -resize 58x58 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-29@2x.png"
convert -background none -resize 87x87 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-29@3x.png"
convert -background none -resize 40x40 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-40.png"
convert -background none -resize 80x80 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-40@2x.png"
convert -background none -resize 120x120 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-40@3x.png"
convert -background none -resize 120x120 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-60@2x.png"
convert -background none -resize 180x180 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-60@3x.png"
convert -background none -resize 76x76 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-76.png"
convert -background none -resize 152x152 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-76@2x.png"
convert -background none -resize 167x167 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-83.5@2x.png"
convert -background none -resize 1024x1024 "$ICON_SOURCE" "$OUTPUT_DIR/ios/AppIcon.appiconset/Icon-1024.png"

echo "✅ iOS icons generated"

echo "🎨 Generating Android App Icons..."

# Android App Icons
convert -background none -resize 48x48 "$ICON_SOURCE" "$OUTPUT_DIR/android/mipmap-mdpi/ic_launcher.png"
convert -background none -resize 72x72 "$ICON_SOURCE" "$OUTPUT_DIR/android/mipmap-hdpi/ic_launcher.png"
convert -background none -resize 96x96 "$ICON_SOURCE" "$OUTPUT_DIR/android/mipmap-xhdpi/ic_launcher.png"
convert -background none -resize 144x144 "$ICON_SOURCE" "$OUTPUT_DIR/android/mipmap-xxhdpi/ic_launcher.png"
convert -background none -resize 192x192 "$ICON_SOURCE" "$OUTPUT_DIR/android/mipmap-xxxhdpi/ic_launcher.png"

# Google Play Store icon
convert -background none -resize 512x512 "$ICON_SOURCE" "$OUTPUT_DIR/play-store/ic_launcher.png"

echo "✅ Android icons generated"

# Create Contents.json for iOS
cat > "$OUTPUT_DIR/ios/AppIcon.appiconset/Contents.json" << 'EOF'
{
  "images": [
    {"size": "20x20", "idiom": "iphone", "filename": "Icon-20@2x.png", "scale": "2x"},
    {"size": "20x20", "idiom": "iphone", "filename": "Icon-20@3x.png", "scale": "3x"},
    {"size": "29x29", "idiom": "iphone", "filename": "Icon-29@2x.png", "scale": "2x"},
    {"size": "29x29", "idiom": "iphone", "filename": "Icon-29@3x.png", "scale": "3x"},
    {"size": "40x40", "idiom": "iphone", "filename": "Icon-40@2x.png", "scale": "2x"},
    {"size": "40x40", "idiom": "iphone", "filename": "Icon-40@3x.png", "scale": "3x"},
    {"size": "60x60", "idiom": "iphone", "filename": "Icon-60@2x.png", "scale": "2x"},
    {"size": "60x60", "idiom": "iphone", "filename": "Icon-60@3x.png", "scale": "3x"},
    {"size": "20x20", "idiom": "ipad", "filename": "Icon-20.png", "scale": "1x"},
    {"size": "20x20", "idiom": "ipad", "filename": "Icon-20@2x.png", "scale": "2x"},
    {"size": "29x29", "idiom": "ipad", "filename": "Icon-29.png", "scale": "1x"},
    {"size": "29x29", "idiom": "ipad", "filename": "Icon-29@2x.png", "scale": "2x"},
    {"size": "40x40", "idiom": "ipad", "filename": "Icon-40.png", "scale": "1x"},
    {"size": "40x40", "idiom": "ipad", "filename": "Icon-40@2x.png", "scale": "2x"},
    {"size": "76x76", "idiom": "ipad", "filename": "Icon-76.png", "scale": "1x"},
    {"size": "76x76", "idiom": "ipad", "filename": "Icon-76@2x.png", "scale": "2x"},
    {"size": "83.5x83.5", "idiom": "ipad", "filename": "Icon-83.5@2x.png", "scale": "2x"},
    {"size": "1024x1024", "idiom": "ios-marketing", "filename": "Icon-1024.png", "scale": "1x"}
  ],
  "info": {
    "version": 1,
    "author": "xcode"
  }
}
EOF

echo "✅ Contents.json created"
echo ""
echo "📁 Output directories:"
echo "  - $OUTPUT_DIR/ios/AppIcon.appiconset/"
echo "  - $OUTPUT_DIR/android/mipmap-*/"
echo "  - $OUTPUT_DIR/play-store/"
echo ""
echo "🚀 Next steps:"
echo "  iOS: Copy $OUTPUT_DIR/ios/AppIcon.appiconset to ios/App/App/Assets.xcassets/"
echo "  Android: Copy $OUTPUT_DIR/android/mipmap-* to android/app/src/main/res/"
