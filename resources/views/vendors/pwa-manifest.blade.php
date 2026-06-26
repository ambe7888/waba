{
  "name": "{{ getAppSettings('name') }}",
  "short_name": "{{ getAppSettings('name') }}",
  "start_url": "/console",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#2dce89",
  "description": "{{ __tr('La plateforme de Marketing WhatsApp SaaS') }}",
  "icons": [
    {
      "src": "{{ getAppSettings('favicon_image_url') }}",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "{{ getAppSettings('logo_image_url') }}",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
