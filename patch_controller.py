import re

file_path = r'c:\xampp\htdocs\waba\app\Yantrana\Components\SupportTicket\Controllers\SupportTicketController.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Update store method
old_store_validation = """            'subject' => 'required|string|max:150',
            'description' => 'required|string',
            'priority' => 'nullable|string|in:low,normal,high',
            'attachment' => 'nullable|file|max:10240', // 10MB max"""
new_store_validation = """            'subject' => 'required|string|max:150',
            'description' => 'required|string',
            'priority' => 'nullable|string|in:low,normal,high',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240',"""
content = content.replace(old_store_validation, new_store_validation)

old_store_file = """        $fileData = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('support_tickets', $filename, 'public');
            $fileData = [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName()
            ];
        }

        TicketModel::create([
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority ?? 'normal',
            'vendors__id' => getVendorId(),
            'users__id' => Auth::id(),
            'status' => 1, // 1: Open
            '__data' => $fileData ? ['attachment' => $fileData] : null,
        ]);"""
new_store_file = """        $attachmentsData = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('support_tickets', $filename, 'public');
                $attachmentsData[] = [
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName()
                ];
            }
        }

        TicketModel::create([
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority ?? 'normal',
            'vendors__id' => getVendorId(),
            'users__id' => Auth::id(),
            'status' => 1, // 1: Open
            '__data' => !empty($attachmentsData) ? ['attachments' => $attachmentsData] : null,
        ]);"""
content = content.replace(old_store_file, new_store_file)

# Update apiReply method
old_api_validation = """            'message' => 'required|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max"""
new_api_validation = """            'message' => 'required|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240', // 10MB max per file"""
content = content.replace(old_api_validation, new_api_validation)

old_api_file = """        $fileData = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('support_tickets', $filename, 'public');
            $fileData = [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName()
            ];
        }

        TicketReplyModel::create([
            'tickets__id' => $ticket->_id,
            'users__id' => Auth::id(),
            'message' => $request->message,
            '__data' => $fileData ? ['attachment' => $fileData] : null,
        ]);"""
new_api_file = """        $attachmentsData = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('support_tickets', $filename, 'public');
                $attachmentsData[] = [
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName()
                ];
            }
        }
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('support_tickets', $filename, 'public');
            $attachmentsData[] = [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName()
            ];
        }

        TicketReplyModel::create([
            'tickets__id' => $ticket->_id,
            'users__id' => Auth::id(),
            'message' => $request->message,
            '__data' => !empty($attachmentsData) ? ['attachments' => $attachmentsData] : null,
        ]);"""
content = content.replace(old_api_file, new_api_file)

# Update reply method
old_reply_validation = """            'message' => 'required|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max"""
new_reply_validation = """            'message' => 'required|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240', // 10MB max"""
content = content.replace(old_reply_validation, new_reply_validation)

old_reply_file = """        $fileData = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('support_tickets', $filename, 'public');
            $fileData = [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName()
            ];
        }

        TicketReplyModel::create([
            'tickets__id' => $ticket->_id,
            'users__id' => Auth::id(),
            'message' => $request->message,
            '__data' => $fileData ? ['attachment' => $fileData] : null,
        ]);"""
new_reply_file = """        $attachmentsData = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('support_tickets', $filename, 'public');
                $attachmentsData[] = [
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName()
                ];
            }
        }
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('support_tickets', $filename, 'public');
            $attachmentsData[] = [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName()
            ];
        }

        TicketReplyModel::create([
            'tickets__id' => $ticket->_id,
            'users__id' => Auth::id(),
            'message' => $request->message,
            '__data' => !empty($attachmentsData) ? ['attachments' => $attachmentsData] : null,
        ]);"""
content = content.replace(old_reply_file, new_reply_file)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patch applied to SupportTicketController.php")
