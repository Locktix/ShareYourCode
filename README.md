ShareYourCode (SYC)

Simple real-time-ish code sharing app built with PHP, HTML, CSS, and JS. No Node required. Uses polling via AJAX to sync code and chat across participants in a room.

Features
- Create or join a room via shareable URL
- Collaborative code editor (CodeMirror via CDN)
- Live code sync with lightweight conflict handling (revision-based)
- Per-room chat
- Language mode and theme selection (stored client-side)

Requirements
- PHP 8.0+ with file write permissions to the `data/` directory
- A web server (Apache/Nginx) or PHP built-in server

Getting Started
1) Ensure the `data/` directory is writable by the web server.
2) Start a PHP server from the project root:
   php -S localhost:8000
3) Open in your browser:
   http://localhost:8000/

Project Structure
- index.php           Landing page (create/join room)
- room.php            Room page with editor and chat
- api/room_state.php  GET: fetch code; POST: update code
- api/chat.php        GET: fetch chat; POST: add chat message
- assets/app.js       Frontend logic (polling / UI wiring)
- assets/styles.css   Basic styling
- data/               Storage directory for rooms and chat JSON files

Notes
- This demo uses file-based JSON storage for simplicity. For production, replace with a database or in-memory store.
- Polling interval defaults to 1000ms; you can adjust in `assets/app.js`.


