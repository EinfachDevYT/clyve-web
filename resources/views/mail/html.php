<?php
use App\Services\Mailer;

$m = $message;
$font = "'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif";
$mono = "'JetBrains Mono', 'SFMono-Regular', Consolas, 'Liberation Mono', monospace";
$host = $siteUrl !== '' ? (string)parse_url($siteUrl, PHP_URL_HOST) : '';
?>
<!doctype html>
<html lang="de" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="color-scheme" content="dark">
  <meta name="supported-color-schemes" content="dark">
  <title><?= e($subject) ?></title>
  <style>
    body { margin: 0; padding: 0; width: 100% !important; background: #0D0B14; }
    a { color: #C084FC; }
    @media (max-width: 620px) {
      .card { border-radius: 0 !important; border-left: 0 !important; border-right: 0 !important; }
      .pad { padding-left: 24px !important; padding-right: 24px !important; }
      .h1 { font-size: 22px !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background:#0D0B14;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#0D0B14;"><?= e($preheader) ?>&#8204;&nbsp;&#8204;&nbsp;&#8204;&nbsp;&#8204;&nbsp;&#8204;&nbsp;&#8204;&nbsp;</div>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0D0B14;background-image:radial-gradient(600px 300px at 50% 0%, #2A1550 0%, #0D0B14 70%);">
    <tr>
      <td align="center" style="padding:36px 12px 28px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;">
          <tr>
            <td align="center" style="padding:0 0 26px;">
              <a href="<?= e($siteUrl ?: '#') ?>" style="text-decoration:none;"><img src="cid:logo@clyve" width="150" height="44" alt="<?= e($business) ?>" style="display:block;border:0;width:150px;height:auto;color:#F5F0FF;font-family:<?= $font ?>;font-size:22px;font-weight:700;"></a>
            </td>
          </tr>
          <tr>
            <td class="card" style="background:#171226;border:1px solid #3B2A5C;border-radius:20px;overflow:hidden;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr><td style="height:4px;line-height:4px;font-size:0;background:#A855F7;background-image:linear-gradient(90deg,#6D28D9,#A855F7,#F0ABFC);">&nbsp;</td></tr>
                <tr>
                  <td class="pad" style="padding:34px 40px 8px;font-family:<?= $font ?>;">
                    <p style="margin:0 0 10px;font-family:<?= $mono ?>;font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#A855F7;">🌸 <?= e($business) ?></p>
                    <h1 class="h1" style="margin:0 0 22px;font-size:25px;line-height:1.25;font-weight:800;color:#F5F0FF;"><?= e($subject) ?></h1>
                    <?php if ($m['greeting'] !== ''): ?>
                      <p style="margin:0 0 16px;font-size:16px;line-height:1.65;color:#F5F0FF;"><?= e($m['greeting']) ?></p>
                    <?php endif ?>
                    <?php foreach ($m['lines'] as $line): ?>
                      <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#D8CCF2;"><?= Mailer::richText((string)$line) ?></p>
                    <?php endforeach ?>
                  </td>
                </tr>
                <?php if ($m['facts']): ?>
                <tr>
                  <td class="pad" style="padding:6px 40px 10px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0F0C1A;border:1px solid #2E2147;border-radius:14px;">
                      <?php $i = 0; foreach ($m['facts'] as $k => $v): ?>
                        <tr>
                          <td style="padding:10px 16px;<?= $i++ ? 'border-top:1px solid #241A38;' : '' ?>font-family:<?= $mono ?>;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#8B7BAE;white-space:nowrap;vertical-align:top;width:1%;"><?= e((string)$k) ?></td>
                          <td style="padding:10px 16px;<?= $i > 1 ? 'border-top:1px solid #241A38;' : '' ?>font-family:<?= $font ?>;font-size:14px;line-height:1.5;color:#F5F0FF;"><?= Mailer::richText((string)$v) ?></td>
                        </tr>
                      <?php endforeach ?>
                    </table>
                  </td>
                </tr>
                <?php endif ?>
                <?php if ($m['quote'] !== ''): ?>
                <tr>
                  <td class="pad" style="padding:10px 40px 10px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr><td style="border-left:3px solid #A855F7;background:#120E20;border-radius:0 12px 12px 0;padding:14px 18px;font-family:<?= $font ?>;font-size:14px;line-height:1.65;color:#D8CCF2;"><?= Mailer::richText($m['quote']) ?></td></tr>
                    </table>
                  </td>
                </tr>
                <?php endif ?>
                <?php if ($m['action']): [$label, $url] = $m['action']; ?>
                <tr>
                  <td class="pad" align="left" style="padding:16px 40px 6px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td align="center" style="border-radius:999px;background:#9333EA;background-image:linear-gradient(135deg,#7C3AED,#A855F7);box-shadow:0 8px 24px -8px rgba(168,85,247,.7);">
                          <a href="<?= e($url) ?>" style="display:inline-block;padding:14px 30px;font-family:<?= $font ?>;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:999px;"><?= e($label) ?> &rarr;</a>
                        </td>
                      </tr>
                    </table>
                    <p style="margin:16px 0 0;font-family:<?= $font ?>;font-size:12px;line-height:1.6;color:#8B7BAE;">Button funktioniert nicht? Kopiere diesen Link in deinen Browser:<br><a href="<?= e($url) ?>" style="color:#C084FC;word-break:break-all;"><?= e($url) ?></a></p>
                  </td>
                </tr>
                <?php endif ?>
                <tr>
                  <td class="pad" style="padding:22px 40px 34px;font-family:<?= $font ?>;">
                    <?php if ($m['greeting'] !== ''): ?>
                      <p style="margin:0;font-size:15px;line-height:1.6;color:#D8CCF2;">Viele Grüße<br><b style="color:#F5F0FF;"><?= e($business) ?></b></p>
                    <?php endif ?>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:26px 20px 0;font-family:<?= $font ?>;font-size:12px;line-height:1.7;color:#6E6090;">
              <?php if ($host !== ''): ?>
                <a href="<?= e($siteUrl) ?>" style="color:#A78BDB;text-decoration:none;"><?= e($host) ?></a>
                &nbsp;·&nbsp; <a href="<?= e($siteUrl) ?>/impressum" style="color:#A78BDB;text-decoration:none;">Impressum</a>
                &nbsp;·&nbsp; <a href="<?= e($siteUrl) ?>/datenschutz" style="color:#A78BDB;text-decoration:none;">Datenschutz</a><br>
              <?php endif ?>
              Diese E-Mail wurde automatisch versendet.
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:18px 20px 0;">
              <span style="display:inline-block;padding:6px 14px;border:1px solid #2E2147;border-radius:999px;font-family:<?= $mono ?>;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#6E6090;">Engineered by <b style="color:#C4B5FD;letter-spacing:1px;">Einfach</b><b style="color:#A855F7;letter-spacing:1px;">Dev</b></span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
