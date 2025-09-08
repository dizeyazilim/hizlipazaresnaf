import java.util.Properties

plugins {
    id("com.android.application")
    id("kotlin-android")
    id("dev.flutter.flutter-gradle-plugin")
}

// 🔍 DEBUG: key.properties dosyasını yükle
val keystoreProperties = Properties()
val keystorePropertiesFile = rootProject.file("android/key.properties")

if (keystorePropertiesFile.exists()) {
    println("✅ key.properties bulundu: ${keystorePropertiesFile.absolutePath}")
    keystoreProperties.load(keystorePropertiesFile.inputStream())
    println("✅ İçerik:")
    keystoreProperties.forEach { (key, value) ->
        println("    $key = $value")
    }
} else {
    println("❌ HATA: key.properties dosyası bulunamadı!")
}

android {
    namespace = "com.dizebulut.hizlipazarvip"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = "27.0.12077973"

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_11.toString()
    }

    defaultConfig {
        applicationId = "com.dizebulut.hizlipazarvip"
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    signingConfigs {
    create("release") {
        storeFile = file("C:/hizlipazarvip/hizlipazarkey.jks")
        storePassword = "181200"
        keyAlias = "hizlipazar"
        keyPassword = "181200"
    }
}


    buildTypes {
        getByName("release") {
            isMinifyEnabled = true
            isShrinkResources = true
            signingConfig = signingConfigs.getByName("release")
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }
}

flutter {
    source = "../.."
}
