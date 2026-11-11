<section id="start" class="hero" data-title="">
  <svg class="branch branch-l" viewBox="0 0 520 420" aria-hidden="true">
    <path d="M-20 30 C 120 60, 210 40, 300 120 S 420 230, 360 330" />
    <path d="M120 -10 C 140 80, 100 140, 200 190" class="thin" />
    <path d="M300 120 C 360 100, 420 110, 470 70" class="thin" />
  </svg>
  <svg class="branch branch-r" viewBox="0 0 520 420" aria-hidden="true">
    <path d="M540 380 C 420 350, 360 300, 300 220 S 220 120, 260 20" />
    <path d="M300 220 C 240 240, 180 230, 120 270" class="thin" />
  </svg>

  <div class="hero-inner">
    <p class="eyebrow mono"><span class="dot<?= $openTypes ? '' : ' dot-off' ?>"></span> <?= e($settings['availability_label']) ?></p>
    <h1>
      Dein Projekt.<br />
      <span class="grad">Einfach</span> umgesetzt.
    </h1>
    <p class="lead">
      Ich entwickle <b>FiveM Scripts</b>, <b>Websites</b> und <b>Discord Bots</b> –
      sauber, performant und genau so, wie du sie brauchst.
    </p>
    <p class="typer mono" aria-live="polite"><span id="typer"></span><span class="caret">▍</span></p>
    <div class="hero-cta">
      <a class="pill" href="#anfrage">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4.4 15.2c-1.4 1.2-1.8 4.6-1.8 4.6s3.4-.4 4.6-1.8c.7-.8.6-2-.1-2.7a1.9 1.9 0 0 0-2.7-.1z"/><path d="M11.5 14 8 10.5a20 20 0 0 1 1.9-3.6C12.2 3.4 15.6 2.3 20 2.3c0 4.4-1.1 7.8-4.6 10.1A20 20 0 0 1 11.5 14z"/><path d="M8.4 11.2H4.2s.5-2.8 1.9-3.7"/><path d="M12.8 15.6v4.2s2.8-.5 3.7-1.9"/></g></svg>
        Auftrag anfragen
      </a>
      <a class="pill pill-ghost" href="https://discord.com/users/267311011520970752" target="_blank" rel="noopener">Direkt auf Discord</a>
    </div>
    <ul class="hero-facts mono">
      <li><b>Saarland</b><span>Standort</span></li>
      <li><b>Lua · Py · PHP · JS</b><span>Sprachen</span></li>
      <li><b><?= e($settings['response_time']) ?></b><span>Antwortzeit</span></li>
    </ul>
  </div>
</section>

<div class="divider" aria-hidden="true"><span></span><img src="/assets/img/blossom.svg" alt="" /><span></span></div>

<section id="leistungen" class="section" data-title="Leistungen">
  <header class="section-head">
    <p class="kicker mono">01 — Leistungen</p>
    <h2>Was ich für dich baue</h2>
  </header>

  <div class="cards">
    <article class="card reveal" data-service="fivem">
      <div class="card-icon">🚗</div>
      <h3>FiveM Scripts</h3>
      <p>Performante Resources für Roleplay-Server – Jobs, NUI-Interfaces, Systeme &amp; Custom Props/Maps.</p>
      <ul class="ticks">
        <li>ESX · QBCore · Qbox</li>
        <li>Optimiert auf 0.00 ms Idle</li>
        <li>Saubere Configs &amp; Locales</li>
      </ul>
      <a class="card-link" href="#anfrage" data-pick="fivem">Script anfragen →</a>
    </article>

    <article class="card reveal" data-service="web">
      <div class="card-icon">🌐</div>
      <h3>Websites</h3>
      <p>Moderne Web-Plattformen mit Dashboards, Login &amp; Ticket-Systemen – auf Wunsch selbstgehostet.</p>
      <ul class="ticks">
        <li>PHP · JavaScript · Tailwind</li>
        <li>Responsive &amp; schnell</li>
        <li>Hosting &amp; Deployment</li>
      </ul>
      <a class="card-link" href="#anfrage" data-pick="web">Website anfragen →</a>
    </article>

    <article class="card reveal" data-service="bot">
      <div class="card-icon">🤖</div>
      <h3>Discord Bots</h3>
      <p>discord.py Bots mit Cog-Struktur: Tickets, Bewerbungen, Verify, Welcome, Rollen &amp; mehr.</p>
      <ul class="ticks">
        <li>Slash-Commands &amp; Buttons</li>
        <li>Datenbank-Anbindung</li>
        <li>24/7 Hosting möglich</li>
      </ul>
      <a class="card-link" href="#anfrage" data-pick="bot">Bot anfragen →</a>
    </article>
  </div>
</section>

<section id="ablauf" class="section" data-title="Ablauf">
  <header class="section-head">
    <p class="kicker mono">02 — Ablauf</p>
    <h2>Von der Idee zum Release</h2>
  </header>

  <ol class="steps">
    <li class="step reveal">
      <span class="step-n mono">01</span>
      <h3>Anfrage</h3>
      <p>Du beschreibst dein Projekt über das Formular – je genauer, desto besser.</p>
    </li>
    <li class="step reveal">
      <span class="step-n mono">02</span>
      <h3>Angebot</h3>
      <p>Ich melde mich mit Rückfragen, Zeitrahmen und einem fairen Festpreis.</p>
    </li>
    <li class="step reveal">
      <span class="step-n mono">03</span>
      <h3>Entwicklung</h3>
      <p>Regelmäßige Updates &amp; Previews, damit du immer weißt, wo das Projekt steht.</p>
    </li>
    <li class="step reveal">
      <span class="step-n mono">04</span>
      <h3>Übergabe</h3>
      <p>Fertiges Projekt inkl. Einrichtung, kurzer Doku und Support nach Release.</p>
    </li>
  </ol>
</section>

<div class="divider" aria-hidden="true"><span></span><img src="/assets/img/blossom.svg" alt="" /><span></span></div>

<section id="anfrage" class="section" data-title="Auftrag anfragen">
  <header class="section-head">
    <p class="kicker mono">03 — Anfrage</p>
    <h2>Erzähl mir von deinem Projekt</h2>
    <p class="sub">Kostenlos &amp; unverbindlich. Ich antworte in der Regel innerhalb von <?= e($settings['response_time']) ?>. 🌸</p>
  </header>

  <?php
    $typeLabels = ['fivem' => '🚗 FiveM Script', 'web' => '🌐 Website', 'bot' => '🤖 Discord Bot', 'other' => '✨ Etwas anderes'];
    $typeHints = [
      'fivem' => 'Jobs, Systeme, NUI, MLOs, Anpassungen',
      'web'   => 'Landingpages, Portale, Dashboards',
      'bot'   => 'Tickets, Bewerbungen, Verify & mehr',
      'other' => 'Etwas, das nicht in die Liste passt',
    ];
  ?>

  <?php if (!$openTypes): ?>
    <div class="closed-panel glass">
      <img src="/assets/brand/clyve-mark.svg" alt="" width="56" height="56">
      <h3>Gerade keine neuen Aufträge</h3>
      <p><?= e($settings['requests_closed_message']) ?></p>
      <a class="pill pill-ghost" href="https://discord.com/users/267311011520970752" target="_blank" rel="noopener">Auf Discord schreiben</a>
    </div>
  <?php else: ?>
  <form class="form glass wizard" id="requestForm" data-json<?= $user ? ' data-logged-in="1"' : '' ?> action="/anfrage" method="post">
    <?= csrf_field() ?>

    <ol class="steps-nav" aria-hidden="true">
      <li data-step-dot="1"><span>1</span>Kategorie</li>
      <li data-step-dot="2"><span>2</span>Details</li>
      <li data-step-dot="3"><span>3</span>Beschreibung</li>
      <li data-step-dot="4"><span>4</span>Kontakt</li>
    </ol>

    <fieldset class="wizard-step" data-step="1">
      <legend class="step-title">Was soll entstehen? <i>*</i></legend>
      <div class="type-cards" role="radiogroup">
        <?php foreach ($typeLabels as $key => $label): $open = in_array($key, $openTypes, true); ?>
          <label class="type-card<?= $open ? '' : ' is-closed' ?>">
            <input type="radio" name="type" value="<?= e($key) ?>"<?= $open ? '' : ' disabled' ?> required>
            <span class="type-card-body">
              <b><?= e($label) ?></b>
              <small><?= $open ? e($typeHints[$key]) : 'Gerade geschlossen' ?></small>
            </span>
          </label>
        <?php endforeach ?>
      </div>
      <p class="hint">Fertige Scripts findest du im <a href="/scripts">Script-Shop</a>.</p>
    </fieldset>

    <fieldset class="wizard-step" data-step="2">
      <legend class="step-title">Ein paar Details</legend>
      <?php foreach ($details as $type => $fields): if (!$fields) continue; ?>
        <div class="type-details" data-for-type="<?= e($type) ?>">
          <?php foreach ($fields as $key => $f): ?>
            <?php if ($f['kind'] === 'multi'): ?>
              <fieldset class="field">
                <legend><?= e($f['label']) ?> <small class="muted">(mehrere möglich)</small></legend>
                <div class="chips">
                  <?php foreach ($f['options'] as $opt): ?>
                    <label class="chip"><input type="checkbox" name="details[<?= e($key) ?>][]" value="<?= e($opt) ?>"><span><?= e($opt) ?></span></label>
                  <?php endforeach ?>
                </div>
              </fieldset>
            <?php else: ?>
              <label class="field"><span><?= e($f['label']) ?></span>
                <select name="details[<?= e($key) ?>]">
                  <option value="">Bitte wählen…</option>
                  <?php foreach ($f['options'] as $opt): ?><option><?= e($opt) ?></option><?php endforeach ?>
                </select>
              </label>
            <?php endif ?>
          <?php endforeach ?>
        </div>
      <?php endforeach ?>

      <label class="field">
        <span>Projekttitel</span>
        <input type="text" name="title" maxlength="120" placeholder="z. B. Garagen-System mit NUI">
      </label>
      <div class="grid-2">
        <label class="field">
          <span>Budget</span>
          <select name="budget">
            <option value="">Noch unklar</option>
            <?php foreach ($budgets as $b): ?><option><?= e($b) ?></option><?php endforeach ?>
          </select>
        </label>
        <label class="field">
          <span>Wunschtermin</span>
          <input type="date" name="deadline">
        </label>
      </div>
    </fieldset>

    <fieldset class="wizard-step" data-step="3">
      <legend class="step-title">Beschreib dein Projekt <i>*</i></legend>
      <label class="field">
        <span class="sr-only">Beschreibung</span>
        <textarea name="message" rows="8" maxlength="4000" required placeholder="Was soll es können? Wie soll es aussehen? Gibt es Beispiele oder Referenzen (Links)?"></textarea>
        <small class="counter mono"><span id="msgCount">0</span> / 4000 · mindestens 20 Zeichen</small>
      </label>
    </fieldset>

    <fieldset class="wizard-step" data-step="4">
      <legend class="step-title">Wie erreiche ich dich?</legend>
      <div class="grid-2">
        <label class="field">
          <span>Name / Nickname <i>*</i></span>
          <input type="text" name="name" maxlength="80" autocomplete="nickname" required placeholder="z. B. Max" value="<?= e($user['name'] ?? '') ?>">
        </label>
        <label class="field">
          <span>Discord-Name</span>
          <input type="text" name="discord" maxlength="32" placeholder="z. B. max.dev" value="<?= e($user['discord'] ?? '') ?>">
        </label>
      </div>
      <label class="field">
        <span>E-Mail <small class="muted">(empfohlen – dann bekommst du Antworten direkt zugeschickt)</small></span>
        <input type="email" name="email" maxlength="160" autocomplete="email" placeholder="du@beispiel.de" value="<?= e($user['email'] ?? '') ?>">
      </label>
      <p class="hint" id="contactHint">Mindestens Discord <b>oder</b> E-Mail angeben.</p>

      <div class="summary" id="requestSummary" hidden></div>

      <label class="hp" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>

      <label class="consent">
        <input type="checkbox" name="consent" value="1" required>
        <span>Ich bin damit einverstanden, dass meine Angaben zur Bearbeitung der Anfrage gespeichert werden. Details in der <a href="/datenschutz">Datenschutzerklärung</a>. <i>*</i></span>
      </label>
    </fieldset>

    <div class="form-foot wizard-foot">
      <button class="pill pill-ghost" type="button" data-step-prev hidden>Zurück</button>
      <button class="pill" type="button" data-step-next hidden>Weiter</button>
      <button class="pill" type="submit" id="submitBtn">
        <span class="btn-label">Anfrage absenden</span>
        <span class="spinner" aria-hidden="true"></span>
      </button>
      <p class="form-status" id="formStatus" role="status"></p>
    </div>
  </form>

  <div class="form glass success-panel" id="requestSuccess" hidden>
    <img src="/assets/brand/clyve-mark.svg" alt="" width="56" height="56">
    <h3 id="successTitle">Danke!</h3>
    <p id="successText"></p>
    <a class="pill" id="successLink" href="#">Anfrage ansehen</a>
    <p class="hint">Speichere dir den Link – darüber siehst du den Stand, kannst mir antworten und Angebote annehmen.</p>
  </div>
  <?php endif ?>
</section>

<section id="faq" class="section" data-title="FAQ">
  <header class="section-head">
    <p class="kicker mono">04 — FAQ</p>
    <h2>Häufige Fragen</h2>
  </header>

  <div class="faq">
    <details class="glass reveal">
      <summary>Was kostet ein Projekt?</summary>
      <p>Das hängt vom Umfang ab. Nach deiner Anfrage bekommst du einen transparenten Festpreis – ohne versteckte Kosten.</p>
    </details>
    <details class="glass reveal">
      <summary>Wie wird bezahlt?</summary>
      <p>Üblicherweise 50 % zum Start und 50 % bei Übergabe. Kleine Aufträge können auch komplett bei Übergabe bezahlt werden.</p>
    </details>
    <details class="glass reveal">
      <summary>Bekomme ich den Quellcode?</summary>
      <p>Ja. Du erhältst den vollständigen, unverschlüsselten Code – sauber strukturiert und kommentiert.</p>
    </details>
    <details class="glass reveal">
      <summary>Gibt es Support nach der Übergabe?</summary>
      <p>Klar. Bugs, die auf meinen Code zurückgehen, behebe ich kostenlos. Erweiterungen sprechen wir gerne separat ab.</p>
    </details>
  </div>
</section>
  
