import re

file_path = r'c:\xampp\htdocs\waba\app\Yantrana\Components\SupportTicket\Controllers\SupportTicketController.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

old_download = """        if (empty($data['attachment']['file_path'])) {
            abort(404, __tr('No attachment found.'));
        }

        $path = storage_path('app/public/' . $data['attachment']['file_path']);

        if (!file_exists($path)) {
            abort(404, __tr('File not found on server.'));
        }

        return response()->download($path, $data['attachment']['file_name']);"""
new_download = """        $index = request('index', 0);

        if (!empty($data['attachments']) && isset($data['attachments'][$index])) {
            $attachment = $data['attachments'][$index];
        } elseif (!empty($data['attachment'])) {
            $attachment = $data['attachment'];
        } else {
            abort(404, __tr('No attachment found.'));
        }

        $path = storage_path('app/public/' . $attachment['file_path']);

        if (!file_exists($path)) {
            abort(404, __tr('File not found on server.'));
        }

        return response()->download($path, $attachment['file_name']);"""

content = content.replace(old_download, new_download)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patch applied to downloadAttachment")
