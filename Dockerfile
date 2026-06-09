FROM php:8.3-cli-alpine

RUN apk add --no-cache curl-dev libcurl openssl-dev \
    && docker-php-ext-install curl \
    && docker-php-ext-enable openssl || true

# Chromium for UX/CRO screenshots
RUN apk add --no-cache \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ca-certificates \
    ttf-freefont \
    && ln -sf /usr/bin/chromium-browser /usr/bin/chromium 2>/dev/null || true

ENV CHROMIUM_PATH=/usr/bin/chromium

WORKDIR /app
COPY . .

EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t . router.php"]
