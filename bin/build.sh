#!/bin/bash

set -e

# Get version from package.json
VERSION=$(jq -r '.version' package.json)
PLUGIN_SLUG="debug-suite"
BUILD_DIR="build"
DEST="$BUILD_DIR/$PLUGIN_SLUG"
ZIP_FILE="$PLUGIN_SLUG-v$VERSION.zip"

# Files and directories to include in the zip
PLUGIN_FILES=(
    "assets"
    "includes"
    "languages"
    "templates"
    "lib"
    "vendor"
    "CHANGELOG.md"
    "readme.txt"
    "debug-suite.php"
    "uninstall.php"
    "composer.json"
)

# Files/directories to remove after composer install
REMOVE_AFTER_BUILD=(
    "assets/src"
    "composer.json"
    "composer.lock"
)

echo "🧹 Cleaning build directory..."
rm -rf "$BUILD_DIR"
mkdir -p "$DEST"

echo "⚙️ Copying plugin files..."
for file in "${PLUGIN_FILES[@]}"; do
    if [ -e "$file" ]; then
        cp -R "$file" "$DEST/"
    else
        echo "⚠️  File or directory '$file' does not exist, skipping."
    fi
done

echo "📦 Installing composer dependencies (prod only)..."
cd "$DEST"
composer install --optimize-autoloader --no-dev
cd - >/dev/null

echo "🧽 Removing development files..."
for path in "${REMOVE_AFTER_BUILD[@]}"; do
    rm -rf "$DEST/$path"
done

echo "📁 Creating zip: $ZIP_FILE"
cd "$BUILD_DIR"
zip -rq "$ZIP_FILE" "$PLUGIN_SLUG"
cd - >/dev/null

echo "🗑️ Cleaning up temp build directory..."
rm -rf "$DEST"

echo "✅ Build complete: $BUILD_DIR/$ZIP_FILE"
