
A Demo of the App can be found on my website:   https://www.thekilani.com/camunda/public/


# PHP Animal Picture App (Microservice)

A small dependency-free PHP microservice (plus a tiny UI) that fetches random pictures of **cats**, **dogs**, or **bears**, stores them in **SQLite**, and exposes REST endpoints to retrieve them.

Image sources (as requested):
- Cats: `https://cataas.com/cat`
- Dogs: `https://place.dog/300/200`

for this link i had actually to do a rand function - like this  `https://placebear.com/rand(x)/rand(y)` for some reason for this url if i send 200/300 each time i get the same photo so that fixed it apperantly. 
- Bears: `https://placebear.com/200/300`

## Features

- `POST /api/pictures/fetch` fetches **N** images for an animal and stores them in SQLite
- `GET /api/pictures/last?animal=cat|dog|bear` returns the most recently stored picture metadata + image URL
- `GET /api/pictures/{id}/image` serves the stored image bytes
- `GET /api/stats` shows counts per animal
- `POST /api/pictures/clear` deletes all stored images (helper for testing / demo)
- Simple UI at `/` to request/show images and counts

The UI also includes a **Delete All** button that calls `POST /api/pictures/clear`.

## Data storage

- SQLite file: `data/app.sqlite`
- Table: `pictures` (stores animal, mime type, image bytes, source URL, created timestamp)

The DB file is created automatically on first run.

---

## Run without Docker (Windows / XAMPP)

> XAMPP the command below uses **full paths**.

From your project folder (or anywhere), run:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8080 -t C:\xampp\htdocs\php-animal-picture-app\public C:\xampp\htdocs\php-animal-picture-app\public\router.php
```

Then open:
- UI: `http://127.0.0.1:8080/`
- Example API:
  - `GET http://127.0.0.1:8080/api/stats`

> Note: this app uses the PHP extensions **cURL** and **SQLite/PDO SQLite**.

### If you host under a subdirectory (Apache)

This app can run under a sub-path like `/camunda/public`.

- The base path is **auto-detected** from `SCRIPT_NAME`.
- You can override it explicitly with an env var:
  - `APP_BASE_PATH=/camunda/public`

This affects routing and the UI’s API calls (so `/camunda/public/api/...` works).

For Apache/XAMPP routing to work (so `/api/...` doesn’t 404 at Apache level), make sure:
- `mod_rewrite` is enabled
- the directory allows `.htaccess` overrides (AllowOverride All)

Then you can use:
- UI: `http://localhost/camunda/public/` (preferred)
- API: `http://localhost/camunda/public/api/stats`

---

## Run with Docker

Build and run:

```bash
docker build -t php-animal-picture-app .
docker run --rm -p 8080:8080 -v "$(pwd)/data:/app/data" php-animal-picture-app
```

Open:
- `http://127.0.0.1:8080/`

### Or with docker compose

```bash
docker compose up --build
```

---

## REST API

### 1) Fetch & save pictures

**Request**

`POST /api/pictures/fetch`

```json
{
  "animal": "cat",
  "count": 3
}
```

**Response**

```json
{
  "animal": "cat",
  "count": 3,
  "saved": [
    {"id": 1, "animal": "cat", "imageUrl": "/api/pictures/1/image"}
  ]
}
```

### 2) Get last stored picture for an animal

`GET /api/pictures/last?animal=cat`

Returns JSON containing `imageUrl` you can load in the browser.

### 3) Delete all stored images

`POST /api/pictures/clear`

Response:

```json
{ "deleted": 12 }
```

---

## Notes / tradeoffs

- Images are stored as BLOBs in SQLite for portability.
- No framework and no Composer dependencies (portable + easy to run).
