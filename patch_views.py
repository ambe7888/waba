import re

# Update show.blade.php
file_path_show = r'c:\xampp\htdocs\waba\resources\views\support_ticket\show.blade.php'

with open(file_path_show, 'r', encoding='utf-8') as f:
    content = f.read()

# Update single attachment input to multiple
old_input = """                            <label class="form-control-label text-muted" for="attachment">{{ __tr('Attach File (Optional)') }}</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="attachment" id="attachment" onchange="document.getElementById('attachment-label').innerHTML = this.files[0].name">
                                <label class="custom-file-label" for="attachment" id="attachment-label">{{ __tr('Choose file') }}</label>
                            </div>"""
new_input = """                            <label class="form-control-label text-muted" for="attachments">{{ __tr('Attach Files (Optional)') }}</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="attachments[]" id="attachments" multiple onchange="document.getElementById('attachments-label').innerHTML = this.files.length + ' {{ __tr('file(s) selected') }}'">
                                <label class="custom-file-label" for="attachments" id="attachments-label">{{ __tr('Choose files') }}</label>
                            </div>"""
content = content.replace(old_input, new_input)

# Update display in ticket (if single attachment or multiple)
old_display_ticket = """                            @if(!empty($ticket->__data['attachment']))
                            <div class="mt-3">
                                <span class="text-muted text-sm mr-2">{{ __tr('Attachment:') }}</span>
                                <div class="d-inline-block p-2 bg-light rounded border">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-paperclip text-muted mr-2"></i>
                                    <a href="{{ route('support_ticket.download_attachment', ['type' => 'ticket', 'uid' => $ticket->_uid]) }}" class="font-weight-bold text-sm text-primary mr-3" target="_blank">
                                        {{ $ticket->__data['attachment']['file_name'] }}
                                    </a>
                                    <a href="{{ route('support_ticket.download_attachment', ['type' => 'ticket', 'uid' => $ticket->_uid]) }}" class="btn btn-sm btn-primary py-1 px-2" title="{{ __tr('Download File') }}">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    </div>
                                </div>
                            </div>
                            @endif"""
new_display_ticket = """                            @if(!empty($ticket->__data['attachments']) || !empty($ticket->__data['attachment']))
                            <div class="mt-3">
                                <span class="text-muted text-sm mr-2">{{ __tr('Attachments:') }}</span>
                                @php
                                    $attachments = !empty($ticket->__data['attachments']) ? $ticket->__data['attachments'] : (!empty($ticket->__data['attachment']) ? [$ticket->__data['attachment']] : []);
                                @endphp
                                @foreach($attachments as $index => $attachment)
                                <div class="d-inline-block p-2 bg-light rounded border mr-2 mb-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-paperclip text-muted mr-2"></i>
                                    <a href="{{ route('support_ticket.download_attachment', ['type' => 'ticket', 'uid' => $ticket->_uid]) }}?index={{ $index }}" class="font-weight-bold text-sm text-primary mr-3" target="_blank">
                                        {{ $attachment['file_name'] }}
                                    </a>
                                    <a href="{{ route('support_ticket.download_attachment', ['type' => 'ticket', 'uid' => $ticket->_uid]) }}?index={{ $index }}" class="btn btn-sm btn-primary py-1 px-2" title="{{ __tr('Download File') }}">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif"""
content = content.replace(old_display_ticket, new_display_ticket)

# Update display in reply
old_display_reply = """                                @if(!empty($reply->__data['attachment']))
                                <div class="mt-2">
                                    <div class="d-inline-block p-2 bg-white rounded border">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-paperclip text-muted mr-2"></i>
                                        <a href="{{ route('support_ticket.download_attachment', ['type' => 'reply', 'uid' => $reply->_uid]) }}" class="font-weight-bold text-sm text-primary mr-3" target="_blank">
                                            {{ $reply->__data['attachment']['file_name'] }}
                                        </a>
                                        <a href="{{ route('support_ticket.download_attachment', ['type' => 'reply', 'uid' => $reply->_uid]) }}" class="btn btn-sm btn-primary py-1 px-2">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        </div>
                                    </div>
                                </div>
                                @endif"""
new_display_reply = """                                @if(!empty($reply->__data['attachments']) || !empty($reply->__data['attachment']))
                                <div class="mt-2">
                                    @php
                                        $attachments = !empty($reply->__data['attachments']) ? $reply->__data['attachments'] : (!empty($reply->__data['attachment']) ? [$reply->__data['attachment']] : []);
                                    @endphp
                                    @foreach($attachments as $index => $attachment)
                                    <div class="d-inline-block p-2 bg-white rounded border mr-2 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-paperclip text-muted mr-2"></i>
                                        <a href="{{ route('support_ticket.download_attachment', ['type' => 'reply', 'uid' => $reply->_uid]) }}?index={{ $index }}" class="font-weight-bold text-sm text-primary mr-3" target="_blank">
                                            {{ $attachment['file_name'] }}
                                        </a>
                                        <a href="{{ route('support_ticket.download_attachment', ['type' => 'reply', 'uid' => $reply->_uid]) }}?index={{ $index }}" class="btn btn-sm btn-primary py-1 px-2">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif"""
content = content.replace(old_display_reply, new_display_reply)

with open(file_path_show, 'w', encoding='utf-8') as f:
    f.write(content)


# Update create.blade.php
file_path_create = r'c:\xampp\htdocs\waba\resources\views\support_ticket\create.blade.php'

with open(file_path_create, 'r', encoding='utf-8') as f:
    content_create = f.read()

old_create_input = """                                    <label class="form-control-label text-muted" for="attachment">{{ __tr('Attachment (Optional)') }}</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="attachment" id="attachment" onchange="document.getElementById('attachment-label').innerHTML = this.files[0].name">
                                        <label class="custom-file-label" for="attachment" id="attachment-label">{{ __tr('Choose file') }}</label>
                                    </div>"""
new_create_input = """                                    <label class="form-control-label text-muted" for="attachments">{{ __tr('Attachments (Optional)') }}</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="attachments[]" id="attachments" multiple onchange="document.getElementById('attachments-label').innerHTML = this.files.length + ' {{ __tr('file(s) selected') }}'">
                                        <label class="custom-file-label" for="attachments" id="attachments-label">{{ __tr('Choose files') }}</label>
                                    </div>"""
content_create = content_create.replace(old_create_input, new_create_input)

with open(file_path_create, 'w', encoding='utf-8') as f:
    f.write(content_create)

print("Patch applied to Blade views")
