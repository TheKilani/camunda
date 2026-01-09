<?php

declare(strict_types=1);

namespace App;

final class Ui
{
    public static function homePageHtml(string $basePath): string
    {
        $basePath = $basePath === '' ? '' : '/' . trim($basePath, '/');

        $escapedBasePath = htmlspecialchars($basePath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $html = <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="app-base-path" content="__APP_BASE_PATH__" />
  <title>Animal Picture App</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 24px; }
    .row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    .card { border: 1px solid #ddd; border-radius: 10px; padding: 16px; max-width: 900px; }
    .stats { display: flex; gap: 16px; flex-wrap: wrap; }
    .stat { padding: 8px 10px; border: 1px solid #eee; border-radius: 8px; }
    img { max-width: 100%; height: auto; border-radius: 10px; border: 1px solid #eee; }
    input, select, button { padding: 8px 10px; }
    button { cursor: pointer; }
    .error { color: #b00020; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; margin-top: 12px; }
  </style>
</head>
<body>
<img src="./i.jpg" alt="Profile" style="width: 100px; height: 100px; float: right; z-index: 10; position: relative; margin-left: 15px; margin-bottom: 15px;" />
<p style="float: right; z-index: 10; position: relative; clear: right; margin-right: 0; text-align: right; font-weight: bold;">This is Me!</p>

  <h1>Animal Picture App</h1>

  <div class="card">
    <div class="row">
      <label>
        Animal:
        <select id="animal">
          <option value="cat">Cat</option>
          <option value="dog">Dog</option>
          <option value="bear">Bear</option>
        </select>
      </label>
      <label>
        Count:
        <input id="count" type="number" min="1" max="25" value="1" />
      </label>
      <button id="fetchBtn">Fetch & Save</button>
      <button id="lastBtn">Show Last</button>
      <button id="clearBtn">Delete & Clear SQLite DB</button>
    </div>

    <p id="msg" class="error" style="display:none;"></p>
    <p id="info" style="display:none;"></p>

    <h3>Stats</h3>
    <div class="stats" id="stats"></div>

    <h3>Most recent image</h3>
    <div id="preview"></div>

    <h3>Fetched this request</h3>
    <div class="grid" id="grid"></div>
  </div>

<script>
  const el = (id) => document.getElementById(id)
  const msgEl = el('msg')
  const infoEl = el('info')
  const BASE = document.querySelector('meta[name="app-base-path"]')?.getAttribute('content') || ''

  function showError(text) {
    msgEl.textContent = text
    msgEl.style.display = 'block'
  }

  function clearError() {
    msgEl.textContent = ''
    msgEl.style.display = 'none'
  }

  function showInfo(text) {
    infoEl.textContent = text
    infoEl.style.display = 'block'
    infoEl.style.color = '#0b6b0b'
  }

  function clearInfo() {
    infoEl.textContent = ''
    infoEl.style.display = 'none'
  }

  async function refreshStats() {
    const res = await fetch(`${BASE}/api/stats`)
    const data = await res.json()
    const stats = el('stats')
    stats.innerHTML = `
      <div class="stat">Cats: <b>${data.cat}</b></div>
      <div class="stat">Dogs: <b>${data.dog}</b></div>
      <div class="stat">Bears: <b>${data.bear}</b></div>
      <div class="stat">Total: <b>${data.total}</b></div>
    `
  }

  function showPreview(imgUrl) {
    el('preview').innerHTML = `<img src="${imgUrl}" alt="animal" />`
  }

  function showGrid(items) {
    const grid = el('grid')
    grid.innerHTML = items.map((it) => `<img src="${it.imageUrl}" alt="${it.animal}" />`).join('')
  }

  el('fetchBtn').addEventListener('click', async () => {
    clearError()
    clearInfo()
    el('grid').innerHTML = ''

    const animal = el('animal').value
    const count = Number(el('count').value || '1')

    try {
      const res = await fetch(`${BASE}/api/pictures/fetch`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ animal, count }),
      })

  el('clearBtn').addEventListener('click', async () => {
    clearError()
    clearInfo()

    const ok = confirm('Delete ALL stored images from SQLite?')
    if (!ok) return

    try {
      const res = await fetch(`${BASE}/api/pictures/clear`, { method: 'POST' })
      const data = await res.json()
      if (!res.ok) {
        showError(data.error || 'Request failed')
        return
      }

      el('grid').innerHTML = ''
      el('preview').innerHTML = ''
      showInfo(`Deleted ${data.deleted ?? 0} records.`)
      await refreshStats()
    } catch (e) {
      showError(String(e))
    }
  })

      const data = await res.json()
      if (!res.ok) {
        showError(data.error || 'Request failed')
        return
      }

      showGrid(data.saved || [])
      if (data.saved && data.saved.length) {
        showPreview(data.saved[data.saved.length - 1].imageUrl)
      }
      await refreshStats()
    } catch (e) {
      showError(String(e))
    }
  })

  el('lastBtn').addEventListener('click', async () => {
    clearError()
    clearInfo()
    el('grid').innerHTML = ''

    const animal = el('animal').value
    try {
      const res = await fetch(`${BASE}/api/pictures/last?animal=${encodeURIComponent(animal)}`)
      const data = await res.json()
      if (!res.ok) {
        showError(data.error || 'Request failed')
        return
      }

      showPreview(data.imageUrl)
      await refreshStats()
    } catch (e) {
      showError(String(e))
    }
  })

  refreshStats().catch((e) => showError(String(e)))
</script>
</body>
</html>
HTML;

        return str_replace('__APP_BASE_PATH__', $escapedBasePath, $html);
    }
}
