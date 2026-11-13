<?php
use App\Services\Mailer;
use App\Services\Settings;
use App\Services\Tebex;

$b = (array)config('business', []);
$v = static fn(string $k) => trim((string)($b[$k] ?? ''));
$discord = Settings::hasSecret('discord_webhook');
$mail = Mailer::enabled();
$shop = Settings::on('shop_enabled') || Tebex::configured();
$licenses = (bool)\App\Core\Db::value('SELECT 1 FROM services LIMIT 1');
$hosting = $v('hosting');

$sections = ['verantwortlicher' => 'Verantwortlicher', 'grundsaetze' => 'Allgemeines', 'hosting' => 'Hosting und Server-Logfiles', 'cookies' => 'Cookies',
    'anfragen' => 'Anfragen und Auftragsabwicklung', 'konto' => 'Kundenkonto', 'tickets' => 'Support-Tickets', 'belege' => 'Belege'];
if ($mail) { $sections['email'] = 'E-Mail-Benachrichtigungen'; }
if ($discord) { $sections['discord'] = 'Interne Benachrichtigung über Discord'; }
if ($licenses) { $sections['lizenzen'] = 'Gehostete Dienste und Lizenzprüfung'; }
$sections['schutz'] = 'Schutz vor Missbrauch';
if ($shop) { $sections['tebex'] = 'Script-Shop und Tebex'; }
$sections += ['links' => 'Externe Links', 'rechte' => 'Deine Rechte', 'stand' => 'Stand'];
$n = 0;
$h = static function (string $id) use ($sections, &$n): string { $n++; return '<h2 id="' . $id . '">' . $n . '. ' . e($sections[$id]) . '</h2>'; };
?>
<section class="legal">
  <p class="kicker mono">Rechtliches</p>
  <h1>Datenschutzerklärung</h1>
  <p>Diese Erklärung informiert dich darüber, welche personenbezogenen Daten beim Besuch dieser Website und bei der Nutzung der angebotenen Funktionen verarbeitet werden, zu welchem Zweck und auf welcher Rechtsgrundlage.</p>

  <nav class="legal-toc" aria-label="Inhalt">
    <ol><?php foreach ($sections as $id => $title): ?><li><a href="#<?= e($id) ?>"><?= e($title) ?></a></li><?php endforeach ?></ol>
  </nav>

  <?= $h('verantwortlicher') ?>
  <p>Verantwortlich im Sinne der Datenschutz-Grundverordnung (DSGVO) ist:</p>
  <p><?= e($v('owner')) ?><br><?= e($v('street')) ?><br><?= e($v('zip')) ?> <?= e($v('city')) ?><br><?= e($v('country') ?: 'Deutschland') ?><br>
    E-Mail: <a href="mailto:<?= e($v('email')) ?>"><?= e($v('email')) ?></a><?= $v('phone') !== '' ? '<br>Telefon: ' . e($v('phone')) : '' ?></p>

  <?= $h('grundsaetze') ?>
  <p>Ich verarbeite personenbezogene Daten nur, soweit dies für den Betrieb dieser Website, die Bearbeitung deiner Anfragen und die Abwicklung von Aufträgen erforderlich ist. Rechtsgrundlagen sind insbesondere:</p>
  <ul>
    <li>Art. 6 Abs. 1 lit. b DSGVO – Vertragserfüllung und vorvertragliche Maßnahmen (z. B. Anfragen, Angebote, Aufträge, Kundenkonto)</li>
    <li>Art. 6 Abs. 1 lit. c DSGVO – gesetzliche Pflichten (z. B. Aufbewahrung von Belegen)</li>
    <li>Art. 6 Abs. 1 lit. f DSGVO – berechtigte Interessen (z. B. sicherer Betrieb der Website, Schutz vor Missbrauch)</li>
  </ul>
  <p>Es findet keine automatisierte Entscheidungsfindung einschließlich Profiling statt. Analyse- oder Werbedienste werden nicht eingesetzt. Alle Schriftarten und Skripte werden direkt von diesem Server geladen.</p>

  <?= $h('hosting') ?>
  <p>Diese Website wird auf einem Server <?= $hosting !== '' ? 'bei folgendem Anbieter betrieben: ' . e($hosting) : 'eines Hosting-Anbieters betrieben' ?>. Beim Aufruf einer Seite verarbeitet der Webserver automatisch Informationen, die dein Browser übermittelt (Server-Logfiles):</p>
  <ul><li>IP-Adresse</li><li>Datum und Uhrzeit des Zugriffs</li><li>aufgerufene Seite bzw. Datei und HTTP-Statuscode</li><li>übertragene Datenmenge</li><li>Referrer-URL</li><li>Browser und Betriebssystem (User-Agent)</li></ul>
  <p>Die Verarbeitung dient der Auslieferung der Website, ihrer Stabilität und Sicherheit sowie der Abwehr von Angriffen (Art. 6 Abs. 1 lit. f DSGVO). Die Logfiles werden nach 14 Tagen automatisch gelöscht, sofern sie nicht zur Aufklärung eines Sicherheitsvorfalls benötigt werden.</p>
  <p>Die Verbindung zu dieser Website ist per TLS verschlüsselt (erkennbar an „https://“ in der Adresszeile).</p>

  <?= $h('cookies') ?>
  <p>Diese Website setzt ausschließlich ein technisch notwendiges Session-Cookie. Es dient dazu, dich nach der Anmeldung angemeldet zu halten und Formulare vor missbräuchlichen Fremdzugriffen (CSRF) zu schützen. Das Cookie enthält nur eine zufällige Kennung und wird beim Schließen des Browsers gelöscht; die zugehörigen Sitzungsdaten auf dem Server verfallen nach spätestens zwei Stunden Inaktivität.</p>
  <p>Rechtsgrundlage ist § 25 Abs. 2 Nr. 2 TDDDG, da das Cookie für die von dir gewünschten Funktionen unbedingt erforderlich ist, sowie Art. 6 Abs. 1 lit. f DSGVO. Eine Einwilligung ist hierfür nicht erforderlich.</p>

  <?= $h('anfragen') ?>
  <p>Wenn du über das Formular einen Auftrag anfragst, verarbeite ich die von dir angegebenen Daten: Name bzw. Nickname, Kontaktdaten (E-Mail-Adresse und/oder Discord-Name), Projektkategorie und -details, Budget, Wunschtermin und deine Beschreibung. Außerdem speichere ich den weiteren Verlauf: Nachrichten, Angebote und den Bearbeitungsstatus.</p>
  <p>Zu jeder Anfrage gibt es einen privaten Link, über den du den Stand einsehen, antworten und Angebote annehmen kannst. Der Link ist nur dir bekannt; in der Datenbank wird er nicht im Klartext gespeichert.</p>
  <p>Zweck ist die Bearbeitung deiner Anfrage und gegebenenfalls die Durchführung des Auftrags (Art. 6 Abs. 1 lit. b DSGVO). Kommt kein Auftrag zustande, lösche ich die Daten spätestens drei Jahre nach dem letzten Kontakt; bei Aufträgen gelten die gesetzlichen Aufbewahrungsfristen (siehe „Belege“). Auf Wunsch lösche ich eine Anfrage auch früher, soweit keine Aufbewahrungspflicht entgegensteht.</p>

  <?= $h('konto') ?>
  <p>Für ein Kundenkonto speichere ich Anrede, Vor- und Nachname, E-Mail-Adresse, Anschrift und ein mit einem sicheren Verfahren (Argon2id) gehashtes Passwort, optional Telefonnummer, Discord-Name sowie bei Geschäftskunden Firmenname und USt-IdNr. Anschrift, Firma und USt-IdNr. werden für Belege verwendet, die Telefonnummer nur für Rückfragen zu deinem Auftrag. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO. Das Konto bleibt bestehen, bis du es unter Einstellungen → Anmeldung &amp; Sicherheit selbst schließt oder die Löschung verlangst. Dabei werden Konto, Kontaktdaten, Tickets und Anfragen gelöscht; ausgestellte Belege und die zugehörigen Auftragsunterlagen werden bis zum Ablauf der gesetzlichen Aufbewahrungsfristen gesperrt aufbewahrt (Art. 17 Abs. 3 lit. b, Art. 18 DSGVO). Solange eine Rechnung offen ist, kann das Konto nicht selbst geschlossen werden; die zur Durchsetzung der Forderung nötigen Daten bleiben dann bis zur Begleichung gespeichert (Art. 17 Abs. 3 lit. e DSGVO).</p>
  <p>Zum Schutz deines Kontos protokolliere ich sicherheitsrelevante Ereignisse (z. B. Anmeldungen, fehlgeschlagene Anmeldeversuche, Passwort- oder E-Mail-Änderungen) mit Zeitpunkt, Browser und Betriebssystem sowie einer gekürzten IP-Adresse (bei IPv4 ohne das letzte Segment). Du siehst diese Einträge selbst unter Einstellungen → Anmeldung &amp; Sicherheit. Sie werden nach 180 Tagen gelöscht. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO (Schutz vor unbefugtem Zugriff).</p>
  <p>Aktivierst du die Zwei-Faktor-Anmeldung, wird der dafür nötige geheime Schlüssel verschlüsselt gespeichert; Wiederherstellungscodes werden nur als Hashwert gespeichert. Für das Zurücksetzen des Passworts und das Ändern der E-Mail-Adresse erzeuge ich zeitlich begrenzte Einmal-Links, die ebenfalls nur als Hashwert gespeichert werden.</p>

  <?= $h('tickets') ?>
  <p>Wenn du ein Support-Ticket erstellst, speichere ich Betreff, Kategorie und alle Nachrichten des Verlaufs, um dein Anliegen zu bearbeiten (Art. 6 Abs. 1 lit. b DSGVO). Tickets werden gelöscht, wenn sie für die Betreuung nicht mehr benötigt werden, spätestens zusammen mit deinem Kundenkonto.</p>

  <?= $h('belege') ?>
  <p>Für bezahlte Aufträge erstelle ich Belege (Quittungen bzw. Rechnungen) mit Name, gegebenenfalls Anschrift, Leistungsbeschreibung und Betrag. Belege bewahre ich entsprechend den gesetzlichen Pflichten acht Jahre auf (§ 147 AO, § 257 HGB; Art. 6 Abs. 1 lit. c DSGVO).</p>

  <?php if ($mail): ?>
    <?= $h('email') ?>
    <p>Hast du eine E-Mail-Adresse angegeben, erhältst du E-Mails zu deiner Anfrage: eine Eingangsbestätigung mit deinem privaten Link sowie Benachrichtigungen bei neuen Nachrichten, Angeboten und Statusänderungen. Dafür werden deine E-Mail-Adresse, dein Name und der jeweilige Inhalt über meinen Mailserver versendet (Art. 6 Abs. 1 lit. b DSGVO). Werbe-E-Mails versende ich nicht.</p>
  <?php endif ?>

  <?php if ($discord): ?>
    <?= $h('discord') ?>
    <p>Um zeitnah auf Anfragen reagieren zu können, sende ich mir neue Anfragen, Nachrichten und Tickets als interne Benachrichtigung in einen privaten Discord-Kanal. Dabei werden die Inhalte der Anfrage bzw. Nachricht sowie dein Name und deine Kontaktangaben an die Discord Inc., 444 De Haro Street, Suite 200, San Francisco, CA 94107, USA übermittelt. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO (effiziente Bearbeitung von Anfragen).</p>
    <p>Die USA gelten als Drittland. Die Übermittlung erfolgt auf Grundlage der von Discord bereitgestellten Garantien (EU-Standardvertragsklauseln bzw. EU-US Data Privacy Framework). Weitere Informationen: <a href="https://discord.com/privacy" target="_blank" rel="noopener">Datenschutzerklärung von Discord</a>.</p>
  <?php endif ?>

  <?php if ($licenses): ?>
    <?= $h('lizenzen') ?>
    <p>Für gehostete Dienste (z. B. Discord-Bots) speichere ich Bezeichnung, Laufzeit, Preis, Status und einen Verlauf der Änderungen (Verlängerung, Sperre, Kündigung) in deinem Kundenkonto. Der Lizenzschlüssel wird nur als nicht umkehrbarer Hashwert gespeichert.</p>
    <p>Der Bot fragt in regelmäßigen Abständen bei dieser Website ab, ob er laufen darf. Dabei werden der Lizenzschlüssel und optional die Versionsnummer des Bots übermittelt; gespeichert werden der Zeitpunkt der letzten Abfrage und die Versionsnummer. Die IP-Adresse des Servers, auf dem der Bot läuft, wird nur wie unter „Schutz vor Missbrauch“ beschrieben verarbeitet. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO. Vor Ablauf der Laufzeit erhältst du eine Erinnerung per E-Mail. Die Daten werden gelöscht, wenn sie für die Vertragsabwicklung nicht mehr benötigt werden, spätestens zusammen mit deinem Kundenkonto, soweit keine Aufbewahrungspflichten bestehen.</p>
  <?php endif ?>

  <?= $h('schutz') ?>
  <p>Zum Schutz vor Spam und Angriffen (z. B. massenhafte Anfragen oder Anmeldeversuche) zähle ich Zugriffe auf Formulare und Anmeldungen. Dafür wird deine IP-Adresse nicht im Klartext, sondern nur als nicht umkehrbarer, verschlüsselter Hashwert gespeichert und nach spätestens 48 Stunden gelöscht (Art. 6 Abs. 1 lit. f DSGVO).</p>

  <?php if ($shop): ?>
    <?= $h('tebex') ?>
    <p>Im Script-Shop verlinke ich auf meinen Shop bei Tebex. Kauf, Zahlung, Lizenzierung und Auslieferung der Scripts werden vollständig von der Tebex Limited (Vereinigtes Königreich) als Händler abgewickelt; dabei gilt die <a href="https://www.tebex.io/privacy" target="_blank" rel="noopener">Datenschutzerklärung von Tebex</a>. Für das Vereinigte Königreich besteht ein Angemessenheitsbeschluss der EU-Kommission.</p>
    <p>Produktbilder im Script-Shop werden direkt von den Servern von Tebex geladen (Content-Delivery-Netzwerk Amazon CloudFront der Amazon Web Services, Inc.). Dabei wird deine IP-Adresse an diese Server übermittelt, was technisch zur Anzeige der Bilder erforderlich ist (Art. 6 Abs. 1 lit. f DSGVO).</p>
  <?php endif ?>

  <?= $h('links') ?>
  <p>Diese Website enthält Links zu externen Angeboten (z. B. Discord, GitHub). Daten werden an diese Anbieter erst übertragen, wenn du einen Link anklickst; dann gilt die Datenschutzerklärung des jeweiligen Anbieters.</p>

  <?= $h('rechte') ?>
  <p>Du hast jederzeit das Recht auf:</p>
  <ul>
    <li>Auskunft über deine gespeicherten Daten (Art. 15 DSGVO)</li>
    <li>Berichtigung unrichtiger Daten (Art. 16 DSGVO)</li>
    <li>Löschung (Art. 17 DSGVO) und Einschränkung der Verarbeitung (Art. 18 DSGVO)</li>
    <li>Datenübertragbarkeit (Art. 20 DSGVO)</li>
    <li>Widerspruch gegen Verarbeitungen auf Grundlage berechtigter Interessen (Art. 21 DSGVO)</li>
  </ul>
  <p>Wende dich dafür einfach an <a href="mailto:<?= e($v('email')) ?>"><?= e($v('email')) ?></a>. Außerdem hast du das Recht, dich bei einer Datenschutz-Aufsichtsbehörde zu beschweren (Art. 77 DSGVO), insbesondere in dem Bundesland deines Wohnsitzes oder meines Sitzes.</p>

  <?= $h('stand') ?>
  <p>Diese Datenschutzerklärung hat den Stand <?= e(date('m/Y')) ?> und wird angepasst, wenn sich die Website oder die Rechtslage ändern.</p>
</section>
