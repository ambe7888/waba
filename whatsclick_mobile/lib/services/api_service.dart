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
import 'package:path_provider/path_provider.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  String? _token;
  /// Last error message from uploadTempMedia (for UI display)
  String? lastUploadError;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
    _cachedRoleId = prefs.getInt('user_role_id');
  }

  bool get isAuthenticated => _token != null;

  int _parseNextPage(dynamic value) {
    if (value == null) return 0;
    if (value is int) return value;
    if (value is String) return int.tryParse(value) ?? 0;
    if (value is double) return value.toInt();
    return 0;
  }

  int? _cachedRoleId;

  Future<void> _saveToken(String token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }

  Future<void> _saveRoleId(int roleId) async {
    _cachedRoleId = roleId;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt('user_role_id', roleId);
  }

  /// Returns the current user role ID (2 = vendor admin, 3 = agent)
  Future<int> getUserRoleId() async {
    if (_cachedRoleId != null) return _cachedRoleId!;
    final prefs = await SharedPreferences.getInstance();
    return prefs.getInt('user_role_id') ?? 3;
  }

  Future<void> logout() async {
    _token = null;
    _cachedRoleId = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('user_role_id');
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
  Future<Map<String, dynamic>> login(String email, String password) async {
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
          final data = body['data'];
          if (data['two_factor_auth_enabled'] == true) {
            return {
              'success': true,
              'two_factor': true,
              'user_id': data['user_id']?.toString(),
            };
          }
          final token = data['access_token'];
          if (token != null) {
            await _saveToken(token);
            // Save role_id from auth_info (2 = vendor admin, 3 = agent)
            final authInfo = data['auth_info'];
            if (authInfo != null) {
              final roleId = authInfo['role_id'];
              if (roleId != null) {
                await _saveRoleId((roleId as num).toInt());
              }
            }
            return {'success': true, 'two_factor': false};
          }
        }
      }
      return {'success': false, 'two_factor': false};
    } catch (e) {
      if (debug) debugPrint('Login Error: $e');
      return {'success': false, 'two_factor': false};
    }
  }

  /// Verify Two Factor Authentication code
  Future<Map<String, dynamic>> verifyTwoFactor({
    required String userId,
    required String code,
  }) async {
    final url = Uri.parse('${baseApiUrl}user/two-factor-challenge');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(requireAuth: false),
        body: jsonEncode({
          'user_id': userId,
          'verify_via': 'code',
          'code': code,
        }),
      );

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        final reaction = body['reaction'];
        if (reaction == 1) {
          final data = body['data'];
          final token = data['access_token'];
          if (token != null) {
            await _saveToken(token);
            final authInfo = data['auth_info'];
            if (authInfo != null) {
              final roleId = authInfo['role_id'];
              if (roleId != null) {
                await _saveRoleId((roleId as num).toInt());
              }
            }
            return {'success': true};
          }
        }
        return {'success': false, 'message': body['message'] ?? 'Code invalide.'};
      }
      return {'success': false, 'message': 'Erreur de connexion.'};
    } catch (e) {
      if (debug) debugPrint('2FA Verification Error: $e');
      return {'success': false, 'message': 'Erreur de connexion.'};
    }
  }

  /// Fetch contacts list with pagination
  Future<Map<String, dynamic>> fetchContacts({
    int page = 1,
    String? selectedLabel,
    String? labelDateFilter,
    String? startDate,
    String? endDate,
    String? assigned,
    String? search,
  }) async {
    final List<String> params = ['page=$page'];
    if (search != null && search.isNotEmpty) params.add('search=$search');
    if (selectedLabel != null) params.add('selected_labels=$selectedLabel');
    if (labelDateFilter != null) params.add('label_date_filter=$labelDateFilter');
    if (startDate != null) params.add('start_date=$startDate');
    if (endDate != null) params.add('end_date=$endDate');
    if (assigned != null) params.add('assigned=$assigned');

    final url = Uri.parse('${baseApiUrl}vendor/contact/contacts-data?' + params.join('&'));
    try {
      final response = await http.get(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        final reaction = body['reaction'];
        if (reaction == 1) {
          final contactsData = body['client_models']?['contacts'] ?? body['data']?['contacts'];
          final nextPageRaw = body['client_models']?['contactsPaginatePage'] ?? body['data']?['contactsPaginatePage'];
          final nextPage = _parseNextPage(nextPageRaw);
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

  /// Fetch dashboard stats
  Future<Map<String, dynamic>?> fetchDashboardStats({
    String? startDate,
    String? endDate,
    String? agentId,
  }) async {
    String query = '';
    final List<String> params = [];
    if (startDate != null) params.add('start_date=$startDate');
    if (endDate != null) params.add('end_date=$endDate');
    if (agentId != null) params.add('agent_id=$agentId');
    if (params.isNotEmpty) {
      query = '?' + params.join('&');
    }
    final url = Uri.parse('${baseApiUrl}vendor/dashboard-stats$query');
    try {
      final response = await http.get(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          final data = body['data'];
          if (data is Map<String, dynamic>) {
            if (data['vendorDashboardData'] is Map<String, dynamic>) {
              return Map<String, dynamic>.from(data['vendorDashboardData'] as Map);
            }
            return data;
          }
        }
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Fetch Dashboard Error: $e');
      return null;
    }
  }

  /// Toggle OpenAI Bot replies status
  Future<bool> toggleBotReply() async {
    final url = Uri.parse('${baseApiUrl}vendor/settings/toggle-bot');
    try {
      final response = await http.post(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          return true;
        }
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Toggle Bot Error: $e');
      return false;
    }
  }

  /// Fetch unread message counts (for notification badges)
  /// Returns: { 'unreadMessagesCount': int, 'myAssignedUnreadMessagesCount': int }
  Future<Map<String, int>> fetchUnreadCounts() async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/chat/unread-count');
    try {
      final response = await http.get(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          final models = body['client_models'] ?? {};
          return {
            'unreadMessagesCount': (models['unreadMessagesCount'] as num?)?.toInt() ?? 0,
            'myAssignedUnreadMessagesCount': (models['myAssignedUnreadMessagesCount'] as num?)?.toInt() ?? 0,
          };
        }
      }
    } catch (e) {
      if (debug) debugPrint('Fetch Unread Counts Error: $e');
    }
    return {'unreadMessagesCount': 0, 'myAssignedUnreadMessagesCount': 0};
  }

  /// Fetch support tickets
  Future<Map<String, dynamic>?> fetchSupportTickets({int page = 1}) async {
    final url = Uri.parse('${baseApiUrl}vendor/support-tickets?page=$page');
    try {
      final response = await http.get(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          return body['data'];
        }
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Fetch Tickets Error: $e');
      return null;
    }
  }

  /// Create support ticket
  Future<bool> createSupportTicket(String subject, String description) async {
    final url = Uri.parse('${baseApiUrl}vendor/support-tickets/store');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'subject': subject,
          'description': description,
        }),
      );
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Create Ticket Error: $e');
      return false;
    }
  }

  /// Fetch single ticket details
  Future<Map<String, dynamic>?> fetchSupportTicketDetails(String uid) async {
    final url = Uri.parse('${baseApiUrl}vendor/support-tickets/$uid');
    
    try {
      final response = await http.get(url, headers: _getHeaders());
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['reaction'] == 1) {
          return data['data'];
        }
        throw Exception('Reaction error: ${response.body}');
      }
      throw Exception('HTTP ${response.statusCode}: ${response.body}');
    } catch (e) {
      if (debug) debugPrint('Fetch Ticket Details Error: $e');
      throw e; // Rethrow to display in UI
    }
  }

  /// Reply to a support ticket
  Future<bool> replyToSupportTicket(String uid, String message, {List<File>? attachments}) async {
    final url = Uri.parse('${baseApiUrl}vendor/support-tickets/$uid/reply');
    
    try {
      if (attachments != null && attachments.isNotEmpty) {
        final prefs = await SharedPreferences.getInstance();
        final token = prefs.getString('auth_token') ?? '';
        var request = http.MultipartRequest('POST', url);
        request.headers.addAll({
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        });
        request.fields['message'] = message;
        for (var file in attachments) {
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
        }
        final streamedResponse = await request.send().timeout(const Duration(seconds: 60));
        final response = await http.Response.fromStream(streamedResponse);
        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          return data['reaction'] == 1;
        } else {
          if (debug) debugPrint('Upload failed: ${response.statusCode} - ${response.body}');
        }
        return false;
      } else {
        final response = await http.post(
          url,
          headers: _getHeaders(),
          body: jsonEncode({'message': message}),
        );
        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          return data['reaction'] == 1;
        }
        return false;
      }
    } catch (e) {
      if (debug) debugPrint('Reply Ticket Error: $e');
      return false;
    }
  }

  /// Fetch chat messages for a specific contact
  Future<List<ChatMessage>?> fetchMessages(String contactUid) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/contact/chat-data/$contactUid/append');
    try {
      final response = await http.get(url, headers: _getHeaders()).timeout(const Duration(seconds: 20));
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
      } else {
        if (debug) debugPrint('Fetch Messages API Error: ${response.statusCode} ${response.body}');
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
      ).timeout(const Duration(seconds: 20));

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      if (debug) debugPrint('Send Message API Error: ${response.statusCode} ${response.body}');
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
        // reaction 1 = success, reaction 14 = "nothing to update" (already at desired state = also success)
        return body['reaction'] == 1 || body['reaction'] == 14;
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
  /// [mediaType] is used as fallback for MIME detection: 'whatsapp_audio', 'whatsapp_image', 'whatsapp_video', 'whatsapp_document'
  Future<String?> uploadTempMedia(File file, String mediaType) async {
    final url = Uri.parse('${baseApiUrl}media/upload-temp-media/$mediaType');
    try {
      final token = _token ?? (await SharedPreferences.getInstance()).getString('auth_token');
      if (token == null || token.isEmpty) {
        if (debug) debugPrint('Upload Temp Media Error: token manquant');
        return null;
      }
      final request = http.MultipartRequest('POST', url);

      request.headers.addAll({
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Api-Request-Signature': 'mobile-app-request',
        'User-Agent': 'WhatsClick-Mobile/1.0.0',
      });

      // Determine MIME type from file path, with fallback based on mediaType
      String? mimeType = lookupMimeType(file.path);
      if (mimeType == null || mimeType == 'application/octet-stream') {
        // Fallback based on the mediaType parameter
        if (mediaType.contains('audio')) {
          mimeType = file.path.endsWith('.ogg') ? 'audio/ogg'
              : file.path.endsWith('.mp3') ? 'audio/mpeg'
              : file.path.endsWith('.aac') ? 'audio/aac'
              : file.path.endsWith('.webm') ? 'audio/webm'
              : 'audio/mp4'; // default for m4a / aac-lc recordings
        } else if (mediaType.contains('image')) {
          mimeType = file.path.endsWith('.png') ? 'image/png' : 'image/jpeg';
        } else if (mediaType.contains('video')) {
          mimeType = file.path.endsWith('.3gp') ? 'video/3gp' : 'video/mp4';
        } else {
          mimeType = 'application/pdf';
        }
        if (debug) debugPrint('Upload Temp Media: MIME fallback -> $mimeType (mediaType=$mediaType, path=${file.path})');
      }

      final typeParts = mimeType.split('/');
      final multipartFile = await http.MultipartFile.fromPath(
        'filepond',
        file.path,
        contentType: MediaType(typeParts[0], typeParts.length > 1 ? typeParts[1] : 'octet-stream'),
      );
      request.files.add(multipartFile);

      final response = await request.send().timeout(const Duration(seconds: 45));
      final responseBody = await response.stream.bytesToString();
      debugPrint('Upload Temp Media [${response.statusCode}]: $responseBody');
      
      if (response.statusCode == 200) {
        final body = jsonDecode(responseBody);
        if (body['reaction'] == 1) {
          lastUploadError = null;
          return body['data']?['fileName'] ?? body['data']?['file_name'];
        }
        // Server returned reaction != 1 — extract the message
        final msg = body['data']?['message'] ?? body['message'] ?? 'Erreur serveur (reaction != 1)';
        lastUploadError = msg is String ? msg : msg.toString();
        if (debug) debugPrint('Upload Temp Media Server Error: $lastUploadError');
      } else {
        lastUploadError = 'HTTP ${response.statusCode}';
      }
      if (debug) debugPrint('Upload Temp Media API Error: status=${response.statusCode}');
      return null;
    } catch (e) {
      lastUploadError = e.toString();
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
      ).timeout(const Duration(seconds: 30));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      if (debug) debugPrint('Send Media Message API Error: ${response.statusCode} ${response.body}');
      return false;
    } catch (e) {
      if (debug) debugPrint('Send Media Message Error: $e');
      return false;
    }
  }

  /// Fetch campaign list for mobile
  Future<List<Map<String, dynamic>>> fetchCampaigns() async {
    final url = Uri.parse('${baseApiUrl}vendor/campaign-list');
    try {
      final response = await http.get(url, headers: _getHeaders()).timeout(const Duration(seconds: 20));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          final raw = body['data']?['campaignList'];
          if (raw is Map && raw['data'] is List) {
            return List<Map<String, dynamic>>.from(raw['data']);
          }
          if (raw is List) {
            return List<Map<String, dynamic>>.from(raw);
          }
        }
      }
      return [];
    } catch (e) {
      if (debug) debugPrint('Fetch Campaigns Error: $e');
      return [];
    }
  }

  /// Check if a new version is available on the server
  Future<Map<String, dynamic>?> checkForUpdate() async {
    final url = Uri.parse('${baseUrl}downloads/version.json');
    try {
      final response = await http.get(url).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final serverVersion = data['version']?.toString() ?? '';
        if (serverVersion.isNotEmpty && serverVersion != version) {
          return {
            'version': serverVersion,
            'change_log': data['change_log']?.toString() ?? '',
            'apk_url': '${baseUrl}downloads/whatsclick.apk',
          };
        }
      }
    } catch (e) {
      if (debug) debugPrint('Check for update error: $e');
    }
    return null;
  }

  /// Create a new contact label on the server
  Future<Map<String, dynamic>?> createContactLabel({
    required String title,
    required String textColor,
    required String bgColor,
  }) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/contact/create-label');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'title': title,
          'text_color': textColor,
          'bg_color': bgColor,
        }),
      );

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          return body['data']?['createdLabel'] as Map<String, dynamic>?;
        }
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Create Contact Label Error: $e');
      return null;
    }
  }

  /// Fetch product list for mobile
  Future<List<Map<String, dynamic>>> fetchProducts({String search = ''}) async {
    final url = Uri.parse('${baseApiUrl}vendor/ecommerce/products?search=$search');
    try {
      final response = await http.get(url, headers: _getHeaders()).timeout(const Duration(seconds: 20));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          final productsObj = body['data']?['products'];
          if (productsObj is Map && productsObj['data'] is List) {
            return List<Map<String, dynamic>>.from(productsObj['data']);
          }
          if (productsObj is List) {
            return List<Map<String, dynamic>>.from(productsObj);
          }
        }
      }
      return [];
    } catch (e) {
      if (debug) debugPrint('Fetch Products Error: $e');
      return [];
    }
  }

  /// Send a product message to a contact
  Future<bool> sendProductMessage(String contactUid, String productUid) async {
    final url = Uri.parse('${baseApiUrl}vendor/ecommerce/send-product');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'contact_uid': contactUid,
          'product_uid': productUid,
        }),
      ).timeout(const Duration(seconds: 20));

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Send Product Message Error: $e');
      return false;
    }
  }

  /// Fetch all canned replies
  Future<List<Map<String, dynamic>>> fetchCannedReplies() async {
    final url = Uri.parse('${baseApiUrl}vendor/canned-replies');
    try {
      final response = await http.get(url, headers: _getHeaders()).timeout(const Duration(seconds: 20));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          final list = body['data']?['canned_replies'] as List?;
          if (list != null) {
            return List<Map<String, dynamic>>.from(list);
          }
        }
      }
      return [];
    } catch (e) {
      if (debug) debugPrint('Fetch Canned Replies Error: $e');
      return [];
    }
  }

  /// Save or update a canned reply
  Future<Map<String, dynamic>?> saveCannedReply({
    String? uid,
    required String shortcut,
    required String message,
  }) async {
    final url = Uri.parse('${baseApiUrl}vendor/canned-replies/save');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'uid': uid,
          'shortcut': shortcut,
          'message': message,
        }),
      ).timeout(const Duration(seconds: 20));

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          return body['data']?['canned_reply'] as Map<String, dynamic>?;
        }
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Save Canned Reply Error: $e');
      return null;
    }
  }

  /// Delete a canned reply
  Future<bool> deleteCannedReply(String uid) async {
    final url = Uri.parse('${baseApiUrl}vendor/canned-replies/$uid');
    try {
      final response = await http.delete(url, headers: _getHeaders()).timeout(const Duration(seconds: 20));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Delete Canned Reply Error: $e');
      return false;
    }
  }

  /// Fetch contact groups
  Future<List<Map<String, dynamic>>> fetchContactGroups() async {
    final url = Uri.parse('${baseApiUrl}vendor/contact/groups');
    try {
      final response = await http.get(url, headers: _getHeaders()).timeout(const Duration(seconds: 20));
      if (debug) debugPrint('fetchContactGroups status: ${response.statusCode}');
      if (debug) debugPrint('fetchContactGroups body: ${response.body.substring(0, response.body.length.clamp(0, 500))}');
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          final data = body['data'];
          // Inline route returns 'groups' key directly
          dynamic list = data?['groups']              // new inline route
              ?? data?['contactList']?['data']        // old paginated
              ?? data?['contactList']                 // plain array
              ?? data?['contactGroups']?['data']
              ?? data?['contactGroups']
              ?? data?['data'];
          if (list is List) {
            return List<Map<String, dynamic>>.from(list);
          }
        }
      }
      return [];
    } catch (e) {
      if (debug) debugPrint('Fetch Contact Groups Error: $e');
      return [];
    }
  }

  /// Fetch campaign audiences
  Future<List<Map<String, dynamic>>> fetchAudiences() async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/audiences/list-data');
    try {
      final response = await http.get(url, headers: _getHeaders()).timeout(const Duration(seconds: 20));
      if (debug) debugPrint('fetchAudiences status: ${response.statusCode}');
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        final list = body['data'] as List?;
        if (list != null) {
          return List<Map<String, dynamic>>.from(list);
        }
      }
      return [];
    } catch (e) {
      if (debug) debugPrint('Fetch Audiences Error: $e');
      return [];
    }
  }

  /// Create a campaign audience
  Future<Map<String, dynamic>?> createAudience({
    required String title,
    List<String>? contacts,
    List<String>? groups,
    List<String>? labels,
  }) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/audiences/create');
    try {
      final payload = {
        'title': title,
        'contacts': contacts ?? [],
        'groups': groups ?? [],
        'labels': labels ?? [],
      };
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode(payload),
      ).timeout(const Duration(seconds: 20));
      if (response.statusCode == 200) {
        return jsonDecode(response.body) as Map<String, dynamic>;
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Create Audience Error: $e');
      return null;
    }
  }

  /// Fetch simple non-paginated contact list for campaign wizard targeting
  Future<List<Map<String, dynamic>>> fetchSimpleContactsList() async {
    final url = Uri.parse('${baseApiUrl}vendor/contacts/simple-list');
    try {
      final response = await http.get(url, headers: _getHeaders()).timeout(const Duration(seconds: 20));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          final list = body['data']?['contacts'] as List?;
          if (list != null) {
            return List<Map<String, dynamic>>.from(list);
          }
        }
      }
      return [];
    } catch (e) {
      if (debug) debugPrint('Fetch Simple Contacts Error: $e');
      return [];
    }
  }

  /// Create a contact group
  Future<bool> createContactGroup(String title) async {
    final url = Uri.parse('${baseApiUrl}vendor/contact/groups/create');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'title': title,
        }),
      ).timeout(const Duration(seconds: 20));

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Create Contact Group Error: $e');
      return false;
    }
  }

  /// Delete a contact group
  Future<bool> deleteContactGroup(String groupUid) async {
    final url = Uri.parse('${baseApiUrl}vendor/contact/groups/$groupUid/delete');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
      ).timeout(const Duration(seconds: 20));

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Delete Contact Group Error: $e');
      return false;
    }
  }

  /// Assign groups to contact
  Future<bool> assignGroupsToContact(List<String> contactUids, List<String> groupUids) async {
    final url = Uri.parse('${baseApiUrl}vendor/contact/assign-groups');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'contacts_uid': contactUids,
          'groups_uid': groupUids,
          'selected_contacts': contactUids,
          'selected_groups': groupUids,
        }),
      ).timeout(const Duration(seconds: 20));

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Assign Groups Error: $e');
      return false;
    }
  }

  /// Fetch contacts belonging to a specific group
  Future<List<Contact>> fetchGroupContacts(String groupUid) async {
    final url = Uri.parse('${baseApiUrl}vendor/contact/groups/$groupUid/contacts');
    try {
      final response = await http.get(url, headers: _getHeaders()).timeout(const Duration(seconds: 20));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          final list = body['data']?['contacts'] as List?;
          if (list != null) {
            return list.map((c) => Contact.fromJson(Map<String, dynamic>.from(c))).toList();
          }
        }
      }
      return [];
    } catch (e) {
      if (debug) debugPrint('Fetch Group Contacts Error: $e');
      return [];
    }
  }

  /// Synchronize templates from Meta (Admin Only)
  Future<bool> syncTemplates() async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/templates/sync');
    try {
      final response = await http.post(url, headers: _getHeaders()).timeout(const Duration(seconds: 40));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Sync Templates Error: $e');
      return false;
    }
  }

  /// Create and schedule campaign (Admin Only)
  Future<Map<String, dynamic>?> scheduleCampaign(Map<String, dynamic> data) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/campaign/schedule');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode(data),
      ).timeout(const Duration(seconds: 35));

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          return Map<String, dynamic>.from(body);
        } else {
          return {
            'success': false,
            'message': body['message'] ?? 'Erreur inconnue',
          };
        }
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Schedule Campaign Error: $e');
      return null;
    }
  }

  /// Fetch Campaign Dashboard / Status (Admin Only)
  Future<Map<String, dynamic>?> fetchCampaignDashboard(String campaignUid) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/campaign/dashboard/$campaignUid/status');
    try {
      final response = await http.get(url, headers: _getHeaders()).timeout(const Duration(seconds: 25));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          return Map<String, dynamic>.from(body['data']);
        }
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Fetch Campaign Dashboard Error: $e');
      return null;
    }
  }

  /// Create WhatsApp Template (Admin Only)
  Future<Map<String, dynamic>?> createTemplate(Map<String, dynamic> data) async {
    final url = Uri.parse('${baseApiUrl}vendor/whatsapp/templates/create');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode(data),
      ).timeout(const Duration(seconds: 35));

      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return Map<String, dynamic>.from(body);
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Create Template Error: $e');
      return null;
    }
  }

  /// Fetch Bot Replies list
  Future<Map<String, dynamic>?> fetchBotReplies() async {
    final url = Uri.parse('${baseApiUrl}vendor/bot-replies-management/list');
    try {
      final response = await http.get(url, headers: _getHeaders()).timeout(const Duration(seconds: 20));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        if (body['reaction'] == 1) {
          return Map<String, dynamic>.from(body['data']);
        }
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Fetch Bot Replies Error: $e');
      return null;
    }
  }

  /// Toggle Bot Reply status
  Future<bool> toggleBotReplyStatus(String uid) async {
    final url = Uri.parse('${baseApiUrl}vendor/bot-replies-management/$uid/toggle-status');
    try {
      final response = await http.post(url, headers: _getHeaders()).timeout(const Duration(seconds: 15));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Toggle Bot Reply Status Error: $e');
      return false;
    }
  }

  /// Delete Bot Reply
  Future<bool> deleteBotReply(String uid) async {
    final url = Uri.parse('${baseApiUrl}vendor/bot-replies-management/$uid/delete');
    try {
      final response = await http.post(url, headers: _getHeaders()).timeout(const Duration(seconds: 15));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['reaction'] == 1;
      }
      return false;
    } catch (e) {
      if (debug) debugPrint('Delete Bot Reply Error: $e');
      return false;
    }
  }

  /// Create Bot Reply
  Future<Map<String, dynamic>?> createBotReply({
    required String name,
    required String triggerType,
    String? replyTrigger,
    required String replyText,
  }) async {
    final url = Uri.parse('${baseApiUrl}vendor/bot-replies-management/add');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          'name': name,
          'trigger_type': triggerType,
          'reply_trigger': replyTrigger,
          'reply_text': replyText,
          'message_type': 'simple',
        }),
      ).timeout(const Duration(seconds: 25));
      if (response.statusCode == 200) {
        return Map<String, dynamic>.from(jsonDecode(response.body));
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Create Bot Reply Error: $e');
      return null;
    }
  }

  /// Update Bot Reply
  Future<Map<String, dynamic>?> updateBotReply({
    required String uid,
    required String name,
    required String triggerType,
    String? replyTrigger,
    required String replyText,
  }) async {
    final url = Uri.parse('${baseApiUrl}vendor/bot-replies-management/update');
    try {
      final response = await http.post(
        url,
        headers: _getHeaders(),
        body: jsonEncode({
          '_uid': uid,
          'name': name,
          'trigger_type': triggerType,
          'reply_trigger': replyTrigger,
          'reply_text': replyText,
          'message_type': 'simple',
        }),
      ).timeout(const Duration(seconds: 25));
      if (response.statusCode == 200) {
        return Map<String, dynamic>.from(jsonDecode(response.body));
      }
      return null;
    } catch (e) {
      if (debug) debugPrint('Update Bot Reply Error: $e');
      return null;
    }
  }
}

