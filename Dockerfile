# Multi-stage Production Dockerfile for PHP Notes App (Supabase / PostgreSQL)

# ============================================
# Stage 1: Builder - Prepare application files
# ============================================
FROM php:8.2-cli-alpine AS builder

WORKDIR /app

# Copy application source
COPY . .

# Remove unnecessary files for production (keep app code only)
RUN rm -rf \
    .git \
    .gitignore \
    .dockerignore \
    Dockerfile \
    docker-compose*.yml \
    *.md \
    tests \
    .env \
    .env.example \
    test_db.php \
    render.yaml \
    .vscode \
    .idea

# ============================================
# Stage 2: Production - Final runtime image
# ============================================
# Uses Supabase (PostgreSQL). Set DATABASE_URL at runtime (e.g. Supabase connection string).
FROM php:8.2-apache AS production

# Install dependencies and PostgreSQL extension for Supabase
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    curl \
    && docker-php-ext-install pdo pdo_pgsql opcache \
    && apt-get purge -y libpq-dev \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache to listen on PORT env var (required by Render)
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Set working directory
WORKDIR /var/www/html

# Copy application from builder stage
COPY --from=builder /app .

# Set ownership and permissions (database is Supabase/PostgreSQL, no local data dir)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Security hardening + opcache for performance
RUN { \
    echo "session.cookie_httponly = 1"; \
    echo "session.cookie_secure = 1"; \
    echo "session.use_strict_mode = 1"; \
    echo "expose_php = Off"; \
    echo "display_errors = Off"; \
    echo "log_errors = On"; \
    echo "error_log = /var/log/apache2/php_errors.log"; \
    echo "opcache.enable = 1"; \
    echo "opcache.enable_cli = 0"; \
    echo "opcache.validate_timestamps = 0"; \
    echo "opcache.max_accelerated_files = 10000"; \
    echo "opcache.memory_consumption = 128"; \
    echo "opcache.interned_strings_buffer = 8"; \
} >> "$PHP_INI_DIR/php.ini"

# Set environment variable for port
ENV PORT=10000

# Expose the port
EXPOSE ${PORT}

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:${PORT}/ || exit 1

# Start Apache (runs as root, drops to www-data for worker processes)
CMD ["apache2-foreground"]
