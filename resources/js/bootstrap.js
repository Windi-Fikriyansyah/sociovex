import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ── Laravel Echo + Reverb WebSocket ────────────────────────────
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Auto-detect the correct WebSocket host:
// - If VITE_REVERB_HOST is set, use it (can be ngrok domain or localhost)
// - Otherwise, use the current page's hostname (works with ngrok)
const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const reverbPort = import.meta.env.VITE_REVERB_PORT || 8080;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http');
const forceTLS = reverbScheme === 'https';

console.log('[Echo] Connecting to:', reverbScheme + '://' + reverbHost + ':' + reverbPort);

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'sk_sociovex_reverb',
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: forceTLS,
    enabledTransports: forceTLS ? ['wss'] : ['ws'],
    disabledTransports: forceTLS ? ['ws'] : ['wss'],
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                window.axios.post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name,
                })
                .then(response => {
                    console.log('[Echo] Auth success for', channel.name);
                    callback(false, response.data);
                })
                .catch(error => {
                    console.error('[Echo] Auth failed for', channel.name, error);
                    callback(true, error);
                });
            },
        };
    },
});
