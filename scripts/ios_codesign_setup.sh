#!/usr/bin/env bash
set -euo pipefail

security create-keychain -p "" build.keychain
security default-keychain -s build.keychain
security unlock-keychain -p "" build.keychain

if [ -n "${CERTIFICATE_PRIVATE_KEY:-}" ]; then
  echo "$CERTIFICATE_PRIVATE_KEY" | base64 --decode > cert.p12
  security import cert.p12 -k build.keychain -P "$P12_PASSWORD" -T /usr/bin/codesign
  security set-key-partition-list -S apple-tool:,apple: -s -k "" build.keychain
fi

mkdir -p "$HOME/Library/MobileDevice/Provisioning Profiles"
if [ -n "${APP_STORE_PROVISIONING_PROFILE:-}" ]; then
  echo "$APP_STORE_PROVISIONING_PROFILE" | base64 --decode > "$HOME/Library/MobileDevice/Provisioning Profiles/app.mobileprovision"
fi
