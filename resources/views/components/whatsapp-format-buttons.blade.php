@props(['inputId'])

<div class="mt-1 mb-2 d-flex justify-content-end">
    <button type="button" class="btn btn-sm btn-light border ml-1" onclick="insertWhatsAppFormat('{{ $inputId }}', '*')">
        <i class="fa fa-bold"></i>
    </button>
    <button type="button" class="btn btn-sm btn-light border ml-1" onclick="insertWhatsAppFormat('{{ $inputId }}', '_')">
        <i class="fa fa-italic"></i>
    </button>
    <button type="button" class="btn btn-sm btn-light border ml-1" onclick="insertWhatsAppFormat('{{ $inputId }}', '~')">
        <i class="fa fa-strikethrough"></i>
    </button>
    <button type="button" class="btn btn-sm btn-light border ml-1" onclick="insertWhatsAppFormat('{{ $inputId }}', '```')">
        <i class="fa fa-code"></i>
    </button>
</div>

@push('appScripts')
<script>
if (typeof window.insertWhatsAppFormat !== 'function') {
    window.insertWhatsAppFormat = function(inputId, formatChar) {
        var el = document.getElementById(inputId);
        if (!el) return;
        
        var start = el.selectionStart;
        var end = el.selectionEnd;
        var text = el.value;
        var selectedText = text.substring(start, end);
        
        var newText = text.substring(0, start) + formatChar + selectedText + formatChar + text.substring(end);
        
        el.value = newText;
        
        // Trigger input event for AlpineJS/Vue/Livewire to catch the change
        el.dispatchEvent(new Event('input', { bubbles: true }));
        
        // Put cursor back
        el.focus();
        if (selectedText.length > 0) {
            el.selectionStart = start + formatChar.length;
            el.selectionEnd = end + formatChar.length;
        } else {
            el.selectionStart = el.selectionEnd = start + formatChar.length;
        }
    };
}
</script>
@endpush
