#!/usr/bin/env bash
# ios-build-sim.sh — Flutter iOS Simulator “tek tuş”
set -Eeuo pipefail

log()  { echo -e "\033[1;34m$*\033[0m"; }
warn() { echo -e "\033[1;33m$*\033[0m"; }
err()  { echo -e "\033[1;31m$*\033[0m" >&2; }

# ---- Çalışma dizini (Codemagic veya yerel) ----
WORKDIR="${CM_BUILD_DIR:-${FCI_BUILD_DIR:-$(git rev-parse --show-toplevel 2>/dev/null || pwd)}}"
cd "$WORKDIR"

# ---- Argümanlar ----
MODE="debug"        # debug | release
PATCH_HEADERS="yes" # yes | no
while [[ $# -gt 0 ]]; do
  case "$1" in
    --release) MODE="release"; shift ;;
    --no-patch) PATCH_HEADERS="no"; shift ;;
    *) err "Kullanım: $0 [--release] [--no-patch]"; exit 1 ;;
  esac
done

# ---- CocoaPods garantile ----
ensure_cocoapods() {
  if ! command -v pod >/dev/null 2>&1; then
    warn "CocoaPods bulunamadı, kullanıcı dizinine kuruluyor..."
    gem install --user-install cocoapods -v '>=1.16.2'
    export PATH="$HOME/.gem/ruby/$(ruby -e 'print RbConfig::CONFIG["ruby_version"]')/bin:$PATH"
  fi
}

# ---- Podfile yaz (Flutter’ın resmi podhelper’ı) ----
write_podfile() {
  cat > ios/Podfile <<'PODFILE'
platform :ios, '13.0'

# Flutter root'u güvenli şekilde bul
flutter_root = ENV['FLUTTER_ROOT']
if flutter_root.nil? || flutter_root.empty?
  flutter_bin = `which flutter`.strip
  raise 'Flutter binary not found in PATH.' if flutter_bin.empty?
  flutter_root = File.expand_path('..', File.dirname(flutter_bin))
end

require File.expand_path('packages/flutter_tools/bin/podhelper', flutter_root)

flutter_ios_podfile_setup

target 'Runner' do
  # use_frameworks! :linkage => :static   # gerekirse aç
  use_modular_headers!
  flutter_install_all_ios_pods File.dirname(File.realpath(__FILE__))
end

post_install do |installer|
  flutter_additional_ios_build_settings(installer)
  installer.pods_project.targets.each do |t|
    t.build_configurations.each do |config|
      config.build_settings['IPHONEOS_DEPLOYMENT_TARGET'] = '13.0'
      config.build_settings['EXCLUDED_ARCHS[sdk=iphonesimulator*]'] = 'arm64'
    end
  end
end
PODFILE
}

# ---- Pod kurulumunu temizden yap ----
pod_install_clean() {
  rm -rf ios/Pods ios/Podfile.lock
  ( cd ios
    pod repo update
    pod deintegrate || true
    pod install --verbose
  )
}

# ---- Gerekirse header arama yollarını patch’le (sqflite_darwin vb.) ----
patch_headers() {
  ruby - <<'RUBY'
require 'xcodeproj'
proj_path = 'ios/Pods/Pods.xcodeproj'
abort "Pods.xcodeproj bulunamadı: #{proj_path}" unless File.exist?(proj_path)
proj = Xcodeproj::Project.open(proj_path)
targets = proj.targets.select { |t| ['Flutter','sqflite_darwin'].include?(t.name) || t.name.start_with?('flutter_') }
targets.each do |t|
  t.build_configurations.each do |cfg|
    hs = cfg.build_settings['HEADER_SEARCH_PATHS'] || '$(inherited)'
    extra = ' $(PODS_ROOT)/Flutter/Flutter.xcframework/ios-arm64_x86_64-simulator/Headers $(PODS_ROOT)/Headers/Public'
    cfg.build_settings['HEADER_SEARCH_PATHS'] = "#{hs}#{extra}"
  end
end
proj.save
puts "Header search paths patched."
RUBY
}

# ---- Build & paketle ----
build_app() {
  flutter clean
  if [[ "$MODE" == "release" ]]; then
    flutter build ios --simulator --release --no-codesign
  else
    flutter build ios --simulator --debug --no-codesign
  fi
}

zip_app() {
  local app_dir="build/ios/iphonesimulator"
  local app_path
  app_path=$(find "$app_dir" -maxdepth 1 -name "*.app" | head -n1 || true)
  [[ -z "${app_path:-}" ]] && { err "Simulator .app bulunamadı"; exit 1; }
  (cd "$app_dir" && zip -r "Runner.app.zip" "$(basename "$app_path")" >/dev/null)
  echo "$app_dir/Runner.app.zip"
}

# ---- Akış ----
log "[1/6] Flutter & precache"
flutter --version
flutter precache --ios --force
flutter pub get

log "[2/6] CocoaPods kontrol"
ensure_cocoapods
pod --version || true

log "[3/6] Podfile yazılıyor"
write_podfile

log "[4/6] Pods temiz kuruluyor"
pod_install_clean

if [[ "$PATCH_HEADERS" == "yes" ]]; then
  log "[5/6] Header search patch uygulanıyor"
  patch_headers || true
fi

log "[6/6] Build ($MODE)"
build_app

ZIP_PATH=$(zip_app)
log "✅ Hazır: $ZIP_PATH"
