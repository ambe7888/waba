import re

file_path = r'c:\xampp\htdocs\waba\whatsclick_mobile\lib\services\api_service.dart'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

old_reply = """  Future<bool> replyToSupportTicket(String uid, String message, {File? attachment}) async {
    final url = Uri.parse('${baseApiUrl}vendor/support-tickets/$uid/reply');
    
    try {
      if (attachment != null) {
        final prefs = await SharedPreferences.getInstance();
        final token = prefs.getString('auth_token') ?? '';
        var request = http.MultipartRequest('POST', url);
        request.headers.addAll({
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        });
        request.fields['message'] = message;
        request.files.add(await http.MultipartFile.fromPath('attachment', attachment.path));
        final streamedResponse = await request.send();
        final response = await http.Response.fromStream(streamedResponse);
        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          return data['reaction'] == 1;
        }
        return false;"""
new_reply = """  Future<bool> replyToSupportTicket(String uid, String message, {List<File>? attachments}) async {
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
          if (length > 10485760) {
            if (debug) debugPrint('File too large: ${length}');
            return false;
          }
          request.files.add(await http.MultipartFile.fromPath('attachments[]', file.path));
        }
        final streamedResponse = await request.send().timeout(const Duration(seconds: 60));
        final response = await http.Response.fromStream(streamedResponse);
        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          return data['reaction'] == 1;
        } else {
          if (debug) debugPrint('Upload failed: ${response.statusCode} - ${response.body}');
        }
        return false;"""

content = content.replace(old_reply, new_reply)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patch applied to api_service.dart")
