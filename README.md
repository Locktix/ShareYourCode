ShareYourCode (SYC)

Static HTML/CSS/JS version (GitHub Pages friendly). Room state and chat are stored in the browser's localStorage.

Features
- Create or join a room via shareable URL
- Code editor (CodeMirror via CDN)
- Per-room chat (stored locally)
- Language mode and theme selection (stored client-side)

Limitations
- No server backend: no real multi-user sync across different devices/browsers
- Data is per-browser (localStorage). Different users will not see each other's changes

Getting Started
1) Open `index.html` directly (or serve the folder with any static server)
2) Create or join a room; edits and chat are saved in localStorage per room

Project Structure
- index.html          Landing page (create/join room)
- room.html           Room page with editor and chat
- assets/app.js       Frontend logic (localStorage-based)
- assets/styles.css   Basic styling

Notes
- Polling interval defaults to 1000ms; used for cross-tab refresh within same browser


