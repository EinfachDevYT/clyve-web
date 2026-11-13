// Layout and texts of the Clyve.dev Discord server. Edit freely, then run setup.mjs again.

export const COLOR = 0xA855F7;

export const P = {
  VIEW: 1n << 10n, SEND: 1n << 11n, THREAD_SEND: 1n << 38n, PUBLIC_THREADS: 1n << 35n, REACT: 1n << 6n,
  HISTORY: 1n << 16n, CONNECT: 1n << 20n, SPEAK: 1n << 21n, MANAGE_MESSAGES: 1n << 13n, KICK: 1n << 1n,
  BAN: 1n << 2n, TIMEOUT: 1n << 40n, MANAGE_THREADS: 1n << 34n, MENTION_ALL: 1n << 17n, AUDIT_LOG: 1n << 7n,
  ATTACH: 1n << 15n, EMBED: 1n << 14n, NICKNAMES: 1n << 27n, PRIORITY_SPEAKER: 1n << 8n, MOVE: 1n << 24n,
};

const MOD = P.MANAGE_MESSAGES | P.KICK | P.BAN | P.TIMEOUT | P.MANAGE_THREADS | P.MENTION_ALL | P.AUDIT_LOG | P.NICKNAMES | P.MOVE | P.PRIORITY_SPEAKER;

// top to bottom
export const ROLES = [
  { key: 'team', name: 'Clyve Team', color: 0xA855F7, hoist: true, permissions: MOD },
  { key: 'dev', name: 'Developer', color: 0x7C3AED, hoist: true, permissions: P.MANAGE_MESSAGES | P.MANAGE_THREADS | P.TIMEOUT },
  { key: 'support', name: 'Support', color: 0xC4B5FD, hoist: true, permissions: P.MANAGE_MESSAGES | P.MANAGE_THREADS | P.TIMEOUT },
  { key: 'customer', name: 'Kunde', color: 0xF0ABFC, hoist: true, permissions: 0n },
  { key: 'bots', name: 'Bots', color: 0x5F5578, hoist: false, permissions: 0n },
  { key: 'ping_devlog', name: 'Devlog-Ping', color: 0x6D28D9, mentionable: true, permissions: 0n },
  { key: 'ping_releases', name: 'Release-Ping', color: 0x6D28D9, mentionable: true, permissions: 0n },
  { key: 'ping_news', name: 'News-Ping', color: 0x6D28D9, mentionable: true, permissions: 0n },
];

// access presets: who may see / write
const READ_ONLY = { everyone: { allow: P.VIEW | P.HISTORY | P.REACT, deny: P.SEND | P.PUBLIC_THREADS | P.THREAD_SEND }, staff: { allow: P.SEND } };
const OPEN = { everyone: { allow: P.VIEW | P.SEND | P.HISTORY | P.REACT | P.ATTACH | P.EMBED | P.THREAD_SEND } };
const CUSTOMERS = { everyone: { deny: P.VIEW }, customer: { allow: P.VIEW | P.SEND | P.HISTORY | P.REACT | P.ATTACH | P.EMBED }, staff: { allow: P.VIEW | P.SEND } };
const TEAM = { everyone: { deny: P.VIEW }, staff: { allow: P.VIEW | P.SEND | P.HISTORY | P.ATTACH | P.EMBED | P.CONNECT | P.SPEAK } };

// type: text | news | forum | voice   (news/forum fall back to text without Community)
export const CATEGORIES = [
  { name: '🌸 START', channels: [
    { key: 'welcome', name: '🌸・willkommen', type: 'text', access: READ_ONLY, topic: 'Schön, dass du da bist! Alles Wichtige zu Clyve.dev auf einen Blick.' },
    { key: 'rules', name: '📜・regeln', type: 'text', access: READ_ONLY, topic: 'Bitte lesen – gilt für alle Kanäle.' },
    { key: 'info', name: '💡・infos', type: 'text', access: READ_ONLY, topic: 'Leistungen, Ablauf eines Auftrags und Support.' },
  ] },
  { name: '📣 NEWS', channels: [
    { key: 'news', name: '📣・ankündigungen', type: 'news', access: READ_ONLY, topic: 'Neuigkeiten rund um Clyve.dev.' },
    { key: 'devlog', name: '📰・devlog', type: 'news', access: READ_ONLY, topic: 'Neu · Verbessert · Behoben – direkt von der Website.' },
    { key: 'releases', name: '🚀・releases', type: 'news', access: READ_ONLY, topic: 'Neue Scripts und Updates aus dem Shop.' },
  ] },
  { name: '💬 COMMUNITY', channels: [
    { key: 'chat', name: '💬・chat', type: 'text', access: OPEN, topic: 'Allgemeiner Chat – sei nett zueinander. 🌸' },
    { key: 'offtopic', name: '🎲・off-topic', type: 'text', access: OPEN, topic: 'Alles, was sonst nirgends passt.' },
    { key: 'showcase', name: '✨・zeig-her', type: 'text', access: OPEN, topic: 'Zeig deine Server, Projekte und Setups.' },
    { key: 'help', name: '❓・fragen', type: 'forum', access: OPEN, topic: 'Fragen zu FiveM, Web und Bots – ein Thread pro Frage.',
      tags: [['FiveM', '🎮'], ['Website', '🌐'], ['Discord-Bot', '🤖'], ['Allgemein', '💬'], ['Gelöst', '✅']] },
    { key: 'feedback', name: '💜・feedback', type: 'text', access: OPEN, topic: 'Ideen, Wünsche und Feedback zu Scripts und Website.' },
  ] },
  { name: '🛠️ KUNDEN', channels: [
    { key: 'customer_chat', name: '🤝・kunden-chat', type: 'text', access: CUSTOMERS, topic: 'Nur für Kunden – Austausch und kurze Fragen. Für Support bitte ein Ticket auf der Website.' },
  ] },
  { name: '🎧 VOICE', channels: [
    { key: 'lounge', name: '🎧 Lounge', type: 'voice', access: OPEN },
    { key: 'devtalk', name: '🛠️ Dev-Talk', type: 'voice', access: OPEN },
  ] },
  { name: '🔒 TEAM', channels: [
    { key: 'team_chat', name: '🔒・team-chat', type: 'text', access: TEAM, topic: 'Intern.' },
    { key: 'site_events', name: '🔔・website', type: 'text', access: TEAM, topic: 'Neue Anfragen, Tickets und Hosting-Meldungen von der Website (Webhook).' },
    { key: 'mod_log', name: '🛡️・mod-log', type: 'text', access: TEAM, topic: 'Discord-Systemmeldungen für Moderatoren.' },
    { key: 'team_voice', name: '🔒 Team', type: 'voice', access: TEAM },
  ] },
];

const btn = (label, url, emoji) => ({ type: 2, style: 5, label, url, ...(emoji ? { emoji: { name: emoji } } : {}) });

export function messages(site) {
  return {
    welcome: [{
      image: 'welcome.png',
      embeds: [{
        color: COLOR,
        title: 'Willkommen bei Clyve.dev 🌸',
        description: 'Hier entstehen **FiveM-Scripts**, **Websites** und **Discord-Bots** nach Maß – sauber, performant und mit Liebe zum Detail.\n\nSchau dich um, stell Fragen in <#help> und bleib in <#devlog> auf dem Laufenden.',
        fields: [
          { name: '📜 Erst lesen', value: '<#rules>', inline: true },
          { name: '💡 Leistungen & Ablauf', value: '<#info>', inline: true },
          { name: '💬 Quatschen', value: '<#chat>', inline: true },
        ],
        image: { url: 'attachment://welcome.png' },
        footer: { text: 'Engineered by EinfachDev' },
      }],
      components: [{ type: 1, components: [
        btn('Website', site, '🌐'), btn('Projekt anfragen', site + '/#anfrage', '✨'), btn('Scripts', site + '/scripts', '🛒'), btn('Kundenbereich', site + '/login', '🔐'),
      ] }],
    }],
    rules: [{
      embeds: [{
        color: COLOR,
        title: '📜 Regeln',
        description: [
          '**1 · Respekt** – Kein Beleidigen, Hetzen, Diskriminieren oder Provozieren.',
          '**2 · Kein Spam** – Keine Massen-Pings, Flut-Nachrichten oder Werbung ohne Erlaubnis.',
          '**3 · Richtiger Kanal** – Fragen in <#help>, Projekte in <#showcase>, Rest in <#chat>.',
          '**4 · Keine geleakten Scripts** – Kein Teilen, Anbieten oder Anfragen von Leaks oder Cracks.',
          '**5 · Keine Selbstjustiz** – Probleme mit anderen? Melde dich beim <@&team>.',
          '**6 · Datenschutz** – Keine privaten Daten (eigene oder fremde) öffentlich posten.',
          '**7 · Support** – Kundensupport läuft über Tickets auf der Website, nicht per DM.',
          '**8 · Discord-Regeln** – Es gelten die [Community-Richtlinien](https://discord.com/guidelines) und [Nutzungsbedingungen](https://discord.com/terms).',
        ].join('\n\n'),
        footer: { text: 'Mit dem Schreiben auf diesem Server akzeptierst du die Regeln. Das Team kann Verstöße ahnden.' },
      }],
    }],
    info: [{
      embeds: [
        {
          color: COLOR,
          title: '✨ Was wir machen',
          fields: [
            { name: '🎮 FiveM-Scripts', value: 'Jobs, UIs, Systeme – für ESX, QBCore & Standalone.', inline: false },
            { name: '🌐 Websites', value: 'Landingpages, Portale und Shops – schnell und sicher.', inline: false },
            { name: '🤖 Discord-Bots', value: 'Tickets, Bewerbungen, Moderation – inkl. Hosting auf Wunsch.', inline: false },
          ],
        },
        {
          color: COLOR,
          title: '🧭 So läuft ein Auftrag',
          description: '**1.** Anfrage über die Website stellen – mit oder ohne Konto\n**2.** Rückfragen & Angebot direkt im Anfrage-Verlauf\n**3.** Angebot annehmen → Umsetzung mit Fortschritt in %\n**4.** Lieferung, Feinschliff und Beleg im Kundenbereich',
        },
        {
          color: COLOR,
          title: '🎫 Support',
          description: 'Für Fragen zu deinem Auftrag, deinen Scripts oder gehosteten Bots erstell bitte ein **Ticket im Kundenbereich** – so geht nichts verloren. Allgemeine Fragen gern in <#help>.',
        },
      ],
      components: [{ type: 1, components: [
        btn('Projekt anfragen', site + '/#anfrage', '✨'), btn('Ticket erstellen', site + '/konto/tickets/neu', '🎫'), btn('Devlog', site + '/konto/devlog', '📰'),
      ] }],
    }],
    devlog: [{
      image: 'devlog.png',
      embeds: [{
        color: COLOR,
        description: 'Hier landen alle Devlogs der Website – im Stil eines Changelogs:\n```diff\n+ Neu hinzugefügt\n- Entfernt\n~ Verbessert\n! Behoben\n```\nHol dir <@&ping_devlog>, um nichts zu verpassen.',
        image: { url: 'attachment://devlog.png' },
      }],
    }],
  };
}

export const WELCOME_SCREEN = {
  description: 'FiveM-Scripts, Websites & Discord-Bots – engineered by EinfachDev. 🌸',
  channels: [
    { key: 'rules', description: 'Kurz die Regeln lesen', emoji: '📜' },
    { key: 'info', description: 'Leistungen & Ablauf eines Auftrags', emoji: '💡' },
    { key: 'devlog', description: 'Was sich gerade tut', emoji: '📰' },
    { key: 'help', description: 'Fragen stellen', emoji: '❓' },
    { key: 'chat', description: 'Mit der Community quatschen', emoji: '💬' },
  ],
};

export const ONBOARDING = {
  defaults: ['welcome', 'rules', 'info', 'news', 'devlog', 'releases', 'chat', 'offtopic', 'showcase', 'help', 'feedback'],
  prompt: {
    title: 'Worüber möchtest du benachrichtigt werden?',
    options: [
      { title: 'Devlogs', description: 'Neue Features & Updates der Website', emoji: '📰', role: 'ping_devlog' },
      { title: 'Script-Releases', description: 'Neue Scripts im Shop', emoji: '🚀', role: 'ping_releases' },
      { title: 'Ankündigungen', description: 'Wichtige News', emoji: '📣', role: 'ping_news' },
    ],
  },
};
