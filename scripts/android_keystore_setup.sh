#!/usr/bin/env bash
set -euo pipefail

KEYPATH="$CM_BUILD_DIR/android/app/release.keystore"
echo ">> Keystore yazılıyor: $KEYPATH"
echo "$KEYSTORE" | base64 --decode > "$KEYPATH"

SIGN_PROPS="$CM_BUILD_DIR/android/key.properties"
cat > "$SIGN_PROPS" <<EOF
storePassword=${KEYSTORE_PASSWORD}
keyPassword=${KEY_PASSWORD}
keyAlias=${KEY_ALIAS}
storeFile=app/release.keystore
EOF

echo ">> key.properties oluşturuldu"
