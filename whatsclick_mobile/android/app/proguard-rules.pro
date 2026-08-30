# pusher_channels_flutter pulls in pusher-java-client, which references an
# optional SLF4J logging backend that isn't bundled - R8 treats the missing
# class as a hard error at minify time otherwise, even though it's harmless
# at runtime (SLF4J falls back to a no-op logger when no binding is found).
-dontwarn org.slf4j.**
-dontwarn com.pusher.**

# isMinifyEnabled was just turned on for the first time in this project (it
# had been declared but inert - see the build.gradle.kts commit this
# accompanies). With no keep rule, R8's first real pass stripped Pusher's
# core public API as "unreachable" - Pusher.<init>, connect(), subscribe(),
# subscribePrivate(), getChannel() were all gone from the release build's
# R8 usage report. The Kotlin plugin glue calls these directly, so this
# would have been a silent runtime failure of realtime messaging (no build
# error - it only shows up as NoSuchMethodError/ClassNotFoundException on
# a device), on a feature this project already spent real effort getting
# working correctly.
-keep class com.pusher.client.** { *; }
-keep interface com.pusher.client.** { *; }
-keep class com.pusher.channels_flutter.** { *; }
