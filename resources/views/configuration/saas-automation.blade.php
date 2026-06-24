@php
$vendors = \App\Yantrana\Components\Vendor\Models\VendorModel::where('status', 1)->orderBy('created_at', 'asc')->get();
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

    <!-- Templates Configuration -->
    <fieldset class="lw-fieldset mb-4">
        <legend class="lw-fieldset-legend">{{ __tr('Automated WhatsApp Templates') }}</legend>
        
        <div class="alert alert-info">
            {{ __tr('Note: Ensure the template names exactly match those approved in your Meta WhatsApp Manager for the selected Vendor Account.') }}
        </div>

        <!-- Welcome Template -->
        <div class="form-group">
            <label for="saas_welcome_template">{{ __tr('Welcome Template Name') }}</label>
            <input type="text" class="form-control" name="saas_welcome_template" 
                   value="{{ getAppSettings('saas_welcome_template') }}" 
                   placeholder="{{ __tr('e.g., welcome_saas_client') }}">
            <small class="form-text text-muted">
                {{ __tr('Sent immediately when a new user registers.') }}
            </small>
        </div>

        <!-- Subscription Expiry Reminder -->
        <div class="form-group">
            <label for="saas_expiry_reminder_template">{{ __tr('Monthly / Expiry Reminder Template') }}</label>
            <input type="text" class="form-control" name="saas_expiry_reminder_template" 
                   value="{{ getAppSettings('saas_expiry_reminder_template') }}" 
                   placeholder="{{ __tr('e.g., subscription_ending_soon') }}">
            <small class="form-text text-muted">
                {{ __tr('Sent automatically as a reminder for upcoming renewals or monthly check-ins.') }}
            </small>
        </div>

        <!-- Subscription Expired -->
        <div class="form-group">
            <label for="saas_expired_template">{{ __tr('Subscription Expired Template') }}</label>
            <input type="text" class="form-control" name="saas_expired_template" 
                   value="{{ getAppSettings('saas_expired_template') }}" 
                   placeholder="{{ __tr('e.g., subscription_expired') }}">
            <small class="form-text text-muted">
                {{ __tr('Sent when a vendor\'s subscription expires.') }}
            </small>
        </div>
    </fieldset>

    <!-- Save Button -->
    <div class="form-group">
        <button type="submit" class="btn btn-primary lw-btn-block-mobile lw-ajax-form-submit-action">
            {{ __tr('Save Configuration') }}
        </button>
    </div>
</form>
