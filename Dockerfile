FROM php:8.2-fpm

# Cài đặt các thư viện hệ thống cần thiết
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt các PHP extension cho Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Lấy Composer từ image chính thức
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Thiết lập thư mục làm việc
WORKDIR /var/www

# Copy toàn bộ code nguồn vào container
COPY . /var/www

# 1. Cài đặt các gói vendor cho Laravel khi build
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 2. Xóa link cũ và tạo lại symlink trực tiếp bằng Linux (tránh lỗi php artisan storage:link đã tồn tại)
RUN rm -rf /var/www/public/storage && ln -sfn /var/www/storage/app/public /var/www/public/storage

# 3. Phân quyền thư mục ghi log, cache & storage cho Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/public

# 4. Mở cổng 10000 và tự động chạy server Laravel
EXPOSE 10000
CMD php artisan migrate --force && php artisan config:clear && php artisan serve --host=0.0.0.0 --port=10000