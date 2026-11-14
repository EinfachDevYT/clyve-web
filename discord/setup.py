#!/usr/bin/env python3
"""Builds the Clyve.dev Discord server: roles, channels, permissions, artwork, welcome texts and webhooks.

Safe to run again – existing roles and channels (matched by name) are updated, not duplicated.

    DISCORD_TOKEN=… [GUILD_ID=…] [SITE_URL=https://clyve.dev] python3 discord/setup.py [--clean] [--repost]

    --clean   delete Discord's default channels ("general", "Text Channels", …)
    --repost  post the welcome/rules/info messages again even if the bot already posted them

Only the Python standard library is used (3.9+).
"""
import base64
import json
import os
import re
import sys
import time
import urllib.error
import urllib.request
import uuid
from pathlib import Path

from server import CATEGORIES, ONBOARDING, P, ROLES, WELCOME_SCREEN, messages

API = os.environ.get("DISCORD_API", "https://discord.com/api/v10").rstrip("/")
TOKEN = os.environ.get("DISCORD_TOKEN", "")
SITE = os.environ.get("SITE_URL", "https://clyve.dev").rstrip("/")
ARGS = set(sys.argv[1:])
ASSETS = Path(__file__).resolve().parent / "assets"
TYPES = {"text": 0, "voice": 2, "category": 4, "news": 5, "forum": 15}
DEFAULT_NAMES = {"general", "allgemein", "text channels", "voice channels", "textkanäle", "sprachkanäle",
                 "lounge", "gaming", "hausaufgabenhilfe", "sitzungsplanung", "off-topic", "treffpunkt"}


class ApiError(Exception):
    def __init__(self, status, message):
        super().__init__(message)
        self.status = status


def log(icon, msg):
    print(f"{icon} {msg}", flush=True)


def data_uri(file):
    return "data:image/png;base64," + base64.b64encode((ASSETS / file).read_bytes()).decode()


def api(method, path, body=None, files=None):
    headers = {"Authorization": f"Bot {TOKEN}", "User-Agent": "clyve-setup (https://clyve.dev, 1.0)"}
    data = None
    if files:
        boundary = uuid.uuid4().hex
        parts = [f'--{boundary}\r\nContent-Disposition: form-data; name="payload_json"\r\n'
                 f"Content-Type: application/json\r\n\r\n{json.dumps(body)}\r\n".encode()]
        for i, f in enumerate(files):
            parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="files[{i}]"; filename="{f}"\r\n'
                         f"Content-Type: image/png\r\n\r\n".encode() + (ASSETS / f).read_bytes() + b"\r\n")
        parts.append(f"--{boundary}--\r\n".encode())
        data = b"".join(parts)
        headers["Content-Type"] = f"multipart/form-data; boundary={boundary}"
    elif body is not None:
        data = json.dumps(body).encode()
        headers["Content-Type"] = "application/json"

    for _ in range(6):
        req = urllib.request.Request(API + path, data=data, headers=headers, method=method)
        try:
            with urllib.request.urlopen(req, timeout=30) as res:
                raw = res.read()
                return json.loads(raw) if raw else None
        except urllib.error.HTTPError as e:
            raw = e.read()
            payload = json.loads(raw) if raw else {}
            if e.code == 429:
                time.sleep(float(payload.get("retry_after", 1)) + 0.25)
                continue
            detail = payload.get("message", "")
            if payload.get("errors"):
                detail += " " + json.dumps(payload["errors"], ensure_ascii=False)
            raise ApiError(e.code, f"{method} {path} → {e.code} {detail}") from None
    raise ApiError(429, f"{method} {path}: rate limited too often")


def soft(label, fn):
    try:
        return fn()
    except ApiError as e:
        log("⚠️ ", f"{label}: {e}")
        return None


def main():
    if not TOKEN:
        sys.exit("DISCORD_TOKEN fehlt. Siehe discord/README.md.")
    me = api("GET", "/users/@me")
    guild_id = os.environ.get("GUILD_ID", "")
    if not guild_id:
        guilds = api("GET", "/users/@me/guilds")
        if len(guilds) != 1:
            if guilds:
                sys.exit(f"Der Bot ist auf {len(guilds)} Servern – bitte GUILD_ID angeben:\n"
                         + "\n".join(f"  {g['id']}  {g['name']}" for g in guilds))
            sys.exit("Der Bot ist noch auf keinem Server. Einladen:\n"
                     f"https://discord.com/oauth2/authorize?client_id={me['id']}&scope=bot&permissions=8")
        guild_id = guilds[0]["id"]
    guild = api("GET", f"/guilds/{guild_id}")
    log("🌸", f"Richte „{guild['name']}“ ein (als {me['username']}) …")

    # ---------- roles ----------
    role_ids = {}
    roles = api("GET", f"/guilds/{guild_id}/roles")
    for r in ROLES:
        body = {"name": r["name"], "color": r["color"], "hoist": r.get("hoist", False),
                "mentionable": r.get("mentionable", False), "permissions": str(r["permissions"])}
        existing = next((x for x in roles if x["name"] == r["name"] and not x.get("managed")), None)
        if existing:
            role = soft(f"Rolle {r['name']}", lambda: api("PATCH", f"/guilds/{guild_id}/roles/{existing['id']}", body)) or existing
        else:
            role = api("POST", f"/guilds/{guild_id}/roles", body)
        role_ids[r["key"]] = role["id"]
        log("↻ " if existing else "＋", f"Rolle {r['name']}")

    member = api("GET", f"/guilds/{guild_id}/members/{me['id']}")
    roles = api("GET", f"/guilds/{guild_id}/roles")
    bot_top = max([0] + [x["position"] for x in roles if x["id"] in member["roles"]])
    positions = [{"id": role_ids[r["key"]], "position": max(1, bot_top - 1 - i)} for i, r in enumerate(ROLES)]
    soft("Rollen sortieren (zieh die Bot-Rolle ganz nach oben und starte erneut)",
         lambda: api("PATCH", f"/guilds/{guild_id}/roles", positions))
    staff = [role_ids["team"], role_ids["dev"], role_ids["support"]]

    # ---------- guild settings ----------
    soft("Server-Icon", lambda: api("PATCH", f"/guilds/{guild_id}", {"icon": data_uri("icon.png")}))
    if "BANNER" in guild["features"]:
        soft("Banner", lambda: api("PATCH", f"/guilds/{guild_id}", {"banner": data_uri("banner.png")}))
    if "INVITE_SPLASH" in guild["features"]:
        soft("Einladungs-Hintergrund", lambda: api("PATCH", f"/guilds/{guild_id}", {"splash": data_uri("splash.png")}))
    soft("Server-Einstellungen", lambda: api("PATCH", f"/guilds/{guild_id}", {
        "verification_level": 1, "explicit_content_filter": 2, "default_message_notifications": 1, "preferred_locale": "de"}))

    # ---------- channels ----------
    channels = api("GET", f"/guilds/{guild_id}/channels")
    if "--clean" in ARGS:
        ours = {c["name"] for c in CATEGORIES} | {x["name"] for c in CATEGORIES for x in c["channels"]}
        for c in [c for c in channels if c["name"].lower() in DEFAULT_NAMES and c["name"] not in ours]:
            soft(f"Löschen {c['name']}", lambda: api("DELETE", f"/channels/{c['id']}"))
            log("－", f"Standard-Kanal „{c['name']}“ entfernt")
        channels = api("GET", f"/guilds/{guild_id}/channels")

    def overwrites(access):
        out = []

        def add(target, rule, kind=0):
            if rule:
                out.append({"id": target, "type": kind, "allow": str(rule.get("allow", 0)), "deny": str(rule.get("deny", 0))})

        add(guild_id, access.get("everyone"))
        add(role_ids["customer"], access.get("customer"))
        for sid in staff:
            add(sid, access.get("staff"))
        bot_rights = {"allow": P.VIEW | P.SEND | P.EMBED | P.ATTACH | P.HISTORY}
        add(role_ids["bots"], bot_rights)
        add(me["id"], bot_rights, kind=1)
        return out

    def ensure(spec, parent_id, position, type_name):
        nonlocal channels
        t = TYPES[type_name]
        body = {"name": spec["name"], "type": t, "parent_id": parent_id, "position": position,
                "permission_overwrites": overwrites(spec["access"])}
        if spec["topic"] and t != TYPES["voice"]:
            body["topic"] = spec["topic"]
        if t == TYPES["forum"]:
            body["available_tags"] = [{"name": n, "emoji_name": e} for n, e in spec["tags"]]
            body["default_reaction_emoji"] = {"emoji_name": "🌸"}
        existing = next((c for c in channels if c["name"] == spec["name"] and c["type"] != TYPES["category"]), None)
        patch = {k: v for k, v in body.items() if k != "type"}
        if existing and (existing["type"] == t or (existing["type"] == TYPES["text"] and t == TYPES["news"])):
            if existing["type"] != t:
                patch["type"] = t  # text → announcement keeps the channel
            return api("PATCH", f"/channels/{existing['id']}", patch), "↻ "
        if existing:
            api("DELETE", f"/channels/{existing['id']}")  # e.g. text → forum: recreate
        return api("POST", f"/guilds/{guild_id}/channels", body), "＋"

    ids = {}
    community = "COMMUNITY" in guild["features"]
    for ci, cat in enumerate(CATEGORIES):
        existing_cat = next((c for c in channels if c["name"] == cat["name"] and c["type"] == TYPES["category"]), None)
        if existing_cat:
            api("PATCH", f"/channels/{existing_cat['id']}", {"position": ci})
            category = existing_cat
        else:
            category = api("POST", f"/guilds/{guild_id}/channels", {"name": cat["name"], "type": TYPES["category"], "position": ci})
            log("＋", f"Kategorie {cat['name']}")
        for i, spec in enumerate(cat["channels"]):
            wanted = "text" if not community and spec["type"] in ("news", "forum") else spec["type"]
            channel, mark = ensure(spec, category["id"], i, wanted)
            ids[spec["key"]] = channel["id"]
            log(mark, spec["name"] + (" (als Text, Community noch aus)" if wanted != spec["type"] else ""))
        channels = api("GET", f"/guilds/{guild_id}/channels")
        if not community and "rules" in ids and "mod_log" in ids:
            ok = soft("Community aktivieren", lambda: api("PATCH", f"/guilds/{guild_id}", {
                "features": sorted(set(guild["features"]) | {"COMMUNITY"}),
                "rules_channel_id": ids["rules"], "public_updates_channel_id": ids["mod_log"]}))
            if ok is not None:
                community = True
                log("✓ ", "Community aktiviert (Ankündigungs- und Forenkanäle möglich)")

    if community:  # channels created as text before Community was on
        for cat in CATEGORIES:
            for spec in cat["channels"]:
                cur = next((c for c in channels if c["id"] == ids.get(spec["key"])), None)
                if cur and cur["type"] != TYPES[spec["type"]]:
                    channel, _ = ensure(spec, cur.get("parent_id"), cur.get("position", 0), spec["type"])
                    ids[spec["key"]] = channel["id"]
                    log("↻ ", f"{spec['name']} → {'Ankündigungskanal' if spec['type'] == 'news' else 'Forum'}")
    soft("System-Kanal", lambda: api("PATCH", f"/guilds/{guild_id}", {"system_channel_id": ids["chat"], "system_channel_flags": 0b1100}))

    # ---------- welcome screen + onboarding ----------
    if community:
        soft("Willkommensbildschirm", lambda: api("PATCH", f"/guilds/{guild_id}/welcome-screen", {
            "enabled": True, "description": WELCOME_SCREEN["description"],
            "welcome_channels": [{"channel_id": ids[k], "description": d, "emoji_name": e} for k, d, e in WELCOME_SCREEN["channels"]]}))
        soft("Onboarding (Ping-Rollen)", lambda: api("PUT", f"/guilds/{guild_id}/onboarding", {
            "enabled": True, "mode": 0, "default_channel_ids": [ids[k] for k in ONBOARDING["defaults"] if k in ids],
            "prompts": [{"type": 0, "title": ONBOARDING["title"], "single_select": False, "required": False, "in_onboarding": True,
                         "options": [{"title": t, "description": d, "emoji_name": e, "role_ids": [role_ids[r]], "channel_ids": []}
                                     for t, d, e, r in ONBOARDING["options"]]}]}))

    # ---------- messages ----------
    def fill(v):
        if isinstance(v, str):
            v = re.sub(r"<#(\w+)>", lambda m: f"<#{ids[m[1]]}>" if m[1] in ids else m[0], v)
            return re.sub(r"<@&(\w+)>", lambda m: f"<@&{role_ids[m[1]]}>" if m[1] in role_ids else m[0], v)
        if isinstance(v, list):
            return [fill(x) for x in v]
        if isinstance(v, dict):
            return {k: fill(x) for k, x in v.items()}
        return v

    for key, items in messages(SITE).items():
        channel_id = ids.get(key)
        if not channel_id:
            continue
        recent = api("GET", f"/channels/{channel_id}/messages?limit=20")
        if any(m.get("author", {}).get("id") == me["id"] for m in recent) and "--repost" not in ARGS:
            log("· ", f"Nachrichten in {key} schon vorhanden (--repost zum Neusenden)")
            continue
        for msg in items:
            payload = fill(msg)
            image = payload.pop("image", None)
            payload["allowed_mentions"] = {"parse": []}
            api("POST", f"/channels/{channel_id}/messages", payload, [image] if image else None)
        log("✉️ ", f"Nachrichten in {key} gepostet")

    # ---------- webhooks for the website ----------
    def hook(channel_key, name):
        found = next((w for w in api("GET", f"/channels/{ids[channel_key]}/webhooks") if w["name"] == name and w.get("token")), None)
        return found or api("POST", f"/channels/{ids[channel_key]}/webhooks", {"name": name, "avatar": data_uri("icon.png")})

    site_hook = soft("Webhook Website", lambda: hook("site_events", "Clyve.dev"))
    devlog_hook = soft("Webhook Devlog", lambda: hook("devlog", "Clyve.dev Devlog"))
    url = lambda w: f"https://discord.com/api/webhooks/{w['id']}/{w['token']}" if w else "(nicht erstellt)"

    print(f"""
✅ Fertig! Trag das im Staff-Bereich unter Verwaltung → Integrationen ein:

   Discord-Webhook (Benachrichtigungen):  {url(site_hook)}
   Devlog-Kanal Webhook-URL:              {url(devlog_hook)}
   Devlog – Rolle erwähnen (Rollen-ID):   {role_ids['ping_devlog']}

   Die Webhook-URLs sind geheim – nicht öffentlich posten.
""" + ("" if community else """
⚠️  Community ist aus: Ankündigungs- und Forenkanäle wurden als Textkanäle angelegt.
   Aktiviere Community in den Servereinstellungen und starte das Skript erneut.
""") + f"""
Weitere Schritte (nur im Discord-Client möglich):
   • Dir selbst die Rolle „{ROLES[0]['name']}“ geben, Kunden die Rolle „Kunde“
   • Den Bot danach wieder entfernen – er wird nur für das Setup gebraucht
""")


if __name__ == "__main__":
    try:
        main()
    except ApiError as e:
        print(f"❌ {e}", file=sys.stderr)
        if e.status == 401:
            print("   Der Bot-Token stimmt nicht (Developer Portal → Bot → Reset Token).", file=sys.stderr)
        if e.status == 403:
            print("   Fehlende Rechte – lade den Bot mit Administrator-Recht ein und zieh seine Rolle nach oben.", file=sys.stderr)
        sys.exit(1)
