# pusher_channels_flutter pulls in pusher-java-client, which references an
# optional SLF4J logging backend that isn't bundled - R8 treats the missing
# class as a hard error at minify time otherwise, even though it's harmless
# at runtime (SLF4J falls back to a no-op logger when no binding is found).
-dontwarn org.slf4j.**
-dontwarn com.pusher.**
