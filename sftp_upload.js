const { Client } = require('ssh2');
const fs = require('fs');

const conn = new Client();
const files = [
  {
    local: 'app/Yantrana/Components/WhatsAppService/Services/WhatsAppApiService.php',
    remote: '/home/whats-click/htdocs/whats-click.com/app/Yantrana/Components/WhatsAppService/Services/WhatsAppApiService.php'
  },
  {
    local: 'app/Yantrana/Components/WhatsAppService/WhatsAppServiceEngine.php',
    remote: '/home/whats-click/htdocs/whats-click.com/app/Yantrana/Components/WhatsAppService/WhatsAppServiceEngine.php'
  },
  {
    local: 'app/Yantrana/Components/Dashboard/Controllers/DashboardController.php',
    remote: '/home/whats-click/htdocs/whats-click.com/app/Yantrana/Components/Dashboard/Controllers/DashboardController.php'
  },
  {
    local: 'app/Yantrana/Components/Contact/ContactReminderEngine.php',
    remote: '/home/whats-click/htdocs/whats-click.com/app/Yantrana/Components/Contact/ContactReminderEngine.php'
  },
  {
    local: 'app/Yantrana/Components/Contact/Controllers/ContactReminderController.php',
    remote: '/home/whats-click/htdocs/whats-click.com/app/Yantrana/Components/Contact/Controllers/ContactReminderController.php'
  },
  {
    local: 'app/Http/Controllers/Admin/NotificationController.php',
    remote: '/home/whats-click/htdocs/whats-click.com/app/Http/Controllers/Admin/NotificationController.php'
  },
  {
    local: 'resources/views/admin/notifications/index.blade.php',
    remote: '/home/whats-click/htdocs/whats-click.com/resources/views/admin/notifications/index.blade.php'
  },
  {
    local: 'resources/views/whatsapp/chat.blade.php',
    remote: '/home/whats-click/htdocs/whats-click.com/resources/views/whatsapp/chat.blade.php'
  },
  {
    local: 'public/dist/css/whatsapp-chat.css',
    remote: '/home/whats-click/htdocs/whats-click.com/public/dist/css/whatsapp-chat.css'
  },
  {
    local: 'addons/WhatsJetDripCampaignAddon/Views/builder.blade.php',
    remote: '/home/whats-click/htdocs/whats-click.com/addons/WhatsJetDripCampaignAddon/Views/builder.blade.php'
  }
];

conn.on('ready', () => {
  console.log('Connexion SSH au VPS établie.');
  
  // Upload files one by one
  let fileIndex = 0;
  
  function uploadNext() {
    if (fileIndex >= files.length) {
      console.log('Tous les fichiers uploadés avec succès.');
      conn.end();
      return;
    }
    
    const file = files[fileIndex++];
    const content = fs.readFileSync(file.local, 'utf8');
    
    // Use SFTP to write the file directly
    conn.sftp((err, sftp) => {
      if (err) throw err;
      const stream = sftp.createWriteStream(file.remote, { encoding: 'utf8' });
      stream.on('close', () => {
        console.log(`✓ ${file.local} uploadé`);
        sftp.end();
        uploadNext();
      });
      stream.on('error', (err) => {
        console.error(`✗ Erreur pour ${file.local}:`, err);
        sftp.end();
        uploadNext();
      });
      stream.write(content);
      stream.end();
    });
  }
  
  uploadNext();
}).connect({
  host: '31.70.111.91',
  port: 22,
  username: 'root',
  password: 'fsd6415sf1'
});
