{
  "name": "{{ getAppSettings('name') }}",
  "short_name": "{{ getAppSettings('name') }}",
  "description": "{{ __tr('La plateforme de Marketing WhatsApp SaaS') }}",
  "start_url": "/console",
  "scope": "/",
  "display": "standalone",
  "orientation": "portrait-primary",
  "lang": "{{ str_replace('_', '-', app()->getLocale()) }}",
  "dir": "{{ isset($CURRENT_LOCALE_DIRECTION) ? $CURRENT_LOCALE_DIRECTION : 'ltr' }}",
  "id": "/console",
  "background_color": "#ffffff",
  "theme_color": "#2dce89",
  "categories": ["business", "productivity"],
  "icons": [
    {
      "src": "{{ getAppSettings('favicon_image_url') }}",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any"
    },
    {
      "src": "{{ getAppSettings('logo_image_url') }}",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any"
    },
    {
      "src": "{{ getAppSettings('logo_image_url') }}",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "maskable"
    }
  ]
}
