#!/bin/bash

# ==============================================================================
# Wapi Automated Installation Script (Multi-Project Version)
# Supported OS: Ubuntu 22.04 (Jammy) / 24.04 (Noble)
# ==============================================================================

set -e # Exit on error

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${GREEN}====================================================${NC}"
echo -e "${GREEN}      Wapi Multi-Project Installation Script        ${NC}"
echo -e "${GREEN}====================================================${NC}"


if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}Please run as root or with sudo.${NC}"
  exit 1
fi


UBUNTU_CODENAME=$(lsb_release -cs)
if [[ ! "$UBUNTU_CODENAME" =~ ^(jammy|noble)$ ]]; then
    echo -e "${YELLOW}Warning: This script is optimized for Ubuntu 22.04/24.04. Detected: $UBUNTU_CODENAME${NC}"
    read -p "Proceed anyway? (y/n): " PROCEED
    [[ "$PROCEED" != "y" ]] && exit 1
fi

echo -e "\n${YELLOW}--- Selection ---${NC}"
read -p "Install Wapi Backend? (y/n): " INSTALL_BACKEND
read -p "Install Wapi Frontend? (y/n): " INSTALL_FRONTEND
read -p "Install Wapi Admin Dashboard? (y/n): " INSTALL_ADMIN


if [[ "$INSTALL_BACKEND" == "y" ]]; then
    read -p "Enter Wapi BACKEND Git URL: " BACKEND_REPO
fi
if [[ "$INSTALL_FRONTEND" == "y" ]]; then
    read -p "Enter Wapi FRONTEND Git URL: " FRONTEND_REPO
fi
if [[ "$INSTALL_ADMIN" == "y" ]]; then
    read -p "Enter Wapi ADMIN Git URL: " ADMIN_REPO
fi

read -p "Is any of these a private repository? (y/n): " IS_PRIVATE
if [[ "$IS_PRIVATE" == "y" ]]; then
    if [ ! -f ~/.ssh/id_rsa.pub ] && [ ! -f ~/.ssh/id_ed25519.pub ]; then
        echo -e "Generating SSH key..."
        ssh-keygen -t ed25519 -C "wapi-server" -f ~/.ssh/id_ed25519 -N ""
        echo -e "${GREEN}SSH Key Generated!${NC}"
    fi
    echo -e "\n${GREEN}Please add this public key to your Git provider:${NC}"
    if [ -f ~/.ssh/id_ed25519.pub ]; then cat ~/.ssh/id_ed25519.pub; else cat ~/.ssh/id_rsa.pub; fi
    echo -e "\n${YELLOW}Press Enter after adding the key...${NC}"
    read
fi

if [[ "$INSTALL_BACKEND" == "y" ]]; then
    echo -e "\n${YELLOW}--- MongoDB Admin Setup ---${NC}"
    read -p "Enter MongoDB Admin User (default: admin): " MONGO_USER
    MONGO_USER=${MONGO_USER:-admin}
    read -p "Enter MongoDB Admin Password: " MONGO_PASS
    while [[ -z "$MONGO_PASS" ]]; do
        read -p "Password required: " MONGO_PASS
    done
fi


echo -e "\n${YELLOW}--- Step 1: System Update & Tools ---${NC}"
apt update && apt install -y curl wget git unzip gnupg2 software-properties-common nginx certbot python3-certbot-nginx


echo -e "\n${YELLOW}--- Step 2: Node.js 22.x ---${NC}"
if ! command -v node >/dev/null 2>&1 || ! node -v | grep -q "v22"; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y nodejs
fi


if [[ "$INSTALL_BACKEND" == "y" ]]; then
    echo -e "\n${YELLOW}--- Step 3: MongoDB 8.0 ---${NC}"
    if ! command -v mongod >/dev/null 2>&1 || ! mongod --version | grep -q "8.0"; then
        curl -fsSL https://www.mongodb.org/static/pgp/server-8.0.asc | gpg --dearmor -o /usr/share/keyrings/mongodb-server-8.0.gpg --yes
        echo "deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-8.0.gpg ] https://repo.mongodb.org/apt/ubuntu $UBUNTU_CODENAME/mongodb-org/8.0 multiverse" | tee /etc/apt/sources.list.d/mongodb-org-8.0.list
        apt update && apt install -y mongodb-org
    fi
    systemctl start mongod || true
    systemctl enable mongod || true


    echo -ne "Waiting for MongoDB to start..."
    for i in {1..10}; do
        if mongosh --eval "db.adminCommand('ping')" --quiet >/dev/null 2>&1; then
            echo -e "\n${GREEN}MongoDB is ready.${NC}"
            break
        fi
        echo -ne "."
        sleep 2
        if [ $i -eq 10 ]; then
            echo -e "\n${RED}Error: MongoDB failed to start. Check 'systemctl status mongod'.${NC}"
            exit 1
        fi
    done


    USER_EXISTS=$(mongosh admin --quiet --eval "db.getSiblingDB('admin').getUser('$MONGO_USER')")
    if [[ "$USER_EXISTS" == "null" ]]; then
        mongosh admin --eval "db.createUser({ user: '$MONGO_USER', pwd: '$MONGO_PASS', roles: [ { role: 'root', db: 'admin' } ] })"
    fi


    if ! grep -E -q "authorization:\s*\"?enabled\"?" /etc/mongod.conf; then

        sed -i '/^security:/d' /etc/mongod.conf
        sed -i '/authorization:\s*\"?enabled\"?/d' /etc/mongod.conf


        echo -e "\nsecurity:\n  authorization: enabled" >> /etc/mongod.conf
        systemctl restart mongod || echo -e "${RED}Warning: mongod failed to restart. Check systemctl status mongod.${NC}"
    fi
fi


echo -e "\n${YELLOW}--- Step 4: Redis ---${NC}"
if ! command -v redis-server >/dev/null 2>&1; then
    apt install -y redis-server
fi
systemctl start redis || true
systemctl enable redis || true


echo -e "\n${YELLOW}--- Step 5: PM2 ---${NC}"
npm install -g pm2
pm2 startup || true


setup_project() {
    local NAME=$1
    local REPO=$2
    local TYPE=$3

    echo -e "\n${YELLOW}--- Setting Up $NAME ---${NC}"
    mkdir -p /var/www
    cd /var/www

    local DIR=$(basename "$REPO" .git)
    if [ -d "$DIR" ]; then
        cd "$DIR"
        git pull
    else
        git clone "$REPO"
        cd "$DIR"
    fi

    npm install --production

    if [ ! -f .env ]; then
        [ -f .env.example ] && cp .env.example .env || touch .env
    fi

    if [[ "$TYPE" == "backend" ]]; then
        sed -i "s|MONGODB_URI=.*|MONGODB_URI=mongodb://$MONGO_USER:$MONGO_PASS@127.0.0.1:27017/wapi?authSource=admin|g" .env

        echo -e "\n${YELLOW}Running database seeders for $NAME...${NC}"
        npm run seed || echo -e "${RED}Warning: Database seeding failed or hook not found. You can run it manually later.${NC}"

        pm2 start server.js --name "$NAME" || pm2 restart "$NAME"
    else
        echo -e "${YELLOW}Building $NAME...${NC}"
        npm run build
        echo -e "${YELLOW}Starting $NAME with PM2...${NC}"
        pm2 start npm --name "$NAME" -- start || pm2 restart "$NAME"
    fi

    echo -e "\n${YELLOW}--- Domain & SSL Configuration for $NAME ---${NC}"
    read -p "Do you want to map a domain and generate SSL for $NAME? (y/n): " MAP_DOMAIN
    if [[ "$MAP_DOMAIN" == "y" ]]; then
        read -p "Enter Domain Name (e.g., api.example.com): " DOMAIN_NAME
        read -p "Enter the internal port this app runs on (e.g., 3000): " INTERNAL_PORT
        read -p "Enter Admin Email for Let's Encrypt SSL: " SSL_EMAIL

        echo -e "\n${YELLOW}Configuring Nginx Reverse Proxy for $DOMAIN_NAME on port $INTERNAL_PORT...${NC}"

        cat <<EOF > /etc/nginx/sites-available/$DOMAIN_NAME
server {
    listen 80;
    server_name $DOMAIN_NAME;

    location / {
        proxy_pass http://127.0.0.1:$INTERNAL_PORT;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_cache_bypass \$http_upgrade;
    }
}
EOF

        ln -sf /etc/nginx/sites-available/$DOMAIN_NAME /etc/nginx/sites-enabled/
        rm -f /etc/nginx/sites-enabled/default
        systemctl restart nginx

        echo -e "${YELLOW}Running Certbot to generate SSL for $DOMAIN_NAME...${NC}"
        certbot --nginx -d "$DOMAIN_NAME" --non-interactive --agree-tos -m "$SSL_EMAIL"

        echo -e "${GREEN}SSL configured successfully! Your app is now available at https://${DOMAIN_NAME}${NC}"
    fi
}

[[ "$INSTALL_BACKEND" == "y" ]] && setup_project "wapi-backend" "$BACKEND_REPO" "backend"
[[ "$INSTALL_FRONTEND" == "y" ]] && setup_project "wapi-frontend" "$FRONTEND_REPO" "frontend"
[[ "$INSTALL_ADMIN" == "y" ]] && setup_project "wapi-admin" "$ADMIN_REPO" "admin"

pm2 save

echo -e "\n${GREEN}====================================================${NC}"
echo -e "${GREEN}      Installation Completed Successfully!          ${NC}"
echo -e "${GREEN}====================================================${NC}"
pm2 status
