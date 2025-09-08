##################################
# Flutter için gerekli sınıflar
##################################

-keep class io.flutter.** { *; }
-keep class io.flutter.plugins.** { *; }
-keep class io.flutter.embedding.** { *; }

##################################
# JSON model (Gson) desteği
##################################

-keepclassmembers class * {
    @com.google.gson.annotations.SerializedName <fields>;
}
-keep class com.yourapp.models.** { *; }   # Bunu kendi model klasörüne göre düzelt!

##################################
# Play Core (SplitInstall vs.)
##################################

-keep class com.google.android.play.core.** { *; }
-dontwarn com.google.android.play.core.**

##################################
# Firebase varsa
##################################

-keep class com.google.firebase.** { *; }
-dontwarn com.google.firebase.**

##################################
# (Opsiyonel) Retrofit / OkHttp kullanıyorsan
##################################

-keep class retrofit2.** { *; }
-keep interface retrofit2.** { *; }

-keep class okhttp3.** { *; }
-keep interface okhttp3.** { *; }
