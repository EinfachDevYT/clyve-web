<?php
[$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
$masked = mb_substr($local, 0, 2) . str_repeat('•', max(1, min(6, mb_strlen($local) - 2))) . '@' . $domain;
ob_start();
?>
<h1>Bestätige deine E-Mail</h1>
<p class="muted">Wir haben dir einen Link an <b class="mono"><?= e($masked) ?></b> geschickt. Klick darauf, um dein Konto freizuschalten – danach bist du direkt angemeldet.</p>

<ol class="verify-steps">
  <li>Postfach öffnen – auch im Spam-Ordner nachsehen</li>
  <li>E-Mail „Bitte bestätige deine E-Mail-Adresse“ öffnen</li>
  <li>Auf „E-Mail bestätigen“ klicken</li>
</ol>

<form action="/bestaetigen" method="post" class="stack" data-native>
  <?= csrf_field() ?>
  <button class="pill pill-block pill-ghost" type="submit">E-Mail erneut senden</button>
</form>
<p class="auth-alt">Falsche Adresse? <a href="/registrieren">Neu registrieren</a> · <a href="/login">Zur Anmeldung</a></p>
<?php App\Core\View::partial('site/auth/_frame', ['inner' => ob_get_clean(), 'mode' => 'verify']) ?>
