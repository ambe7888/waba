 <!-- Quick Bot Reply Modal -->
<x-lw.modal id="lwQuickReply" :header="__tr('Quick Bot Reply')" :hasForm="true"
    data-pre-callback="appFuncs.clearContainer">

    <!--  Quick Bot Reply Form -->
    <x-lw.form id="lwQuickReplyForm" :action="route('vendor.bot_reply.write.quick-reply')"
        :data-callback-params="['modalId' => '#lwQuickReply', 'datatableId' => '#lwContactList']"
        data-callback="appFuncs.modelSuccessCallback">

        <div id="lwQuickReplyContentBody" class="lw-form-modal-body"></div>
        <script type="text/template" id="lwQuickReplyContentBody-template">
            <!-- form body -->
            <!-- form fields form fields -->
            <input type="hidden" name="contact_id_or_uid" :value="'<%= __tData.contactIdOrUid %>'">

            <div x-data="{ bot_id: '', selectedTemplate: '', onBotChange() { 

                        if (!_.isEmpty(this.bot_id)) {

                            let apiUrl = __Utils.apiURL('{{ route('vendor.bot_reply.read.bot_preview', ['botIdOrUid', 'contactIdOrUid']) }}?only-preview=1', {
                                'botIdOrUid': this.bot_id,
                                'contactIdOrUid': '<%= __tData.contactIdOrUid %>'
                            });
                            
                            __DataRequest.get(apiUrl, {}, function(responseData) {
                                $('#lwBotPreviewContent').html(responseData.data.previewContent);
                                _.defer(function() {
                                    window.lwPluginsInit();
                                });
                            });
                        }
                    } 
                }">
                <div x-effect="onBotChange()"></div>

                <!-- Quick Preview Cards -->
                <% if (!_.isEmpty(__tData.bot_replies)) { %>
                    <div class="mb-4">
                        <label style="font-weight: 600; font-size: 0.9rem; color: #475569; margin-bottom: 8px;">{{ __tr('Réponses suggérées') }}</label>
                        <div class="d-flex flex-wrap" style="gap: 10px;">
                            <% _.forEach(_.sampleSize(__tData.bot_replies, 6), function (botReply) { %>
                                <div @click="
                                        bot_id = '<%- botReply._id %>'; 
                                        if($('#lwSelectBot')[0] && $('#lwSelectBot')[0].selectize) {
                                            $('#lwSelectBot')[0].selectize.setValue('<%- botReply._id %>');
                                        }
                                     " 
                                     class="p-2 border rounded" 
                                     :class="bot_id == '<%- botReply._id %>' ? 'bg-primary text-white border-primary shadow-sm' : 'bg-light text-dark'"
                                     style="cursor: pointer; flex: 1 1 calc(33.333% - 10px); min-width: 140px; transition: all 0.2s ease;">
                                    <div style="font-weight: 600; font-size: 0.85rem;" class="text-truncate" title="<%- botReply.name %>">
                                        <%- botReply.name %>
                                    </div>
                                </div>
                            <% }) %>
                        </div>
                    </div>
                <% } %>

                <x-lw.input-field type="selectize" x-model="bot_id" data-lw-plugin="lwSelectize" id="lwSelectBot"
                    data-form-group-class="" data-selected=" " :label="__tr('Select Bot')" name="bot_id" required="true">
                    <x-slot name="selectOptions">
                        <option value="">{{ __tr('Select Bot') }}</option>
                        <% _.forEach(__tData.bot_replies, function (botReply) { %>
                            <option value="<%- botReply._id %>"><%- botReply.name %></option>
                        <% }) %>
                    </x-slot>
                </x-lw.input-field>
            
                <fieldset x-show="bot_id" class="lw-dynamic-template-container mt-3">
                    <legend>{{  __tr('Message Preview') }}</legend>
                    <div id="lwBotPreviewContent"></div>
                </fieldset>

            </div>
            
        </script>

        <!-- form footer --> 
        <div class="modal-footer">
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">{{ __tr('Send') }}</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
        </div>
    </x-lw.form>
    <!--/  Quick Bot Reply Form -->
</x-lw.modal>
<!--/ Quick Bot Reply Modal -->

@push('appScripts')
<?= __yesset([
            'dist/js/whatsapp-template.js',
        ],true,
) ?>
@endpush