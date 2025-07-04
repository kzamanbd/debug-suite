#!/bin/bash

set -e

# Define base folders and individual files
plugin_paths=(
    "assets"
    "includes"
    "templates"
    "modules"
)
individual_files=(
    "debug-suite.php"
)

# Read version from package.json
version=$(jq -r '.version' package.json)

# Replace in recursive directories
for path in "${plugin_paths[@]}"; do
    find "$path" -type f -print0 | xargs -0 sed -i.bak -e "s/DEBUG_SUITE_SINCE/$version/g"
done

# Replace in individual files
for file in "${individual_files[@]}"; do
    if [ -f "$file" ]; then
        sed -i.bak -e "s/DEBUG_SUITE_SINCE/$version/g" "$file"
    fi
done

# Remove backup files
find . -name "*.bak" -type f -delete

echo "Version replaced with $version"
