@php
$vendors = \App\Yantrana\Components\Vendor\Models\VendorModel::where('status', 1)->orderBy('created_at', 'asc')->get();
$saasAdminVendorId = getAppSettings('saas_admin_vendor_id');
$approvedTemplates = [];
if ($saasAdminVendorId) {
    $approvedTemplates = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppTemplateModel::where([
        'status' => 'APPROVED',
        'vendors__id' => $saasAdminVendorId
    ])->get();
}
@endphp

<!-- Page Heading -->
<h1>{{ __tr('SaaS WhatsApp Automation') }}</h1>
<p>{{ __tr('Configure the Super Admin vendor account to send automated messages to your clients (vendors).') }}</p>

<!-- Form -->
<form class="lw-ajax-form lw-form" method="post"
      action="<?= route('manage.configuration.write', ['pageType' => request()->pageType]) ?>">

    <!-- Vendor Account Selection -->
    <fieldset class="lw-fieldset mb-4">
        <legend class="lw-fieldset-legend">{{ __tr('Sender Account Configuration') }}</legend>
        
        <div class="form-group">
            <label for="saas_admin_vendor_id">{{ __tr('Select Super Admin Vendor Account') }}</label>
            <select class="form-control" name="saas_admin_vendor_id" id="saas_admin_vendor_id">
                <option value="">{{ __tr('--- Select a Vendor Account ---') }}</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->_id }}" 
                        {{ getAppSettings('saas_admin_vendor_id') == $vendor->_id ? 'selected' : '' }}>
                        {{ $vendor->title }} (ID: {{ $vendor->_id }})
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted">
                {{ __tr('This vendor account\'s WhatsApp Business API setup will be used to send all automated platform messages.') }}
            </small>
        </div>
    </fieldset>

    @if(!$saasAdminVendorId)
        <div class="alert alert-warning">
            {{ __tr('Please select and save a Super Admin Vendor Account to configure WhatsApp Templates.') }}
        </div>
    @else
        <!-- Templates Configuration -->
        <fieldset class="lw-fieldset mb-4">
            <legend class="lw-fieldset-legend">{{ __tr('Automated WhatsApp Templates') }}</legend>
            
            <div class="alert alert-info">
                {{ __tr('Note: Ensure the template names exactly match those approved in your Meta WhatsApp Manager for the selected Vendor Account.') }}
                <br>
                <strong>{{ __tr('Supported dynamic variables:') }}</strong>
                <code>{first_name}</code>, <code>{last_name}</code>, <code>{full_name}</code>, <code>{email}</code>, <code>{mobile_number}</code>, <code>{app_name}</code>, <code>{account_name}</code>, <code>{expiry_date}</code>, <code>{subscription_amount}</code>
            </div>

            <!-- Welcome Template -->
            <div class="form-group border-bottom pb-4 mb-4">
                <label for="saas_welcome_template" class="font-weight-bold">{{ __tr('Welcome Template Name') }}</label>
                <select class="form-control saas-template-select" name="saas_welcome_template" id="saas_welcome_template" data-vars-container="#welcome-vars-container" data-template-type="welcome">
                    <option value="">{{ __tr('--- Select Welcome Template ---') }}</option>
                    @foreach($approvedTemplates as $tmpl)
                        <option value="{{ $tmpl->template_name }}" {{ getAppSettings('saas_welcome_template') == $tmpl->template_name ? 'selected' : '' }}>
                            {{ $tmpl->template_name }} ({{ $tmpl->language }})
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted mb-2">
                    {{ __tr('Sent immediately when a new user registers.') }}
                </small>
                
                <!-- Welcome Vars Container -->
                <div id="welcome-vars-container" class="mt-2 p-3 border rounded bg-white shadow-sm" style="display: none;">
                    <div class="text-xs font-weight-bold text-uppercase text-secondary mb-2">{{ __tr('Template Preview & Variable Mappings') }}</div>
                    <div class="template-preview-text text-sm bg-light p-2 rounded mb-3 border"></div>
                    <div class="vars-inputs-list"></div>
                    <input type="hidden" name="saas_welcome_template_vars" id="saas_welcome_template_vars_hidden">
                </div>
            </div>

            <!-- Subscription Expiry Reminder -->
            <div class="form-group border-bottom pb-4 mb-4">
                <label for="saas_expiry_reminder_template" class="font-weight-bold">{{ __tr('Monthly / Expiry Reminder Template') }}</label>
                <select class="form-control saas-template-select" name="saas_expiry_reminder_template" id="saas_expiry_reminder_template" data-vars-container="#expiry-reminder-vars-container" data-template-type="expiry_reminder">
                    <option value="">{{ __tr('--- Select Expiry Reminder Template ---') }}</option>
                    @foreach($approvedTemplates as $tmpl)
                        <option value="{{ $tmpl->template_name }}" {{ getAppSettings('saas_expiry_reminder_template') == $tmpl->template_name ? 'selected' : '' }}>
                            {{ $tmpl->template_name }} ({{ $tmpl->language }})
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted mb-2">
                    {{ __tr('Sent automatically as a reminder for upcoming renewals or monthly check-ins.') }}
                </small>

                <!-- Expiry Reminder Vars Container -->
                <div id="expiry-reminder-vars-container" class="mt-2 p-3 border rounded bg-white shadow-sm" style="display: none;">
                    <div class="text-xs font-weight-bold text-uppercase text-secondary mb-2">{{ __tr('Template Preview & Variable Mappings') }}</div>
                    <div class="template-preview-text text-sm bg-light p-2 rounded mb-3 border"></div>
                    <div class="vars-inputs-list"></div>
                    <input type="hidden" name="saas_expiry_reminder_template_vars" id="saas_expiry_reminder_template_vars_hidden">
                </div>
            </div>

            <!-- Subscription Expired -->
            <div class="form-group pb-2 mb-2">
                <label for="saas_expired_template" class="font-weight-bold">{{ __tr('Subscription Expired Template') }}</label>
                <select class="form-control saas-template-select" name="saas_expired_template" id="saas_expired_template" data-vars-container="#expired-vars-container" data-template-type="expired">
                    <option value="">{{ __tr('--- Select Expired Template ---') }}</option>
                    @foreach($approvedTemplates as $tmpl)
                        <option value="{{ $tmpl->template_name }}" {{ getAppSettings('saas_expired_template') == $tmpl->template_name ? 'selected' : '' }}>
                            {{ $tmpl->template_name }} ({{ $tmpl->language }})
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted mb-2">
                    {{ __tr('Sent when a vendor\'s subscription expires.') }}
                </small>

                <!-- Expired Vars Container -->
                <div id="expired-vars-container" class="mt-2 p-3 border rounded bg-white shadow-sm" style="display: none;">
                    <div class="text-xs font-weight-bold text-uppercase text-secondary mb-2">{{ __tr('Template Preview & Variable Mappings') }}</div>
                    <div class="template-preview-text text-sm bg-light p-2 rounded mb-3 border"></div>
                    <div class="vars-inputs-list"></div>
                    <input type="hidden" name="saas_expired_template_vars" id="saas_expired_template_vars_hidden">
                </div>
            </div>
        </fieldset>
    @endif

    <!-- Save Button -->
    <div class="form-group">
        <button type="submit" class="btn btn-primary lw-btn-block-mobile lw-ajax-form-submit-action">
            {{ __tr('Save Configuration') }}
        </button>
    </div>
</form>

@if($saasAdminVendorId)
@push('js')
<script>
$(document).ready(function() {
    // Approved templates passed from PHP
    const approvedTemplates = @json($approvedTemplates);
    
    // Loaded template vars from settings
    const welcomeTemplateVars = @json(json_decode(getAppSettings('saas_welcome_template_vars', '[]'), true));
    const expiryReminderTemplateVars = @json(json_decode(getAppSettings('saas_expiry_reminder_template_vars', '[]'), true));
    const expiredTemplateVars = @json(json_decode(getAppSettings('saas_expired_template_vars', '[]'), true));
    
    const savedVars = {
        'welcome': welcomeTemplateVars || {},
        'expiry_reminder': expiryReminderTemplateVars || {},
        'expired': expiredTemplateVars || {}
    };

    function parseTemplateVariables(template) {
        let variables = [];
        let bodyText = '';
        let headerText = '';
        
        if (template && template.__data && template.__data.template) {
            const components = template.__data.template.components || [];
            components.forEach(comp => {
                if (comp.type === 'BODY') {
                    bodyText = comp.text || '';
                    const bodyMatches = bodyText.match(new RegExp('\\{\\{\\d+\\}\\}', 'g')) || [];
                    bodyMatches.forEach(match => {
                        const index = match.replace('{' + '{', '').replace('}' + '}', '');
                        variables.push({
                            type: 'BODY',
                            index: parseInt(index),
                            match: match
                        });
                    });
                } else if (comp.type === 'HEADER' && comp.format === 'TEXT') {
                    headerText = comp.text || '';
                    const headerMatches = headerText.match(new RegExp('\\{\\{\\d+\\}\\}', 'g')) || [];
                    headerMatches.forEach(match => {
                        const index = match.replace('{' + '{', '').replace('}' + '}', '');
                        variables.push({
                            type: 'HEADER',
                            index: parseInt(index),
                            match: match
                        });
                    });
                }
            });
        }
        
        // Remove duplicate items from variables list
        const uniqueVariables = [];
        const seen = new Set();
        variables.forEach(v => {
            const key = `${v.type}_${v.index}`;
            if (!seen.has(key)) {
                seen.add(key);
                uniqueVariables.push(v);
            }
        });
        
        return {
            variables: uniqueVariables.sort((a, b) => a.index - b.index),
            bodyText: bodyText,
            headerText: headerText
        };
    }

    function renderTemplateVariables($select) {
        const templateName = $select.val();
        const containerSelector = $select.data('vars-container');
        const templateType = $select.data('template-type');
        const $container = $(containerSelector);
        const $inputsList = $container.find('.vars-inputs-list');
        const $previewText = $container.find('.template-preview-text');
        
        $inputsList.empty();
        $previewText.empty();
        
        if (!templateName) {
            $container.hide();
            return;
        }
        
        const template = approvedTemplates.find(t => t.template_name === templateName);
        if (!template) {
            $container.hide();
            return;
        }
        
        const { variables, bodyText, headerText } = parseTemplateVariables(template);
        
        if (variables.length === 0) {
            $previewText.html('<strong>Template:</strong> ' + bodyText);
            $container.show();
            // Still update hidden input to empty object
            updateHiddenInput(templateType);
            return;
        }
        
        // Render preview with highlighted variables
        let highlightedText = bodyText;
        variables.forEach(v => {
            highlightedText = highlightedText.replaceAll(v.match, `<span class="badge bg-yellow text-dark font-weight-bold">${v.match}</span>`);
        });
        $previewText.html('<strong>Template:</strong> ' + highlightedText);
        
        // Render inputs for each variable
        const currentSavedVars = savedVars[templateType] || {};
        variables.forEach(v => {
            const varKey = `${v.type}_${v.index}`;
            const value = currentSavedVars[varKey] || '';
            const placeholder = v.type === 'BODY' ? `e.g., {first_name}` : `e.g., {app_name}`;
            
            const html = `
                <div class="form-group mb-3">
                    <label class="text-sm font-weight-bold text-secondary mb-1">Variable ${v.match} (${v.type})</label>
                    <input type="text" class="form-control form-control-sm var-input-field" 
                           data-template-type="${templateType}" 
                           data-var-key="${varKey}" 
                           value="${value}" 
                           placeholder="${placeholder}" />
                </div>
            `;
            $inputsList.append(html);
        });
        
        $container.show();
        updateHiddenInput(templateType);
    }

    function updateHiddenInput(templateType) {
        const varsObj = {};
        $(`.var-input-field[data-template-type="${templateType}"]`).each(function() {
            const key = $(this).data('var-key');
            const val = $(this).val();
            varsObj[key] = val;
        });
        
        $(`#saas_${templateType}_template_vars_hidden`).val(JSON.stringify(varsObj));
    }

    // Initialize on page load
    $('.saas-template-select').each(function() {
        renderTemplateVariables($(this));
    });

    // Update on change
    $('.saas-template-select').on('change', function() {
        renderTemplateVariables($(this));
    });

    // Update hidden input when variable values change
    $(document).on('input', '.var-input-field', function() {
        const templateType = $(this).data('template-type');
        updateHiddenInput(templateType);
    });
});
</script>
@endpush
@endif
