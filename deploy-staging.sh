#!/usr/bin/env bash
set -e

# Remote server info
REMOTE_USER="mentorcorps"
REMOTE_HOST="s417.sureserver.com"
REMOTE_PATH="/www/template-dev/wp-content/plugins/mpro-matching"

# Local plugin directory (this repo)
LOCAL_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "Deploying from:"
echo "  $LOCAL_DIR"
echo "to:"
echo "  $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"
echo

lftp -u "mentorcorps" "s417.sureserver.com" <<EOF
set ftp:ssl-force true
set ftp:ssl-protect-data true
set ftp:passive-mode on

# Dry run first time: remove --dry-run after you trust it
mirror -R \
  --dry-run \
  --delete \
  --verbose \
  --exclude-glob .git* \
  --exclude-glob 'deploy-staging.sh' \
  "$LOCAL_DIR" "$REMOTE_PATH"

bye
EOF

echo
echo "Dry-run complete."
echo "If this looked correct, remove '--dry-run' from deploy-staging.sh to actually upload."
