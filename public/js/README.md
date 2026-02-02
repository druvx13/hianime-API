# HiAnime API - JavaScript Client Library

A pure vanilla JavaScript client library for the HiAnime API. **No dependencies required** - works in any modern browser!

## Features

✅ **Zero Dependencies** - Pure vanilla JavaScript  
✅ **Browser Ready** - Works directly in the browser (no build step)  
✅ **Promise-based** - Modern async/await syntax  
✅ **Error Handling** - Comprehensive error handling with custom error class  
✅ **Timeout Support** - Configurable request timeouts  
✅ **Type Safe** - Well-documented methods with JSDoc  

## Installation

### Option 1: Direct Include (Browser)

```html
<script src="/public/js/hianime-api-client.js"></script>
<script>
    const api = new HiAnimeAPI('/api/v1');
    // Use the API...
</script>
```

### Option 2: Download and Host

Download `hianime-api-client.js` and host it on your server:

```html
<script src="/path/to/hianime-api-client.js"></script>
```

## Quick Start

```javascript
// Initialize the client
const api = new HiAnimeAPI('/api/v1');

// Get home page data
const homeData = await api.getHome();
console.log(homeData.spotlight);
console.log(homeData.trending);

// Search for anime
const searchResults = await api.search('naruto', 1);
console.log(searchResults.results);

// Get search suggestions
const suggestions = await api.getSuggestions('one');
console.log(suggestions);

// Get episode servers
const servers = await api.getServers('steinsgate-3?ep=1');
console.log(servers.sub);  // Sub servers
console.log(servers.dub);  // Dub servers

// Get streaming links
const stream = await api.getStream('steinsgate-3?ep=1', 'hd-1', 'sub');
console.log(stream.link.file);  // HLS m3u8 URL
console.log(stream.tracks);     // Subtitle tracks
console.log(stream.intro);      // Intro timestamps
console.log(stream.outro);      // Outro timestamps
```

## API Reference

### Constructor

```javascript
const api = new HiAnimeAPI(baseURL, options);
```

**Parameters:**
- `baseURL` (string, optional) - Base URL for the API (default: `/api/v1`)
- `options` (object, optional) - Configuration options
  - `timeout` (number) - Request timeout in milliseconds (default: 30000)
  - `headers` (object) - Additional headers to send with requests

**Example:**
```javascript
const api = new HiAnimeAPI('/api/v1', {
    timeout: 60000,
    headers: {
        'X-Custom-Header': 'value'
    }
});
```

### Methods

#### `getHome()`

Get home page data including spotlight, trending, top airing, etc.

```javascript
const data = await api.getHome();
```

**Returns:** Promise<object>

**Response structure:**
```javascript
{
    success: true,
    data: {
        spotlight: [...],
        trending: [...],
        topAiring: [...],
        mostPopular: [...],
        // ... more data
    }
}
```

---

#### `search(keyword, page)`

Search for anime by keyword.

```javascript
const results = await api.search('naruto', 1);
```

**Parameters:**
- `keyword` (string, required) - Search keyword
- `page` (number, optional) - Page number (default: 1)

**Returns:** Promise<object>

---

#### `getSuggestions(keyword)`

Get search suggestions as you type.

```javascript
const suggestions = await api.getSuggestions('one');
```

**Parameters:**
- `keyword` (string, required) - Search keyword

**Returns:** Promise<object>

---

#### `getServers(episodeId)`

Get available streaming servers for an episode.

```javascript
const servers = await api.getServers('steinsgate-3?ep=1');
```

**Parameters:**
- `episodeId` (string, required) - Episode ID

**Returns:** Promise<object>

**Response structure:**
```javascript
{
    success: true,
    data: {
        sub: [
            { id: '...', name: 'hd-1', type: 'sub', index: 0 },
            { id: '...', name: 'hd-2', type: 'sub', index: 1 }
        ],
        dub: [...],
        raw: [...]
    }
}
```

---

#### `getStream(episodeId, server, type)`

Get streaming links with subtitles and timestamps.

```javascript
const stream = await api.getStream('steinsgate-3?ep=1', 'hd-1', 'sub');
```

**Parameters:**
- `episodeId` (string, required) - Episode ID
- `server` (string, required) - Server name (e.g., 'hd-1', 'hd-2')
- `type` (string, optional) - Stream type: 'sub', 'dub', or 'raw' (default: 'sub')

**Returns:** Promise<object>

**Response structure:**
```javascript
{
    success: true,
    data: {
        id: '...',
        type: 'sub',
        link: {
            file: 'https://...m3u8',  // HLS stream URL
            type: 'hls'
        },
        tracks: [
            { file: 'https://...', kind: 'captions', label: 'English' },
            // ... more subtitle tracks
        ],
        intro: { start: 90, end: 180 },
        outro: { start: 1320, end: 1420 },
        server: 'hd-1'
    }
}
```

---

#### `ping()`

Check if the API is alive.

```javascript
const isOnline = await api.ping();
console.log('API is', isOnline ? 'online' : 'offline');
```

**Returns:** Promise<boolean>

---

## Error Handling

The client throws `HiAnimeAPIError` for all API-related errors:

```javascript
try {
    const data = await api.search('', 1);  // Empty keyword
} catch (error) {
    if (error instanceof HiAnimeAPIError) {
        console.error('API Error:', error.message);
        console.error('Status Code:', error.statusCode);
        console.error('Details:', error.details);
    } else {
        console.error('Unexpected error:', error);
    }
}
```

**HiAnimeAPIError properties:**
- `message` (string) - Error message
- `statusCode` (number) - HTTP status code (0 for network errors)
- `details` (object) - Additional error details from the API

---

## Complete Example

Here's a complete example of building a simple anime search app:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Anime Search</title>
</head>
<body>
    <h1>Anime Search</h1>
    
    <input type="text" id="searchInput" placeholder="Search anime...">
    <button onclick="searchAnime()">Search</button>
    
    <div id="results"></div>

    <script src="/public/js/hianime-api-client.js"></script>
    <script>
        const api = new HiAnimeAPI('/api/v1');
        
        async function searchAnime() {
            const keyword = document.getElementById('searchInput').value;
            
            if (!keyword) {
                alert('Please enter a search keyword');
                return;
            }
            
            try {
                const response = await api.search(keyword, 1);
                const results = response.data.results;
                
                const resultsDiv = document.getElementById('results');
                resultsDiv.innerHTML = '';
                
                results.forEach(anime => {
                    const div = document.createElement('div');
                    div.innerHTML = `
                        <h3>${anime.title}</h3>
                        <img src="${anime.poster}" alt="${anime.title}" width="200">
                        <p>Type: ${anime.type}</p>
                        <p>Episodes: ${anime.episodes.eps}</p>
                    `;
                    resultsDiv.appendChild(div);
                });
            } catch (error) {
                console.error('Search failed:', error);
                alert('Search failed: ' + error.message);
            }
        }
    </script>
</body>
</html>
```

---

## Browser Compatibility

Works in all modern browsers that support:
- ES6 Classes
- Async/await
- Fetch API
- AbortController

Supported browsers:
- ✅ Chrome 55+
- ✅ Firefox 52+
- ✅ Safari 11+
- ✅ Edge 79+

---

## License

MIT License - See main project for details.

---

## Support

For issues or questions:
- API Documentation: `/doc`
- Interactive Playground: `/playground`
- GitHub Issues: [Create an issue](https://github.com/druvx13/hianime-API/issues)

---

**Made for LAMP Stack** 🚀  
Pure client-side JavaScript - No Node.js required!
