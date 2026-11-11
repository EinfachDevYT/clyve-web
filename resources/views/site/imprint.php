<?php
$b = (array)config('business', []);
$v = static fn(string $k) => trim((string)($b[$k] ?? ''));
?>
<section class="legal">
  <p class="kicker mono">Rechtliches</p>
  <h1>Impressum</h1>

  <h2>Angaben gemäß § 5 DDG</h2>
  <p>
    <?= e($v('owner')) ?><br>
    <?= e($v('street')) ?><br>
    <?= e($v('zip')) ?> <?= e($v('city')) ?><br>
    <?= e($v('country') ?: 'Deutschland') ?>
  </p>
  <?php if ($v('name') !== '' && $v('name') !== $v('owner')): ?>
    <p>Website: <?= e($v('name')) ?></p>
  <?php endif ?>

  <h2>Kontakt</h2>
  <p>
    E-Mail: <a href="mailto:<?= e($v('email')) ?>"><?= e($v('email')) ?></a>
    <?php if ($v('phone') !== ''): ?><br>Telefon: <?= e($v('phone')) ?><?php endif ?>
  </p>

  <?php if ($v('vat_id') !== ''): ?>
    <h2>Umsatzsteuer-ID</h2>
    <p>Umsatzsteuer-Identifikationsnummer gemäß § 27a Umsatzsteuergesetz: <?= e($v('vat_id')) ?></p>
  <?php endif ?>

  <h2>Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV</h2>
  <p><?= e($v('owner')) ?><br><?= e($v('street')) ?><br><?= e($v('zip')) ?> <?= e($v('city')) ?></p>

  <h2>Verbraucherstreitbeilegung</h2>
  <p>Ich bin nicht bereit und nicht verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>

  <h2>Haftung für Inhalte</h2>
  <p>Die Inhalte dieser Website wurden mit größtmöglicher Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte kann ich jedoch keine Gewähr übernehmen. Als Diensteanbieter bin ich gemäß § 7 Abs. 1 DDG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Nach §§ 8 bis 10 DDG bin ich jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen. Bei Bekanntwerden von Rechtsverletzungen werde ich derartige Inhalte umgehend entfernen.</p>

  <h2>Haftung für Links</h2>
  <p>Diese Website enthält Links zu externen Websites Dritter, auf deren Inhalte ich keinen Einfluss habe. Für diese fremden Inhalte ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich. Zum Zeitpunkt der Verlinkung waren keine Rechtsverstöße erkennbar. Bei Bekanntwerden von Rechtsverletzungen werde ich derartige Links umgehend entfernen.</p>

  <h2>Urheberrecht</h2>
  <p>Die auf dieser Website erstellten Inhalte, Grafiken und Logos unterliegen dem deutschen Urheberrecht. Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechts bedürfen der schriftlichen Zustimmung des jeweiligen Rechteinhabers.</p>

  <h2>Design &amp; Entwicklung</h2>
  <p><a href="https://einfachdev.de" target="_blank" rel="noopener">EinfachDev</a></p>
</section>
