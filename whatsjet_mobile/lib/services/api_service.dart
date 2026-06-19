import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';
import 'package:mime/mime.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';
import '../models/contact.dart';
import '../models/chat_message.dart';
import '../models/resource.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  String? _token;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
  }

  bool get isAuthenticated => _token != null;

  Future<void> _saveToken(String token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }

  Future<void> logout() async {
    _token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }

  Map<String, String> _getHeaders({bool requireAuth = true}) {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Api-Request-Signature': 'mobile-app-request',
      'User-Agent': 'WhatsClick-Mobile/1.0.0',
    };
    if (requireAuth && _token != null) {
      headers['Authorization'] = 'Bearer $_token';
    }
    return headers;
  }

  /// Authenticate the user and save the token
  Future<bool> login(String email, String password) async {
    final url = Uri.parse('${baseApiUrl}user/login-process');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(requireAuth: false),
        body: jsonEncode({
          'email': email,
          'password': password,
        }),
      );

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        final reaction = body['reaction'];
        if (reaction == 1) {
          final token = body['data']['access_token'];
          if (token != null) {
            await _saveToken(token);
            return true;
          }
        }
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Login Error: $e');
      return false;
    }
  }

  /// Fetch contacts list with pagination
  Future<Map<String, dynamic>> fetchContacts({int page = 1}) async {
    final url = Uri.parse('${baseApiUrl}vendor/contact/contacts-data?page=$page');
    try {
      final response = await http.get(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        final reaction = body['reaction'];
        if (reaction == 1) {
          final contactsData = body['client_models']?['contacts'] ?? body['data']?['contacts'];
          final nextPage = body['client_models']?['contactsPaginatePage'] ?? 0;
          List<Contact> list = [];
          if (contactsData is List) {
            list = contactsData.map((c) => Contact.fromJson(c)).toList();
          } else if (contactsData is Map) {
            list = contactsData.values.map((c) => Contact.fromJson(c as Map<String, dynamic>)).toList();
          }
          return {'contacts': list, 'nextPage': nextPage};
        }
      }
      return {'contacts': <Contact>[], 'nextPage': 0};
    } catch (e) {
      if (debug) debugPrint('Fetch Contacts Error: $e');
      return {'contacts': <Contact>[], 'nextPage': 0};
    }
  }

  /// Fetch chat messages for a specific contact
  Future<List<ChatMessage>?> fetchMessages(String contactUid) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/contact/chat-data/$contactUid/append');
    try {
      final response = await http.get(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        final reaction = body['reaction'];
        if (reaction == 1) {
          final logsData = body['client_models']?['whatsappMessageLogs'] ?? body['data']?['whatsappMessageLogs'];
          if (logsData is List) {
            return logsData.map((m) => ChatMessage.fromJson(m)).toList();
          } else if (logsData is Map) {
            return logsData.values.map((m) => ChatMessage.fromJson(m as Map<String, dynamic>)).toList();
          }
        }
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Fetch Messages Error: $e');
      return null;
    }
  }

  /// Send message to a contact
  Future<bool> sendMessage(String contactUid, String messageBody) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/contact/chat/send');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'contact_uid': contactUid,
          'message_body': messageBody,
        }),
      );

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Send Message Error: $e');
      return false;
    }
  }

  /// Register FCM device token
  Future<bool> registerDeviceToken(String fcmToken, String deviceId, String deviceType) async {
    final url = Uri.parse('${baseApiUrl}user-device/token');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'device_token': fcmToken,
          'device_id': deviceId,
          'device_type': deviceType,
        }),
      );

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Register Device Token Error: $e');
      return false;
    }
  }

  /// Fetch resource list
  Future<List<Resource>> fetchResources() async {
    final url = Uri.parse('${baseApiUrl}vendor/info-materials');
    try {
      final response = await http.get(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        final reaction = body['reaction'];
        if (reaction == 1) {
          final materials = body['data']['materials'] as List?;
          if (materials != null) {
            return materials.map((m) => Resource.fromJson(m)).toList();
          }
        }
      }
      return [];
    } catch (e) {
      if (debug) debugPrint('Fetch Resources Error: $e');
      return [];
    }
  }

  /// Fetch contact details (groups, custom field values, etc)
  Future<Map<String, dynamic>?> fetchContactDetails(String contactUid) async {
    final url = Uri.parse('${baseApiUrl}vendor/contacts/$contactUid/get-update-data');
    try {
      final response = await http.get(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          return body['data'] as Map<String, dynamic>?;
        }
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Fetch Contact Details Error: $e');
      return null;
    }
  }

  /// Update contact details (names, email, custom fields, AI bots)
  Future<bool> updateContactDetails(
    String contactUid, {
    String? firstName,
    String? lastName,
    String? email,
    Map<String, String>? customFields,
    bool? enableAiBot,
    bool? enableReplyBot,
  }) async {
    final url = Uri.parse('${baseApiUrl}vendor/contacts/update-process');
    try {
      final Map<String, dynamic> bodyMap = {
        'contactIdOrUid': contactUid,
      };
      if (firstName != null) bodyMap['first_name'] = firstName;
      if (lastName != null) bodyMap['last_name'] = lastName;
      if (email != null) bodyMap['email'] = email;
      if (customFields != null) bodyMap['custom_input_fields'] = customFields;
      if (enableAiBot != null) bodyMap['enable_ai_bot'] = enableAiBot ? 1 : 0;
      if (enableReplyBot != null) bodyMap['enable_reply_bot'] = enableReplyBot ? 1 : 0;

      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode(bodyMap),
      );

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Update Contact Details Error: $e');
      return false;
    }
  }

  /// Fetch labels and team members data
  Future<Map<String, dynamic>?> fetchLabelsAndAgents(String contactUid) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/contact/chat-box-data/$contactUid');
    try {
      final response = await http.get(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          return body['data'] as Map<String, dynamic>?;
        }
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Fetch Labels and Agents Error: $e');
      return null;
    }
  }

  /// Assign labels to contact
  Future<bool> assignContactLabels(String contactUid, List<int> labelIds) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/contact/chat/assign-labels');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'contactUid': contactUid,
          'contact_labels': labelIds,
        }),
      );
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Assign Contact Labels Error: $e');
      return false;
    }
  }

  /// Assign chat agent to contact
  Future<bool> assignContactUser(String contactUid, String userUid) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/contact/chat/assign-user');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'contactIdOrUid': contactUid,
          'assigned_users_uid': userUid,
        }),
      );
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Assign Contact User Error: $e');
      return false;
    }
  }

  /// Update contact internal notes
  Future<bool> updateContactNotes(String contactUid, String notes) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/contact/chat/update-notes');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'contactIdOrUid': contactUid,
          'contact_notes': notes,
        }),
      );
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Update Contact Notes Error: $e');
      return false;
    }
  }

  /// Block contact
  Future<bool> blockContact(String contactUid) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/contact/$contactUid/block-process');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
      );
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Block Contact Error: $e');
      return false;
    }
  }

  /// Unblock contact
  Future<bool> unblockContact(String contactUid) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/contact/$contactUid/unblock-process');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
      );
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Unblock Contact Error: $e');
      return false;
    }
  }

  /// Fetch active quick replies for a contact
  Future<List<Map<String, dynamic>>> fetchQuickReplies(String contactUid) async {
    final url = Uri.parse('${baseApiUrl}vendor/bot-replies/$contactUid/all-active-bots');
    try {
      final response = await http.get(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          final list = body['data']?['bot_replies'] as List?;
          if (list != null) {
            return List<Map<String, dynamic>>.from(list);
          }
        }
      }
      return [];
    } catch (e) {
      if (debug) debugPrint('Fetch Quick Replies Error: $e');
      return [];
    }
  }

  /// Send a quick reply
  Future<bool> sendQuickReply(String contactUid, int botId) async {
    final url = Uri.parse('${baseApiUrl}vendor/bot-replies/quick-reply-process');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'contact_id_or_uid': contactUid,
          'bot_id': botId,
        }),
      );
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Send Quick Reply Error: $e');
      return false;
    }
  }

  /// Fetch WhatsApp approved templates list
  Future<List<Map<String, dynamic>>> fetchTemplates() async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/templates');
    try {
      final response = await http.get(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          final list = body['data']?['templates'] as List?;
          if (list != null) {
            return List<Map<String, dynamic>>.from(list);
          }
        }
      }
      return [];
    } catch (e) {
      if (debug) debugPrint('Fetch Templates Error: $e');
      return [];
    }
  }

  /// Send WhatsApp template message
  Future<bool> sendTemplateMessage(String contactUid, String templateUid, Map<String, dynamic> variables) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/contact/send-template-message');
    try {
      final Map<String, dynamic> bodyMap = {
        'contact_uid': contactUid,
        'template_uid': templateUid,
        ...variables,
      };
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode(bodyMap),
      );
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Send Template Message Error: $e');
      return false;
    }
  }

  /// Upload temporary media file for chat
  Future<String?> uploadTempMedia(File file, String mediaType) async {
    final url = Uri.parse('${baseApiUrl}media/upload-temp-media/$mediaType');
    try {
      final token = _token ?? (await SharedPreferences.getInstance()).getString('auth_token');
      final request = http.MultipartRequest('POST', url);

      request.headers.addAll({
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Api-Request-Signature': 'mobile-app-request',
      });

      // Ensure proper MIME type is determined
      String? mimeType = lookupMimeType(file.path);
      if (mimeType == null) {
        if (file.path.toLowerCase().endsWith('.m4a')) {
          mimeType = 'audio/mp4';
        } else {
          mimeType = 'application/octet-stream';
        }
      }

      final type = mimeType.split('/')[0];
      final subtype = mimeType.split('/').length > 1 ? mimeType.split('/')[1] : 'octet-stream';

      final multipartFile = await http.MultipartFile.fromPath(
        'filepond',
        file.path,
        contentType: MediaType(type, subtype),
      );
      request.files.add(multipartFile);

      final response = await request.send();
      final responseBody = await response.stream.bytesToString();
      debugPrint('Upload Temp Media Response: $responseBody');
      
      if (response.statusCode == 200) {
        final body = jsonDecode(responseBody);
        if (body['reaction'] == 1) {
          return body['data']?['fileName'];
        }
      }
      if (debug) debugPrint('Upload Temp Media API Error Status: ${response.statusCode}, Body: $responseBody');
      return null;
    } catch (e) {
      if (debug) debugPrint('Upload Temp Media Exception: $e');
      return null;
    }
  }

  /// Send media message referencing uploaded temp file
  Future<bool> sendMediaMessage(String contactUid, String mediaType, String fileName, {String? caption, String? originalFilename}) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/contact/chat/send-media');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'contact_uid': contactUid,
          'media_type': mediaType,
          'uploaded_media_file_name': fileName,
          'caption': caption ?? '',
          'raw_upload_data': jsonEncode({
            'original_filename': originalFilename ?? fileName,
          }),
        }),
      );
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Send Media Message Error: $e');
      return false;
    }
  }
}

