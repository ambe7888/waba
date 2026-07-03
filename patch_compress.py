import re

file_path = r'c:\xampp\htdocs\waba\whatsclick_mobile\lib\services\api_service.dart'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Add imports
imports = """import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';
import 'package:path_provider/path_provider.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import '../models/chat_message.dart';"""

old_imports = """import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';
import '../models/chat_message.dart';"""

content = content.replace(old_imports, imports)

old_loop = """        for (var file in attachments) {
          final length = await file.length();
          if (length > 10485760) {
            if (debug) debugPrint('File too large: ${length}');
            return false;
          }
          request.files.add(await http.MultipartFile.fromPath('attachments[]', file.path));
        }"""
new_loop = """        for (var file in attachments) {
          final length = await file.length();
          File fileToUpload = file;
          
          // If image is larger than 2MB, try to compress it
          if (length > 2000000) {
            final ext = file.path.split('.').last.toLowerCase();
            if (['jpg', 'jpeg', 'png', 'webp'].contains(ext)) {
              try {
                final tempDir = await getTemporaryDirectory();
                final targetPath = '${tempDir.path}/${DateTime.now().millisecondsSinceEpoch}_compressed.$ext';
                final format = ext == 'png' ? CompressFormat.png : (ext == 'webp' ? CompressFormat.webp : CompressFormat.jpeg);
                var compressedFile = await FlutterImageCompress.compressAndGetFile(
                  file.absolute.path, 
                  targetPath,
                  quality: 70,
                  format: format,
                );
                if (compressedFile != null) {
                  fileToUpload = File(compressedFile.path);
                }
              } catch (e) {
                if (debug) debugPrint('Compression error: $e');
              }
            }
          }
          
          final finalLength = await fileToUpload.length();
          if (finalLength > 10485760) {
            if (debug) debugPrint('File too large even after compression: ${finalLength}');
            return false;
          }
          request.files.add(await http.MultipartFile.fromPath('attachments[]', fileToUpload.path));
        }"""

content = content.replace(old_loop, new_loop)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patch applied to api_service.dart for image compression")
