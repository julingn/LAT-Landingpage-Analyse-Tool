FROM php:8.3-cli-alpine

RUN apk add --no-cache curl-dev libcurl openssl-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install curl zip mbstring \
    && docker-php-ext-enable openssl || true

# Chromium + Node.js for UX/CRO screenshots (Puppeteer)
RUN apk add --no-cache \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ca-certificates \
    ttf-freefont \
    nodejs \
    npm \
    && ln -sf /usr/bin/chromium-browser /usr/bin/chromium 2>/dev/null || true

ENV CHROMIUM_PATH=/usr/bin/chromium
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium
ENV PHP_CLI_SERVER_WORKERS=4

WORKDIR /app
COPY package*.json ./
RUN npm install --omit=dev 2>/dev/null || true
COPY . .

EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t . router.php"]
