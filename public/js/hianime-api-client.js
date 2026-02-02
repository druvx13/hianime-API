/**
 * HiAnime API Client
 * 
 * A pure vanilla JavaScript client library for the HiAnime API.
 * No dependencies required - works in any browser!
 * 
 * @version 1.0.0
 * @author HiAnime API Team
 * @license MIT
 * 
 * Usage:
 * const api = new HiAnimeAPI('/api/v1');
 * 
 * // Get home page data
 * api.getHome().then(data => console.log(data));
 * 
 * // Search anime
 * api.search('naruto', 1).then(data => console.log(data));
 * 
 * // Get stream links
 * api.getStream('anime-id?ep=1', 'hd-1', 'sub').then(data => console.log(data));
 */

class HiAnimeAPI {
    /**
     * Initialize the API client
     * @param {string} baseURL - Base URL for the API (default: '/api/v1')
     * @param {object} options - Configuration options
     */
    constructor(baseURL = '/api/v1', options = {}) {
        this.baseURL = baseURL.replace(/\/$/, ''); // Remove trailing slash
        this.options = {
            timeout: options.timeout || 30000, // 30 seconds default
            headers: options.headers || {},
            ...options
        };
    }

    /**
     * Make HTTP request
     * @private
     */
    async _request(endpoint, params = {}) {
        // Build URL with query parameters
        const url = new URL(this.baseURL + endpoint, window.location.origin);
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.append(key, params[key]);
            }
        });

        // Create abort controller for timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.options.timeout);

        try {
            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    ...this.options.headers
                },
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            // Parse JSON response
            const data = await response.json();

            // Check if response was successful
            if (!response.ok) {
                throw new HiAnimeAPIError(
                    data.message || `HTTP ${response.status}: ${response.statusText}`,
                    response.status,
                    data
                );
            }

            return data;
        } catch (error) {
            clearTimeout(timeoutId);

            if (error.name === 'AbortError') {
                throw new HiAnimeAPIError('Request timeout', 408);
            }

            if (error instanceof HiAnimeAPIError) {
                throw error;
            }

            throw new HiAnimeAPIError(error.message, 0, { originalError: error });
        }
    }

    /**
     * Get home page data
     * Returns spotlight, trending, top airing, latest episodes, genres, and more.
     * 
     * @returns {Promise<object>} Home page data
     * 
     * @example
     * const data = await api.getHome();
     * console.log(data.spotlight, data.trending);
     */
    async getHome() {
        return this._request('/home');
    }

    /**
     * Search anime by keyword
     * 
     * @param {string} keyword - Search keyword
     * @param {number} page - Page number (default: 1)
     * @returns {Promise<object>} Search results
     * 
     * @example
     * const results = await api.search('naruto', 1);
     * console.log(results.results);
     */
    async search(keyword, page = 1) {
        if (!keyword) {
            throw new HiAnimeAPIError('Keyword is required for search', 400);
        }

        return this._request('/search', { keyword, page });
    }

    /**
     * Get search suggestions
     * 
     * @param {string} keyword - Search keyword
     * @returns {Promise<object>} Suggestions
     * 
     * @example
     * const suggestions = await api.getSuggestions('one');
     * console.log(suggestions);
     */
    async getSuggestions(keyword) {
        if (!keyword) {
            throw new HiAnimeAPIError('Keyword is required for suggestions', 400);
        }

        return this._request('/search/suggestion', { keyword });
    }

    /**
     * Get episode servers
     * 
     * @param {string} episodeId - Episode ID
     * @returns {Promise<object>} Available servers (sub, dub, raw)
     * 
     * @example
     * const servers = await api.getServers('steinsgate-3?ep=1');
     * console.log(servers.sub, servers.dub);
     */
    async getServers(episodeId) {
        if (!episodeId) {
            throw new HiAnimeAPIError('Episode ID is required', 400);
        }

        return this._request('/servers', { id: episodeId });
    }

    /**
     * Get streaming links
     * 
     * @param {string} episodeId - Episode ID
     * @param {string} server - Server name (e.g., 'hd-1', 'hd-2')
     * @param {string} type - Stream type: 'sub', 'dub', or 'raw' (default: 'sub')
     * @returns {Promise<object>} Stream data with HLS links, subtitles, intro/outro
     * 
     * @example
     * const stream = await api.getStream('steinsgate-3?ep=1', 'hd-1', 'sub');
     * console.log(stream.link.file); // HLS m3u8 URL
     * console.log(stream.tracks);    // Subtitle tracks
     */
    async getStream(episodeId, server, type = 'sub') {
        if (!episodeId) {
            throw new HiAnimeAPIError('Episode ID is required', 400);
        }

        if (!server) {
            throw new HiAnimeAPIError('Server name is required', 400);
        }

        if (!['sub', 'dub', 'raw'].includes(type)) {
            throw new HiAnimeAPIError('Type must be sub, dub, or raw', 400);
        }

        return this._request('/stream', { id: episodeId, server, type });
    }

    /**
     * Ping the API to check if it's alive
     * 
     * @returns {Promise<boolean>} True if API is responsive
     * 
     * @example
     * const isAlive = await api.ping();
     * console.log('API is', isAlive ? 'online' : 'offline');
     */
    async ping() {
        try {
            const response = await fetch('/ping');
            return response.ok && await response.text() === 'pong';
        } catch {
            return false;
        }
    }
}

/**
 * Custom error class for API errors
 */
class HiAnimeAPIError extends Error {
    constructor(message, statusCode = 0, details = null) {
        super(message);
        this.name = 'HiAnimeAPIError';
        this.statusCode = statusCode;
        this.details = details;
    }
}

// Export for module systems (if available)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { HiAnimeAPI, HiAnimeAPIError };
}

// Also make available globally for browser
if (typeof window !== 'undefined') {
    window.HiAnimeAPI = HiAnimeAPI;
    window.HiAnimeAPIError = HiAnimeAPIError;
}
