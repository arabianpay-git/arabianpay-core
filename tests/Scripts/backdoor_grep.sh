#!/usr/bin/env bash
set -euo pipefail

PATTERNS='pandaxcode|some_random_long_secret_key|xcr9z|/kill/.*/destroy|/revive/|x9/Handler'

if git grep -nE "$PATTERNS" -- app public bootstrap config routes 2>/dev/null; then
    echo "BACKDOOR IOC DETECTED — see lines above" >&2
    exit 1
fi

echo "No backdoor IOCs detected."
