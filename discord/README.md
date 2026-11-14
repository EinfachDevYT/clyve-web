# Discord server setup

Builds the Clyve.dev Discord server in the website's design with one command: roles in the brand colours,
categories and channels with permissions, server icon/banner, welcome screen, onboarding with ping roles,
welcome/rules/info messages with buttons, and the two webhooks the website uses.

No dependencies – Python 3.9+ standard library only.

## 1. Create server and bot

1. In Discord: **+ → Create My Own → For a club or community**, name it e.g. `Clyve.dev`.
2. [Discord Developer Portal](https://discord.com/developers/applications) → **New Application** → **Bot** → **Reset Token**, copy the token.
3. **OAuth2 → URL Generator**: scope `bot`, permission `Administrator`, open the URL and add the bot to your new server.
4. Server settings → **Roles**: drag the bot's role to the very top (otherwise it can't sort the other roles).

## 2. Run

On Windows (PowerShell): `$env:DISCORD_TOKEN="your-bot-token"; py discord\setup.py --clean`


```bash
DISCORD_TOKEN=your-bot-token python3 discord/setup.py --clean
```

- `GUILD_ID=…` – only needed if the bot is on more than one server
- `SITE_URL=…` – website for the buttons (default `https://clyve.dev`)
- `--clean` – removes Discord's default channels (`general`, „Text Channels“, …)
- `--repost` – posts the welcome/rules/info messages again

The script can be run again any time after editing `server.py`: roles and channels are matched by name and updated.

At the end it prints the webhook URLs and the devlog role ID – enter them in the staff area under
Verwaltung → Integrationen (Discord webhook, devlog webhook, role to mention). Keep the webhook URLs secret.

## 3. Afterwards

- Give yourself the „Clyve Team“ role, customers get „Kunde“ (unlocks the customers' channel).
- Kick the bot – it's only needed for the setup; webhooks and messages stay.
- Banner and invite background need server boosts; the script sets them automatically once they're available (just run it again).

## What gets created

| Category | Channels |
|---|---|
| 🌸 START | willkommen, regeln, infos (read-only, with embeds and link buttons) |
| 📣 NEWS | ankündigungen, devlog, releases (announcement channels, followable) |
| 💬 COMMUNITY | chat, off-topic, zeig-her, fragen (forum with tags), feedback |
| 🛠️ KUNDEN | kunden-chat (role „Kunde“ only) |
| 🎧 VOICE | Lounge, Dev-Talk |
| 🔒 TEAM | team-chat, website (webhook for site notifications), mod-log, Team voice |

Roles: Clyve Team, Developer, Support, Kunde, Bots and the self-assignable ping roles Devlog-Ping, Release-Ping,
News-Ping (picked in Discord's onboarding). Layout and texts live in `server.py`, artwork in `assets/`.
