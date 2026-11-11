<?php
use App\Services\Hosting;
use App\Services\InvoiceService;
$state = Hosting::state($s);
$left = Hosting::daysLeft($s);
$progress = Hosting::termProgress($s);
$url = Hosting::apiUrl();
$interval = Hosting::checkInterval();
$snippets = [
    'Python (discord.py)' => <<<PY
# pip install discord.py aiohttp
import asyncio
import os

import aiohttp
import discord
from discord.ext import tasks

LICENSE_URL = "{$url}"
LICENSE_KEY = os.environ["CLYVE_LICENSE_KEY"]
OFFLINE_GRACE = 6 * 60 * 60  # Sekunden, die der Bot ohne erreichbare Lizenz-API weiterläuft


class LicensedClient(discord.Client):
    async def setup_hook(self) -> None:
        self.offline_since = None
        if not await self.license_ok():
            raise SystemExit("Lizenz ungültig – Bot wird nicht gestartet.")
        self.license_loop.start()

    async def license_ok(self) -> bool:
        loop = asyncio.get_running_loop()
        try:
            timeout = aiohttp.ClientTimeout(total=10)
            headers = {"Authorization": f"Bearer {LICENSE_KEY}", "X-Bot-Version": "1.0.0"}
            async with aiohttp.ClientSession(timeout=timeout) as http:
                async with http.get(LICENSE_URL, headers=headers) as r:
                    if r.status >= 500 or r.status == 429:
                        raise aiohttp.ClientError(f"HTTP {r.status}")
                    data = await r.json()
        except (aiohttp.ClientError, asyncio.TimeoutError, ValueError):
            self.offline_since = self.offline_since or loop.time()
            return loop.time() - self.offline_since < OFFLINE_GRACE
        self.offline_since = None
        if not data.get("valid"):
            print(f"[Lizenz] {data.get('message')}")
        return data.get("valid") is True

    @tasks.loop(seconds={$interval})
    async def license_loop(self) -> None:
        if not await self.license_ok():
            await self.close()


client = LicensedClient(intents=discord.Intents.default())
client.run(os.environ["DISCORD_TOKEN"])
PY,
    'JavaScript (discord.js)' => <<<JS
// npm i discord.js   (Node 18+)
const { Client, GatewayIntentBits } = require('discord.js');

const LICENSE_URL = '{$url}';
const LICENSE_KEY = process.env.CLYVE_LICENSE_KEY;
const OFFLINE_GRACE = 6 * 60 * 60 * 1000; // ms ohne erreichbare Lizenz-API
let offlineSince = null;

async function licenseOk() {
  let data;
  try {
    const r = await fetch(LICENSE_URL, {
      headers: { Authorization: `Bearer \${LICENSE_KEY}`, 'X-Bot-Version': '1.0.0' },
      signal: AbortSignal.timeout(10000),
    });
    if (r.status >= 500 || r.status === 429) throw new Error(`HTTP \${r.status}`);
    data = await r.json();
  } catch {
    offlineSince ??= Date.now();
    return Date.now() - offlineSince < OFFLINE_GRACE;
  }
  offlineSince = null;
  if (!data.valid) console.error(`[Lizenz] \${data.message}`);
  return data.valid === true;
}

const client = new Client({ intents: [GatewayIntentBits.Guilds] });

(async () => {
  if (!(await licenseOk())) process.exit(1);
  setInterval(async () => {
    if (!(await licenseOk())) { await client.destroy(); process.exit(1); }
  }, {$interval} * 1000);
  await client.login(process.env.DISCORD_TOKEN);
})();
JS,
];
?>
<header class="page-head">
  <div>
    <p class="kicker mono"><a href="/hosting">Hosting</a> / #<?= (int)$s['id'] ?></p>
    <h1><?= e($s['name']) ?></h1>
    <p class="meta"><?= Hosting::badge($s) ?> <a href="/kunden/<?= (int)$s['customer_id'] ?>"><?= e($s['customer_name']) ?></a>
      <span><?= e(Hosting::priceLabel($s)) ?></span><?php if ($s['plan_name']): ?><span>Tarif: <?= e($s['plan_name']) ?></span><?php endif ?></p>
  </div>
</header>

<?php if ($key): ?>
  <section class="panel glass license-once stack">
    <div><h2>Lizenzschlüssel</h2><p class="muted small">Wird nur jetzt angezeigt und nicht im Klartext gespeichert. Trag ihn als Umgebungsvariable <span class="mono">CLYVE_LICENSE_KEY</span> beim Bot ein.</p></div>
    <div class="license-key"><code class="mono"><?= e($key) ?></code><button type="button" class="pill pill-sm" data-copy="<?= e($key) ?>">Kopieren</button></div>
  </section>
<?php endif ?>

<?php if ($s['status'] === 'suspended'): ?>
  <div class="notice notice-warn"><b><?= $s['suspended_by'] === 'auto' ? 'Automatisch gesperrt' : 'Manuell gesperrt' ?></b> am <?= date_de($s['suspended_at'], true) ?><?= $s['suspended_reason'] !== '' ? ': ' . e($s['suspended_reason']) : '' ?>.
    <?= $s['suspended_by'] === 'auto' ? 'Eine Verlängerung hebt die Sperre automatisch auf.' : '' ?></div>
<?php elseif ($state === 'grace' || $state === 'overdue'): ?>
  <div class="notice notice-warn">Die Laufzeit ist seit <?= date_de($s['expires_on']) ?> abgelaufen.
    <?= $state === 'grace' ? 'Kulanzzeit bis ' . date_de(Hosting::graceUntil($s)) . ', danach wird der Dienst gesperrt.' : 'Automatische Sperre ist ausgeschaltet – der Bot läuft weiter.' ?></div>
<?php endif ?>

<div class="request-layout">
  <div class="stack-lg">
    <section class="panel glass stack">
      <h2>Laufzeit</h2>
      <dl class="kv">
        <dt>Start</dt><dd><?= date_de($s['starts_on']) ?></dd>
        <dt>Läuft bis</dt><dd><?= $s['expires_on'] ? date_de($s['expires_on']) : 'Unbefristet' ?></dd>
        <?php if ($left !== null && $s['status'] === 'active'): ?>
          <dt>Restlaufzeit</dt><dd><?= $left >= 0 ? $left . ' ' . ($left === 1 ? 'Tag' : 'Tage') : 'abgelaufen seit ' . -$left . ' ' . (-$left === 1 ? 'Tag' : 'Tagen') ?></dd>
        <?php endif ?>
        <dt>Abrechnung</dt><dd><?= e(Hosting::priceLabel($s)) ?></dd>
      </dl>
      <?php if ($progress !== null && $s['status'] === 'active'): ?>
        <div class="progress-bar" title="<?= $progress ?> % der Laufzeit"><span data-progress="<?= $progress ?>"></span></div>
      <?php endif ?>
    </section>

    <section class="panel glass stack">
      <div><h2>Einbindung in den Bot</h2>
        <p class="muted small">Der Bot fragt alle <?= (int)round($interval / 60) ?> Min. <span class="mono"><?= e($url) ?></span> ab und beendet sich, sobald der Dienst gesperrt, gekündigt oder abgelaufen ist. Ist die API nicht erreichbar, läuft er bis zu 6 Stunden weiter. Startet dein Container automatisch neu, nutze <span class="mono">restart: on-failure</span> mit Limit.</p></div>
      <?php foreach ($snippets as $label => $code): ?>
        <details class="snippet">
          <summary class="panel-summary"><b><?= e($label) ?></b></summary>
          <div class="snippet-body"><button type="button" class="chip-btn" data-copy="<?= e($code) ?>">Kopieren</button><pre class="code"><code><?= e($code) ?></code></pre></div>
        </details>
      <?php endforeach ?>
      <details class="snippet">
        <summary class="panel-summary"><b>Antwort der API</b></summary>
        <pre class="code"><code><?= e(json_encode(Hosting::payload($s), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></code></pre>
      </details>
    </section>

    <details class="panel glass">
      <summary class="panel-summary"><b>Details bearbeiten</b></summary>
      <form class="stack mt" action="/hosting/<?= (int)$s['id'] ?>" method="post">
        <?= csrf_field() ?>
        <label class="field<?= invalid('name') ?>"><span>Name</span><input type="text" name="name" maxlength="120" required value="<?= e(old('name', $s['name'])) ?>"><?= error_for('name') ?></label>
        <div class="grid-3">
          <label class="field<?= invalid('price') ?>"><span>Preis (€)</span><input type="text" name="price" inputmode="decimal" value="<?= e(old('price', number_format((int)$s['price_cents'] / 100, 2, ',', ''))) ?>"><?= error_for('price') ?></label>
          <label class="field<?= invalid('term_months') ?>"><span>Laufzeit</span>
            <select name="term_months">
              <?php foreach (Hosting::TERMS as $m => $label): ?><option value="<?= $m ?>"<?= (int)old('term_months', (string)$s['term_months']) === $m ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?>
            </select><?= error_for('term_months') ?></label>
          <label class="field<?= invalid('expires_on') ?>"><span>Läuft bis</span><input type="date" name="expires_on" value="<?= e(old('expires_on', (string)$s['expires_on'])) ?>"><?= error_for('expires_on') ?></label>
        </div>
        <label class="consent"><input type="checkbox" name="unlimited" value="1"<?= $s['expires_on'] ? '' : ' checked' ?>><span>Unbefristet (kein Ablaufdatum)</span></label>
        <label class="field"><span>Interne Notiz</span><textarea name="notes" rows="3" maxlength="2000"><?= e(old('notes', $s['notes'])) ?></textarea></label>
        <div class="row-end"><button class="pill pill-sm" type="submit">Speichern</button></div>
      </form>
    </details>

    <section class="panel glass">
      <h2>Verlauf</h2>
      <ul class="timeline mt-sm">
        <?php foreach ($events as $ev): ?>
          <li><span class="muted small"><?= date_de($ev['created_at'], true) ?></span>
            <span><?= e($ev['detail']) ?> <small class="muted">· <?= e($ev['actor'] === 'system' ? 'Automatik' : preg_replace('/^staff:/', '', $ev['actor'])) ?><?= $ev['visible'] ? '' : ' · intern' ?></small></span></li>
        <?php endforeach ?>
      </ul>
    </section>
  </div>

  <aside>
    <?php if ($s['status'] !== 'cancelled'): ?>
      <form class="panel glass stack" action="/hosting/<?= (int)$s['id'] ?>/verlaengern" method="post">
        <?= csrf_field() ?>
        <h2>Verlängern</h2>
        <label class="field"><span>Um</span>
          <select name="months">
            <?php foreach ([1, 3, 6, 12, 24] as $m): ?><option value="<?= $m ?>"<?= $m === max(1, (int)$s['term_months']) ? ' selected' : '' ?>><?= e(Hosting::termLabel($m)) ?></option><?php endforeach ?>
          </select></label>
        <?php if ((int)$s['price_cents'] > 0 && (int)$s['term_months'] > 0): ?>
          <label class="consent"><input type="checkbox" name="invoice" value="1" checked><span><?= e(InvoiceService::docLabel()) ?>-Entwurf erstellen</span></label>
        <?php endif ?>
        <button class="pill pill-sm" type="submit">Verlängern</button>
      </form>

      <?php if ($s['status'] === 'active'): ?>
        <form class="panel glass stack" action="/hosting/<?= (int)$s['id'] ?>/sperren" method="post" data-confirm="Dienst jetzt sperren? Der Bot beendet sich bei der nächsten Prüfung.">
          <?= csrf_field() ?>
          <h2>Sperren</h2>
          <label class="field"><span>Grund <small class="muted">(sieht Kunde und Bot)</small></span><input type="text" name="reason" maxlength="200" placeholder="z. B. Zahlung offen"></label>
          <button class="pill pill-sm pill-ghost danger" type="submit">Sperren</button>
        </form>
      <?php else: ?>
        <form class="panel glass stack" action="/hosting/<?= (int)$s['id'] ?>/entsperren" method="post">
          <?= csrf_field() ?>
          <h2>Sperre</h2>
          <p class="muted small">Seit <?= date_de($s['suspended_at'], true) ?>.</p>
          <button class="pill pill-sm" type="submit">Entsperren</button>
        </form>
      <?php endif ?>
    <?php endif ?>

    <section class="panel glass stack">
      <h2>Lizenz</h2>
      <dl class="kv">
        <dt>Schlüssel</dt><dd class="mono"><?= e($s['license_hint'] ?: '–') ?></dd>
        <dt>Letzte Prüfung</dt><dd><?= $s['last_check_at'] ? date_de($s['last_check_at'], true) : 'noch nie' ?></dd>
        <?php if ($s['last_version'] !== ''): ?><dt>Version</dt><dd class="mono"><?= e($s['last_version']) ?></dd><?php endif ?>
      </dl>
      <form action="/hosting/<?= (int)$s['id'] ?>/schluessel" method="post" data-confirm="Neuen Schlüssel erzeugen? Der alte funktioniert sofort nicht mehr.">
        <?= csrf_field() ?><button class="pill pill-sm pill-ghost" type="submit">Neuen Schlüssel erzeugen</button>
      </form>
    </section>

    <form class="panel glass stack" action="/hosting/<?= (int)$s['id'] ?>/kuendigen" method="post" data-confirm="<?= $s['status'] === 'cancelled' ? 'Dienst reaktivieren?' : 'Dienst kündigen? Der Lizenzschlüssel wird ungültig.' ?>">
      <?= csrf_field() ?>
      <h2><?= $s['status'] === 'cancelled' ? 'Gekündigt' : 'Kündigen' ?></h2>
      <button class="link-btn<?= $s['status'] === 'cancelled' ? '' : ' danger' ?>" type="submit"><?= $s['status'] === 'cancelled' ? 'Dienst reaktivieren' : 'Dienst kündigen' ?></button>
    </form>
  </aside>
</div>
