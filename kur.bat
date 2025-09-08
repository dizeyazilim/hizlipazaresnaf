@echo off
setlocal

set BASE=lib

REM Klasörleri oluştur
mkdir %BASE%\config
mkdir %BASE%\screens
mkdir %BASE%\services

REM Dosyaları oluştur
type nul > %BASE%\main.dart
type nul > %BASE%\config\api_config.dart
type nul > %BASE%\screens\login_screen.dart
type nul > %BASE%\screens\register_screen.dart
type nul > %BASE%\screens\home_screen.dart
type nul > %BASE%\screens\content_add_screen.dart
type nul > %BASE%\services\api_service.dart

echo 🎉 Flutter yapısı başarıyla oluşturuldu!
pause
echo Yapılandırma dosyası oluşturulmadı. Lütfen manuel olarak ekleyin.