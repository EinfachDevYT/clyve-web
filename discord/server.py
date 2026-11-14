"""Layout and texts of the Clyve.dev Discord server. Edit freely, then run setup.py again."""

COLOR = 0xA855F7


class P:
    VIEW = 1 << 10
    SEND = 1 << 11
    THREAD_SEND = 1 << 38
    PUBLIC_THREADS = 1 << 35
    REACT = 1 << 6
    HISTORY = 1 << 16
    CONNECT = 1 << 20
    SPEAK = 1 << 21
    MANAGE_MESSAGES = 1 << 13
    KICK = 1 << 1
    BAN = 1 << 2
    TIMEOUT = 1 << 40
    MANAGE_THREADS = 1 << 34
    MENTION_ALL = 1 << 17
    AUDIT_LOG = 1 << 7
    ATTACH = 1 << 15
    EMBED = 1 << 14
    NICKNAMES = 1 << 27
    PRIORITY_SPEAKER = 1 << 8
    MOVE = 1 << 24


MOD = (P.MANAGE_MESSAGES | P.KICK | P.BAN | P.TIMEOUT | P.MANAGE_THREADS | P.MENTION_ALL
       | P.AUDIT_LOG | P.NICKNAMES | P.MOVE | P.PRIORITY_SPEAKER)

# top to bottom
ROLES = [
    {"key": "team", "name": "Clyve Team", "color": 0xA855F7, "hoist": True, "permissions": MOD},
    {"key": "dev", "name": "Developer", "color": 0x7C3AED, "hoist": True, "permissions": P.MANAGE_MESSAGES | P.MANAGE_THREADS | P.TIMEOUT},
    {"key": "support", "name": "Support", "color": 0xC4B5FD, "hoist": True, "permissions": P.MANAGE_MESSAGES | P.MANAGE_THREADS | P.TIMEOUT},
    {"key": "customer", "name": "Kunde", "color": 0xF0ABFC, "hoist": True, "permissions": 0},
    {"key": "bots", "name": "Bots", "color": 0x5F5578, "permissions": 0},
    {"key": "ping_devlog", "name": "Devlog-Ping", "color": 0x6D28D9, "mentionable": True, "permissions": 0},
    {"key": "ping_releases", "name": "Release-Ping", "color": 0x6D28D9, "mentionable": True, "permissions": 0},
    {"key": "ping_news", "name": "News-Ping", "color": 0x6D28D9, "mentionable": True, "permissions": 0},
]

# who may see / write
READ_ONLY = {"everyone": {"allow": P.VIEW | P.HISTORY | P.REACT, "deny": P.SEND | P.PUBLIC_THREADS | P.THREAD_SEND},
             "staff": {"allow": P.SEND}}
OPEN = {"everyone": {"allow": P.VIEW | P.SEND | P.HISTORY | P.REACT | P.ATTACH | P.EMBED | P.THREAD_SEND}}
CUSTOMERS = {"everyone": {"deny": P.VIEW},
             "customer": {"allow": P.VIEW | P.SEND | P.HISTORY | P.REACT | P.ATTACH | P.EMBED},
             "staff": {"allow": P.VIEW | P.SEND}}
TEAM = {"everyone": {"deny": P.VIEW},
        "staff": {"allow": P.VIEW | P.SEND | P.HISTORY | P.ATTACH | P.EMBED | P.CONNECT | P.SPEAK}}


def ch(key, name, type_, access, topic="", tags=None):
    return {"key": key, "name": name, "type": type_, "access": access, "topic": topic, "tags": tags or []}


# type: text | news | forum | voice   (news/forum are created as text until Community is on)
CATEGORIES = [
    {"name": "🌸 START", "channels": [
        ch("welcome", "🌸・willkommen", "text", READ_ONLY, "Schön, dass du da bist! Alles Wichtige zu Clyve.dev auf einen Blick."),
        ch("rules", "📜・regeln", "text", READ_ONLY, "Bitte lesen – gilt für alle Kanäle."),
        ch("info", "💡・infos", "text", READ_ONLY, "Leistungen, Ablauf eines Auftrags und Support."),
    ]},
    {"name": "📣 NEWS", "channels": [
        ch("news", "📣・ankündigungen", "news", READ_ONLY, "Neuigkeiten rund um Clyve.dev."),
        ch("devlog", "📰・devlog", "news", READ_ONLY, "Neu · Verbessert · Behoben – direkt von der Website."),
        ch("releases", "🚀・releases", "news", READ_ONLY, "Neue Scripts und Updates aus dem Shop."),
    ]},
    {"name": "💬 COMMUNITY", "channels": [
        ch("chat", "💬・chat", "text", OPEN, "Allgemeiner Chat – sei nett zueinander. 🌸"),
        ch("offtopic", "🎲・off-topic", "text", OPEN, "Alles, was sonst nirgends passt."),
        ch("showcase", "✨・zeig-her", "text", OPEN, "Zeig deine Server, Projekte und Setups."),
        ch("help", "❓・fragen", "forum", OPEN, "Fragen zu FiveM, Web und Bots – ein Thread pro Frage.",
           [("FiveM", "🎮"), ("Website", "🌐"), ("Discord-Bot", "🤖"), ("Allgemein", "💬"), ("Gelöst", "✅")]),
        ch("feedback", "💜・feedback", "text", OPEN, "Ideen, Wünsche und Feedback zu Scripts und Website."),
    ]},
    {"name": "🛠️ KUNDEN", "channels": [
        ch("customer_chat", "🤝・kunden-chat", "text", CUSTOMERS,
           "Nur für Kunden – Austausch und kurze Fragen. Für Support bitte ein Ticket auf der Website."),
    ]},
    {"name": "🎧 VOICE", "channels": [
        ch("lounge", "🎧 Lounge", "voice", OPEN),
        ch("devtalk", "🛠️ Dev-Talk", "voice", OPEN),
    ]},
    {"name": "🔒 TEAM", "channels": [
        ch("team_chat", "🔒・team-chat", "text", TEAM, "Intern."),
        ch("site_events", "🔔・website", "text", TEAM, "Neue Anfragen, Tickets und Hosting-Meldungen von der Website (Webhook)."),
        ch("mod_log", "🛡️・mod-log", "text", TEAM, "Discord-Systemmeldungen für Moderatoren."),
        ch("team_voice", "🔒 Team", "voice", TEAM),
    ]},
]


def btn(label, url, emoji=None):
    b = {"type": 2, "style": 5, "label": label, "url": url}
    if emoji:
        b["emoji"] = {"name": emoji}
    return b


def messages(site):
    """Messages per channel key. <#key> and <@&key> are replaced with real IDs."""
    return {
        "welcome": [{
            "image": "welcome.png",
            "embeds": [{
                "color": COLOR,
                "title": "Willkommen bei Clyve.dev 🌸",
                "description": "Hier entstehen **FiveM-Scripts**, **Websites** und **Discord-Bots** nach Maß – sauber, "
                               "performant und mit Liebe zum Detail.\n\nSchau dich um, stell Fragen in <#help> und bleib "
                               "in <#devlog> auf dem Laufenden.",
                "fields": [
                    {"name": "📜 Erst lesen", "value": "<#rules>", "inline": True},
                    {"name": "💡 Leistungen & Ablauf", "value": "<#info>", "inline": True},
                    {"name": "💬 Quatschen", "value": "<#chat>", "inline": True},
                ],
                "image": {"url": "attachment://welcome.png"},
                "footer": {"text": "Engineered by EinfachDev"},
            }],
            "components": [{"type": 1, "components": [
                btn("Website", site, "🌐"), btn("Projekt anfragen", site + "/#anfrage", "✨"),
                btn("Scripts", site + "/scripts", "🛒"), btn("Kundenbereich", site + "/login", "🔐"),
            ]}],
        }],
        "rules": [{
            "embeds": [{
                "color": COLOR,
                "title": "📜 Regeln",
                "description": "\n\n".join([
                    "**1 · Respekt** – Kein Beleidigen, Hetzen, Diskriminieren oder Provozieren.",
                    "**2 · Kein Spam** – Keine Massen-Pings, Flut-Nachrichten oder Werbung ohne Erlaubnis.",
                    "**3 · Richtiger Kanal** – Fragen in <#help>, Projekte in <#showcase>, Rest in <#chat>.",
                    "**4 · Keine geleakten Scripts** – Kein Teilen, Anbieten oder Anfragen von Leaks oder Cracks.",
                    "**5 · Keine Selbstjustiz** – Probleme mit anderen? Melde dich beim <@&team>.",
                    "**6 · Datenschutz** – Keine privaten Daten (eigene oder fremde) öffentlich posten.",
                    "**7 · Support** – Kundensupport läuft über Tickets auf der Website, nicht per DM.",
                    "**8 · Discord-Regeln** – Es gelten die [Community-Richtlinien](https://discord.com/guidelines) "
                    "und [Nutzungsbedingungen](https://discord.com/terms).",
                ]),
                "footer": {"text": "Mit dem Schreiben auf diesem Server akzeptierst du die Regeln. Das Team kann Verstöße ahnden."},
            }],
        }],
        "info": [{
            "embeds": [
                {"color": COLOR, "title": "✨ Was wir machen", "fields": [
                    {"name": "🎮 FiveM-Scripts", "value": "Jobs, UIs, Systeme – für ESX, QBCore & Standalone."},
                    {"name": "🌐 Websites", "value": "Landingpages, Portale und Shops – schnell und sicher."},
                    {"name": "🤖 Discord-Bots", "value": "Tickets, Bewerbungen, Moderation – inkl. Hosting auf Wunsch."},
                ]},
                {"color": COLOR, "title": "🧭 So läuft ein Auftrag",
                 "description": "**1.** Anfrage über die Website stellen – mit oder ohne Konto\n"
                                "**2.** Rückfragen & Angebot direkt im Anfrage-Verlauf\n"
                                "**3.** Angebot annehmen → Umsetzung mit Fortschritt in %\n"
                                "**4.** Lieferung, Feinschliff und Beleg im Kundenbereich"},
                {"color": COLOR, "title": "🎫 Support",
                 "description": "Für Fragen zu deinem Auftrag, deinen Scripts oder gehosteten Bots erstell bitte ein "
                                "**Ticket im Kundenbereich** – so geht nichts verloren. Allgemeine Fragen gern in <#help>."},
            ],
            "components": [{"type": 1, "components": [
                btn("Projekt anfragen", site + "/#anfrage", "✨"), btn("Ticket erstellen", site + "/konto/tickets/neu", "🎫"),
                btn("Devlog", site + "/konto/devlog", "📰"),
            ]}],
        }],
        "devlog": [{
            "image": "devlog.png",
            "embeds": [{
                "color": COLOR,
                "description": "Hier landen alle Devlogs der Website – im Stil eines Changelogs:\n"
                               "```diff\n+ Neu hinzugefügt\n- Entfernt\n~ Verbessert\n! Behoben\n```\n"
                               "Hol dir <@&ping_devlog>, um nichts zu verpassen.",
                "image": {"url": "attachment://devlog.png"},
            }],
        }],
    }


WELCOME_SCREEN = {
    "description": "FiveM-Scripts, Websites & Discord-Bots – engineered by EinfachDev. 🌸",
    "channels": [
        ("rules", "Kurz die Regeln lesen", "📜"),
        ("info", "Leistungen & Ablauf eines Auftrags", "💡"),
        ("devlog", "Was sich gerade tut", "📰"),
        ("help", "Fragen stellen", "❓"),
        ("chat", "Mit der Community quatschen", "💬"),
    ],
}

ONBOARDING = {
    "defaults": ["welcome", "rules", "info", "news", "devlog", "releases", "chat", "offtopic", "showcase", "help", "feedback"],
    "title": "Worüber möchtest du benachrichtigt werden?",
    "options": [
        ("Devlogs", "Neue Features & Updates der Website", "📰", "ping_devlog"),
        ("Script-Releases", "Neue Scripts im Shop", "🚀", "ping_releases"),
        ("Ankündigungen", "Wichtige News", "📣", "ping_news"),
    ],
}
