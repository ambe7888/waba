@php
    $otherMessageType = $messageDataValues['type'] ?? null;
    $messageDataValues = $messageDataValues['data'] ?? [];
@endphp
@if ($otherMessageType)
@if ($otherMessageType != 'contacts')
<div class="lw-whatsapp-preview-message-container">
    <div class="lw-whatsapp-preview">
        <div class="card ">
            <div class="lw-whatsapp-header-placeholder ">
                @if ($otherMessageType == 'location')
                <iframe height="100" src="https://maps.google.com/maps/place?q={{ $messageDataValues['latitude'] ?? '' }},{{ $messageDataValues['longitude'] ?? '' }}&output=embed&language={{ app()->getLocale() }}" frameborder="0" scrolling="no"></iframe>
                @endif
            </div>
            @if ($otherMessageType == 'location')
            <div class="lw-whatsapp-location-meta bg-secondary text-white p-2">
                <small>{{ $messageDataValues['name'] ?? '' }}</small><br>
                <small>{{ $messageDataValues['address'] ?? '' }}</small>
            </div>
            @endif
            @isset($messageDataValues['caption'])
            <div class="p-2 lw-plain-message-text">{!! $messageDataValues['caption'] !!}</div>
            @endisset
        </div>
    </div>
</div>
@elseif ($otherMessageType == 'contacts')
<<<<<<< HEAD
    @foreach ($messageDataValues as $contact)
        <h3><strong>{{ $contact['name']['formatted_name'] ?? '' }}</strong></h3>
        @foreach ($contact as $contactDataKey => $contactDataValue)
            @if ($contactDataKey != 'name')
                @foreach ($contactDataValue as $contactDataItemKey => $contactDataItemValue)
                    <div>{{ $contactDataItemValue['type'] ?? '' }}: {{ $contactDataItemValue[Str::singular($contactDataKey)] ?? '' }}</div>
                @endforeach
            @endif
        @endforeach
        <hr>
    @endforeach
=======
    @if (is_array($messageDataValues) || is_object($messageDataValues))
        @foreach ($messageDataValues as $contact)
            @if (is_array($contact) || is_object($contact))
                <h3><strong>{{ $contact['name']['formatted_name'] ?? '' }}</strong></h3>
                @foreach ($contact as $contactDataKey => $contactDataValue)
                    @if ($contactDataKey != 'name' && (is_array($contactDataValue) || is_object($contactDataValue)))
                        @foreach ($contactDataValue as $contactDataItemKey => $contactDataItemValue)
                            @if (is_array($contactDataItemValue) || is_object($contactDataItemValue))
                                <div>{{ $contactDataItemValue['type'] ?? '' }}: {{ $contactDataItemValue[Str::singular($contactDataKey)] ?? '' }}</div>
                            @endif
                        @endforeach
                    @endif
                @endforeach
                <hr>
            @endif
        @endforeach
    @endif
>>>>>>> cbd36d040e200715c7cd741e355f6ca8ead310db
@endif
@else
<div class="lw-whatsapp-preview-message-container">
    <div class="lw-whatsapp-preview">
        <div class="card ">
            <div class="text-warning p-3">{{  __tr('Unknown message type.') }}</div>
        </div>
    </div>
</div>
@endif