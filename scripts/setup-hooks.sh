#!/bin/sh

HOOK_DIR="$(git rev-parse --git-dir)/hooks"
PRE_COMMIT_HOOK="$HOOK_DIR/pre-commit"

echo "Installing pre-commit hook in $PRE_COMMIT_HOOK..."

cat << 'EOF' > "$PRE_COMMIT_HOOK"
#!/bin/sh

echo "Running pre-commit check-all suite..."
composer check-all

if [ $? -ne 0 ]; then
    echo "❌ composer check-all failed. Please fix issues before committing."
    exit 1
fi
EOF

chmod +x "$PRE_COMMIT_HOOK"
echo "✅ Pre-commit hook installed successfully!"
