# 📺 hianime-API (PHP Edition)

**hianime-API** is an unofficial REST API that scrapes anime data from **hianimez.to**.  
It provides endpoints for anime discovery, details, episodes, servers, and streaming links.

**This is a complete PHP conversion of the original Node.js/Bun API.**

> ⚠️ **Important**
>
> - This API is **unofficial** and not affiliated with hianimez.to
> - No hosted instance exists — **deploy your own**
> - All content belongs to its original owners
> - This project is for **educational and personal use only**

---

## ✨ Features

- Anime home page data (trending, spotlight, top airing, etc.)
- Search & search suggestions
- Detailed anime information  
- Episode lists with streaming servers
- HLS streaming links with AES decryption
- Redis caching support
- Rate limiting middleware
- CORS enabled
- Clean MVC architecture

---

## 💻 Installation

### Prerequisites

- **PHP 8.1+** (required)
- **Composer** (for dependency management)
- **Apache/Nginx** web server
- **PHP Extensions:**
  - `php-curl`
  - `php-json`
  - `php-mbstring`
  - `php-openssl`
  - `php-dom`
- **Redis** (optional, for caching)

---

### Local Setup

1. **Clone the repository**

```bash
git clone https://github.com/druvx13/hianime-API.git
cd hianime-API
```

2. **Install dependencies**

```bash
composer install
```

3. **Configure environment**

Copy `.env.php` and configure:

```bash
cp .env.php .env
```

Edit `.env`:

```ini
ORIGIN=*
UPSTASH_REDIS_REST_URL=your_redis_url_here
UPSTASH_REDIS_REST_TOKEN=your_redis_token_here
RATE_LIMIT_WINDOW_MS=60000
RATE_LIMIT_LIMIT=100
DEBUG=false
```

4. **Set up web server**

#### Apache (with mod_rewrite)

The `.htaccess` file is already included. Just make sure:
- `mod_rewrite` is enabled
- `AllowOverride All` is set for the directory

#### Nginx

Add to your nginx config:

```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/hianime-API;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

5. **Start the server**

If using PHP's built-in server (development only):

```bash
php -S localhost:8080
```

Then visit:
```
http://localhost:8080
```

For documentation:
```
http://localhost:8080/doc
```

---

## 📚 API Documentation

Base URL:
```
/api/v1
```

All responses follow:

```json
{
  "success": true,
  "data": {}
}
```

---

### 🏠 Home Page

```http
GET /api/v1/home
```

Returns spotlight, trending, top airing, latest episodes, genres, and more.

---

### 🔍 Search

```http
GET /api/v1/search?keyword={query}&page={page}
```

Search for anime by keyword.

**Parameters:**
- `keyword` (required) - Search query
- `page` (optional) - Page number (default: 1)

#### Suggestions

```http
GET /api/v1/search/suggestion?keyword={query}
```

Get search suggestions as you type.

---

### 📺 Episode Servers

```http
GET /api/v1/servers?id={episodeId}
```

Get available streaming servers for an episode.

**Parameters:**
- `id` (required) - Episode ID

---

### 🎬 Streaming

```http
GET /api/v1/stream?id={episodeId}&server={server}&type={sub|dub}
```

Get HLS streaming links with subtitles and intro/outro timestamps.

**Parameters:**
- `id` (required) - Episode ID
- `server` (required) - Server name (e.g., "hd-1", "hd-2")
- `type` (optional) - Type: "sub", "dub", or "raw" (default: "sub")

Returns:
- HLS m3u8 links
- Subtitle tracks
- Intro/outro timestamps
- Server information

---

## 🏗️ Architecture

```
src/
├── Config/          # Configuration files
├── Lib/             # Core application (Router, App)
├── Middlewares/     # CORS, Rate Limiting
├── Modules/         # API modules (Home, Search, Stream, etc.)
├── Routes/          # API route definitions
├── Services/        # HTTP Client, external services
├── Utils/           # Helper functions, Response, Errors
└── Views/           # HTML templates (landing, docs)
```

---

## 🔧 Technology Stack

- **PHP 8.1+** - Core language
- **Symfony DomCrawler** - HTML parsing
- **Predis** - Redis client
- **OpenSSL** - AES decryption for streams
- **cURL** - HTTP requests

---

## 🐳 Docker Deployment (Coming Soon)

Docker support will be added in a future update.

---

## 📝 Differences from Original

This PHP version maintains feature parity with the original Node.js/Bun version:

✅ All API endpoints implemented  
✅ MegaCloud stream decryption working  
✅ Redis caching support  
✅ Rate limiting  
✅ CORS middleware  
✅ Error handling  
✅ HTML/CSS documentation pages

**Key differences:**
- Uses Composer instead of npm/bun
- PHP classes instead of JavaScript modules
- Symfony DomCrawler instead of Cheerio
- OpenSSL for AES instead of crypto-js
- Simple router instead of Hono framework

---

## 👨‍💻 Development

### Running Tests

Tests will be added in a future update.

### Code Style

Follow PSR-12 coding standards:

```bash
composer require --dev squizlabs/php_codesniffer
vendor/bin/phpcs src/
```

---

## 🤝 Contributing

Pull requests are welcome! For major changes:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

---

## 🌟 Credits

- Original API: [yahyamomin/hianime-API](https://github.com/yahyamomin/hianime-API)
- PHP conversion: druvx13
- Inspired by: consumet.ts, api.consumet.org

---

## ⚠️ Disclaimer

This project is for **educational purposes only**. The developers are not responsible for any misuse of this API. All anime content belongs to their respective owners. Please support the official releases.

---

## 📄 License

MIT License - see LICENSE file for details

---

## 🌟 Support

If this project helped you, please star the repository!

---

**Made with ❤️ by the community**
