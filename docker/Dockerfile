FROM nginx:1.15-alpine

COPY nginx.conf /etc/nginx/conf.d/default.conf

RUN mkdir -p /app/public/api && touch /app/public/api/index.php