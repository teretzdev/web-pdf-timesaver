#!/usr/bin/env bash
# Pause before verifying production after a local save (Syncthing push).
# Usage: ./scripts/wait-for-syncthing-sync.sh [seconds]
# Default: 10
set -euo pipefail
SECS="${1:-10}"
if ! [[ "$SECS" =~ ^[0-9]+$ ]]; then SECS=10; fi
echo "Waiting ${SECS}s for Syncthing sync..."
sleep "$SECS"
